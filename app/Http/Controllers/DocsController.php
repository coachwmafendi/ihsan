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

        $base = $docsPath.DIRECTORY_SEPARATOR.$relative;
        $file = null;

        if (is_file($base.'.md')) {
            $file = realpath($base.'.md');
        } elseif (is_dir($base) && is_file($base.DIRECTORY_SEPARATOR.'index.md')) {
            $file = realpath($base.DIRECTORY_SEPARATOR.'index.md');
        }

        if ($file === false || $file === null || ! str_starts_with($file, $docsPath.DIRECTORY_SEPARATOR)) {
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
}
