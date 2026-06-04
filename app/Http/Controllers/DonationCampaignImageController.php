<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DonationCampaignImageController extends Controller
{
    private const int ModalImageMaxWidth = 1100;

    private const int ModalImageQuality = 76;

    public function __invoke(Element $element, Request $request): Response|StreamedResponse
    {
        abort_if(! $element->is_active, 404);

        $element->loadMissing('campaign');

        $imagePath = $element->campaign?->image_path;

        abort_if(blank($imagePath) || str($imagePath)->contains('..'), 404);
        $disk = $this->imageDisk($imagePath);

        abort_if($disk === null, 404);

        return $this->imageResponse($disk, $imagePath, $request);
    }

    public function campaignImage(Campaign $campaign, Request $request): Response|StreamedResponse
    {
        abort_if($campaign->status !== CampaignStatus::Active, 404);

        $imagePath = $campaign->image_path;

        abort_if(blank($imagePath) || str($imagePath)->contains('..'), 404);
        $disk = $this->imageDisk($imagePath);

        abort_if($disk === null, 404);

        return $this->imageResponse($disk, $imagePath, $request);
    }

    private function imageResponse(string $disk, string $imagePath, Request $request): Response|StreamedResponse
    {
        if ($request->string('variant')->toString() === 'modal') {
            $optimizedResponse = $this->optimizedModalImageResponse($disk, $imagePath);

            if ($optimizedResponse !== null) {
                return $optimizedResponse;
            }
        }

        return Storage::disk($disk)->response($imagePath, null, $this->cacheHeaders());
    }

    private function optimizedModalImageResponse(string $disk, string $imagePath): ?Response
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }

        $sourceDisk = Storage::disk($disk);
        $cacheDisk = Storage::disk('local');
        $cachePath = $this->modalImageCachePath($disk, $imagePath);

        if (! $cacheDisk->exists($cachePath)) {
            try {
                $sourceImage = imagecreatefromstring($sourceDisk->get($imagePath));
            } catch (Throwable) {
                return null;
            }

            if ($sourceImage === false) {
                return null;
            }

            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            if ($sourceWidth < 1 || $sourceHeight < 1) {
                imagedestroy($sourceImage);

                return null;
            }

            $targetWidth = min($sourceWidth, self::ModalImageMaxWidth);
            $targetHeight = (int) round($sourceHeight * ($targetWidth / $sourceWidth));

            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

            ob_start();
            $saved = imagewebp($targetImage, null, self::ModalImageQuality);
            $optimizedImage = ob_get_clean();

            imagedestroy($sourceImage);
            imagedestroy($targetImage);

            if (! $saved || $optimizedImage === false || $optimizedImage === '') {
                return null;
            }

            $cacheDisk->put($cachePath, $optimizedImage);
        }

        return response($cacheDisk->get($cachePath), 200, [
            ...$this->cacheHeaders(),
            'Content-Type' => 'image/webp',
        ]);
    }

    private function modalImageCachePath(string $disk, string $imagePath): string
    {
        $lastModified = 0;

        try {
            $lastModified = Storage::disk($disk)->lastModified($imagePath);
        } catch (Throwable) {
            $lastModified = 0;
        }

        return 'optimized-campaign-images/'.sha1($disk.'|'.$imagePath.'|'.$lastModified.'|modal-v1').'.webp';
    }

    /**
     * @return array<string, string>
     */
    private function cacheHeaders(): array
    {
        return [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ];
    }

    private function imageDisk(string $imagePath): ?string
    {
        $disks = Arr::whereNotNull([
            config('filesystems.default'),
            'public',
            'local',
        ]);

        foreach (array_unique($disks) as $disk) {
            if (Storage::disk($disk)->exists($imagePath)) {
                return $disk;
            }
        }

        return null;
    }
}
