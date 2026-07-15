<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Docs\MarkdownParser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

final class DocsController extends Controller
{
    public function __construct(private MarkdownParser $parser) {}

    public function __invoke(?string $path = null): Response
    {
        $docsPath = realpath(config('docs.path')) ?: resource_path('docs');
        $requested = $path ?? 'index';
        $relative = trim($requested, '/');
        $locale = app()->getLocale();

        $file = $this->resolveDocFile($docsPath, $relative, $locale);

        if ($file === null) {
            abort(404);
        }

        $html = $this->parser->toHtml(File::get($file));

        $title = null;
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/', $html, $matches)) {
            $title = strip_tags($matches[1]);
        }

        return response()->view('docs.show', [
            'html' => $html,
            'title' => $title,
            'path' => $relative,
        ]);
    }

    private function resolveDocFile(string $docsPath, string $relative, string $locale): ?string
    {
        $candidates = [];
        $localeDir = $docsPath.DIRECTORY_SEPARATOR.$locale;

        if (is_dir($localeDir)) {
            $candidates[] = $this->fileCandidate($localeDir, $relative);
        }

        $candidates[] = $this->fileCandidate($docsPath, $relative);

        foreach ($candidates as $candidate) {
            if ($candidate !== null && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function fileCandidate(string $base, string $relative): ?string
    {
        $base = rtrim($base, DIRECTORY_SEPARATOR);
        $path = $base.DIRECTORY_SEPARATOR.$relative;

        if (is_file($path.'.md')) {
            $resolved = realpath($path.'.md');
        } elseif (is_dir($path) && is_file($path.DIRECTORY_SEPARATOR.'index.md')) {
            $resolved = realpath($path.DIRECTORY_SEPARATOR.'index.md');
        } else {
            return null;
        }

        if ($resolved === false) {
            return null;
        }

        $safePrefix = $base.DIRECTORY_SEPARATOR;

        return str_starts_with($resolved, $safePrefix) ? $resolved : null;
    }
}
