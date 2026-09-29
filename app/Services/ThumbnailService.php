<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class ThumbnailService
{
    protected FileService $fileService;
    protected string $cachePath;
    protected array $sizes;
    protected int $quality;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
        $this->cachePath = config('thumbnails.cache_path', storage_path('app/thumbnails'));
        $this->sizes = config('thumbnails.sizes', ['sm' => 100, 'md' => 300, 'lg' => 800]);
        $this->quality = config('thumbnails.quality', 80);
    }

    public function get(string $relPath, string $size = 'md'): ?string
    {
        if (!$this->fileService->isImage($relPath)) {
            return null;
        }

        try {
            $cleanRel = $this->fileService->sanitizePath($relPath);
            $fullPath = $this->fileService->publicPath($cleanRel);

            if (!File::exists($fullPath) || File::isDirectory($fullPath)) {
                return null;
            }

            $dim = $this->sizes[$size] ?? 300;
            $mtime = File::lastModified($fullPath);

            $dir = dirname($cleanRel);
            $dir = ($dir === '.' || $dir === '/') ? '' : $dir;

            $hash = md5($cleanRel . '_' . $mtime);
            $filename = "{$hash}_{$size}.webp";

            $targetDir = $dir ? $this->cachePath . '/' . $dir : $this->cachePath;
            $cachedPath = $targetDir . '/' . $filename;

            if (File::exists($cachedPath)) {
                return $cachedPath;
            }

            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            $manager = new ImageManager(new GdDriver());
            $image = $manager->read($fullPath);
            $image->cover($dim, $dim);
            $encoded = $image->toWebp($this->quality);
            $encoded->save($cachedPath);

            return $cachedPath;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function url(string $relPath, string $size = 'md'): string
    {
        return route('thumb', ['size' => $size, 'path' => $relPath]);
    }

    public function clear(string $relPath): void
    {
        try {
            $cleanRel = $this->fileService->sanitizePath($relPath);
            $dir = dirname($cleanRel);
            $dir = ($dir === '.' || $dir === '/') ? '' : $dir;
            $targetDir = $dir ? $this->cachePath . '/' . $dir : $this->cachePath;

            if (File::exists($targetDir)) {
                $files = File::files($targetDir);
                foreach ($files as $f) {
                    if (str_contains($f->getFilename(), md5($cleanRel))) {
                        File::delete($f->getPathname());
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore cache clear failures
        }
    }

    public function clearAll(): void
    {
        if (File::exists($this->cachePath)) {
            File::cleanDirectory($this->cachePath);
        }
    }
}
