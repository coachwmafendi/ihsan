<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ReceiptImageOptimizer
{
    private const int MAX_WIDTH = 200;

    private const int MAX_HEIGHT = 200;

    private const int PNG_COMPRESSION = 8;

    private const array SUPPORTED_MIMES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /**
     * Return an absolute file path suitable for embedding in a DomPDF receipt.
     *
     * Large organization logos are downsampled to a small fixed dimension so the
     * generated PDF stays lightweight. Unsupported files or images that are
     * already small enough are returned as-is.
     */
    public static function receiptThumbnail(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $mime = @mime_content_type($path);

        if (! in_array($mime, self::SUPPORTED_MIMES, true)) {
            return $path;
        }

        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            return $path;
        }

        [$width, $height] = $dimensions;

        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            return $path;
        }

        $destination = self::cachePath($path);

        if ($destination === null) {
            return $path;
        }

        if (is_file($destination)) {
            return $destination;
        }

        return self::createThumbnail($path, $destination) ?? $path;
    }

    private static function cachePath(string $path): ?string
    {
        $mtime = @filemtime($path);

        if ($mtime === false) {
            return null;
        }

        $disk = Storage::disk('local');
        $hash = md5($path.'|'.$mtime);
        $directory = 'receipt-logos/'.$hash;

        $disk->makeDirectory($directory);

        return $disk->path($directory.'/logo.png');
    }

    private static function createThumbnail(string $source, string $destination): ?string
    {
        $content = @file_get_contents($source);

        if ($content === false) {
            return null;
        }

        $image = @imagecreatefromstring($content);

        if ($image === false) {
            return null;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        $ratio = min(self::MAX_WIDTH / $originalWidth, self::MAX_HEIGHT / $originalHeight, 1);
        $newWidth = (int) round($originalWidth * $ratio);
        $newHeight = (int) round($originalHeight * $ratio);

        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        if ($thumbnail === false) {
            return null;
        }

        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);

        imagecopyresampled(
            $thumbnail,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        $saved = @imagepng($thumbnail, $destination, self::PNG_COMPRESSION);

        return $saved ? $destination : null;
    }
}
