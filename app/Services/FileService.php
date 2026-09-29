<?php

namespace App\Services;

use App\Exceptions\ForbiddenExtensionException;
use App\Exceptions\InvalidPathException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class FileService
{
    protected array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'htaccess',
        'exe', 'sh', 'bat', 'cgi', 'pl', 'py', 'rb'
    ];

    /**
     * Sanitize relative path string to prevent path traversal.
     * Throws InvalidPathException if traversal attempt detected.
     */
    public function sanitizePath(string $rel): string
    {
        // Reject null bytes, backslashes, path traversal sequences
        if (str_contains($rel, "\0") || str_contains($rel, '\\') || str_contains($rel, '..')) {
            throw new InvalidPathException('Path traversal or invalid characters detected.');
        }

        $trimmed = trim($rel, '/ ');

        if (str_starts_with($trimmed, '/') || str_starts_with($trimmed, '\\')) {
            throw new InvalidPathException('Absolute paths are not allowed.');
        }

        return $trimmed;
    }

    /**
     * Resolve absolute path in public/ and ensure it remains strictly inside public_path().
     */
    public function publicPath(string $rel): string
    {
        $cleanRel = $this->sanitizePath($rel);
        $publicRoot = realpath(public_path());

        if ($publicRoot === false) {
            $publicRoot = public_path();
        }

        $targetPath = public_path($cleanRel);

        if (file_exists($targetPath)) {
            $realTarget = realpath($targetPath);
            if ($realTarget === false || !str_starts_with($realTarget, $publicRoot)) {
                throw new InvalidPathException('Path escapes public directory.');
            }
            return $realTarget;
        }

        // For non-existent target paths (e.g. upload/mkdir), check parent dir realpath
        $parentDir = dirname($targetPath);
        if (file_exists($parentDir)) {
            $realParent = realpath($parentDir);
            if ($realParent === false || !str_starts_with($realParent, $publicRoot)) {
                throw new InvalidPathException('Target directory escapes public directory.');
            }
        }

        return $targetPath;
    }

    /**
     * Sanitize file/folder name: allow a-z A-Z 0-9 . _ -, convert spaces to '-'.
     */
    public function sanitizeName(string $name): string
    {
        $name = str_replace(' ', '-', $name);
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $sanitized = trim($sanitized, '.-');

        if (empty($sanitized)) {
            $sanitized = 'file_' . time();
        }

        return $sanitized;
    }

    /**
     * Validate extension against forbidden list.
     */
    public function validateExtension(string $filename): void
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Check if filename itself is dangerous (e.g. .htaccess)
        if (strtolower($filename) === '.htaccess' || $ext === 'htaccess') {
            throw new ForbiddenExtensionException('Forbidden file type: htaccess');
        }

        if (in_array($ext, $this->dangerousExtensions, true)) {
            throw new ForbiddenExtensionException("Forbidden file extension: [{$ext}]");
        }
    }

    /**
     * List contents of a directory in public/.
     */
    public function list(string $rel = ''): array
    {
        $cleanRel = $this->sanitizePath($rel);
        $fullPath = $this->publicPath($cleanRel);

        if (!File::isDirectory($fullPath)) {
            throw new InvalidPathException("Directory [{$cleanRel}] does not exist.");
        }

        $items = File::allFiles($fullPath);
        $dirs = File::directories($fullPath);

        $foldersList = [];
        foreach ($dirs as $dir) {
            $baseName = basename($dir);

            // Hide dotfiles and root .trash folder
            if (str_starts_with($baseName, '.') || ($cleanRel === '' && $baseName === '.trash')) {
                continue;
            }

            $itemRel = $cleanRel === '' ? $baseName : $cleanRel . '/' . $baseName;

            $foldersList[] = [
                'name' => $baseName,
                'path' => $itemRel,
                'type' => 'folder',
                'size' => 0,
                'human_size' => '--',
                'mime' => 'directory',
                'extension' => '',
                'mtime' => File::lastModified($dir),
                'url' => $this->publicUrl($itemRel),
                'thumb' => null,
                'is_image' => false,
                'is_pdf' => false,
                'is_video' => false,
                'is_audio' => false,
            ];
        }

        // Direct files only (not recursive)
        $directFiles = File::files($fullPath);
        $filesList = [];

        foreach ($directFiles as $file) {
            $baseName = $file->getFilename();

            // Hide dotfiles (.env, .htaccess, .git, etc.)
            if (str_starts_with($baseName, '.')) {
                continue;
            }

            $itemRel = $cleanRel === '' ? $baseName : $cleanRel . '/' . $baseName;
            $ext = strtolower($file->getExtension());
            $mime = File::mimeType($file->getPathname()) ?: 'application/octet-stream';
            $size = $file->getSize();

            $isImg = $this->isImage($itemRel);

            $filesList[] = [
                'name' => $baseName,
                'path' => $itemRel,
                'type' => 'file',
                'size' => $size,
                'human_size' => $this->humanSize($size),
                'mime' => $mime,
                'extension' => $ext,
                'mtime' => $file->getMTime(),
                'url' => $this->publicUrl($itemRel),
                'thumb' => $isImg ? route('thumb', ['size' => 'md', 'path' => $itemRel]) : null,
                'is_image' => $isImg,
                'is_pdf' => $this->isPdf($itemRel),
                'is_video' => $this->isVideo($itemRel),
                'is_audio' => $this->isAudio($itemRel),
            ];
        }

        // Sort folders A-Z, files A-Z
        usort($foldersList, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        usort($filesList, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return array_merge($foldersList, $filesList);
    }

    /**
     * Upload file into specified directory in public/.
     */
    public function upload(UploadedFile $file, string $dir = ''): array
    {
        $clientOriginalName = $file->getClientOriginalName();
        $this->validateExtension($clientOriginalName);

        $cleanDir = $this->sanitizePath($dir);
        $targetDirFull = $this->publicPath($cleanDir);

        if (!File::exists($targetDirFull)) {
            File::makeDirectory($targetDirFull, 0755, true);
        }

        $filename = pathinfo($clientOriginalName, PATHINFO_FILENAME);
        $ext = $file->getClientOriginalExtension();

        $sanitizedBase = $this->sanitizeName($filename);
        $sanitizedName = $ext ? "{$sanitizedBase}.{$ext}" : $sanitizedBase;

        $this->validateExtension($sanitizedName);

        // Handle filename duplicate collisions
        $finalName = $sanitizedName;
        $counter = 1;
        while (File::exists($targetDirFull . '/' . $finalName)) {
            $finalName = $ext ? "{$sanitizedBase}-{$counter}.{$ext}" : "{$sanitizedBase}-{$counter}";
            $counter++;
        }

        $file->move($targetDirFull, $finalName);

        $relPath = $cleanDir === '' ? $finalName : $cleanDir . '/' . $finalName;

        ActivityLogger::log('upload', [
            'path' => $relPath,
            'meta' => ['size' => File::size($targetDirFull . '/' . $finalName)],
        ]);

        return [
            'name' => $finalName,
            'path' => $relPath,
            'url' => $this->publicUrl($relPath),
            'size' => File::size($targetDirFull . '/' . $finalName),
        ];
    }

    /**
     * Create folder inside target directory.
     */
    public function createFolder(string $dir, string $name): array
    {
        $cleanDir = $this->sanitizePath($dir);
        $sanitizedName = $this->sanitizeName($name);

        $relPath = $cleanDir === '' ? $sanitizedName : $cleanDir . '/' . $sanitizedName;
        $fullPath = $this->publicPath($relPath);

        if (File::exists($fullPath)) {
            throw new InvalidPathException("Folder or file [{$sanitizedName}] already exists.");
        }

        File::makeDirectory($fullPath, 0755, true);

        ActivityLogger::log('folder_create', [
            'path' => $relPath,
        ]);

        return [
            'name' => $sanitizedName,
            'path' => $relPath,
        ];
    }

    /**
     * Rename file or folder.
     */
    public function rename(string $path, string $newName): array
    {
        $cleanPath = $this->sanitizePath($path);
        $fullPath = $this->publicPath($cleanPath);

        if (!File::exists($fullPath)) {
            throw new InvalidPathException("Item [{$cleanPath}] does not exist.");
        }

        $isDir = File::isDirectory($fullPath);
        $sanitizedNewName = $this->sanitizeName($newName);

        if (!$isDir) {
            // Keep original extension if new name doesn't specify one
            $origExt = pathinfo($cleanPath, PATHINFO_EXTENSION);
            $newExt = pathinfo($newName, PATHINFO_EXTENSION);
            if (empty($newExt) && !empty($origExt)) {
                $sanitizedNewName .= '.' . $origExt;
            }
            $this->validateExtension($sanitizedNewName);
        }

        $parentDir = dirname($cleanPath);
        $parentDir = ($parentDir === '.' || $parentDir === '/') ? '' : $parentDir;

        $targetRel = $parentDir === '' ? $sanitizedNewName : $parentDir . '/' . $sanitizedNewName;
        $targetFull = $this->publicPath($targetRel);

        if (File::exists($targetFull)) {
            throw new InvalidPathException("An item with name [{$sanitizedNewName}] already exists.");
        }

        File::move($fullPath, $targetFull);

        // Update thumbnails cache if image
        if (!$isDir && $this->isImage($targetRel)) {
            app(ThumbnailService::class)->clear($cleanPath);
        }

        ActivityLogger::log('rename', [
            'path' => $cleanPath,
            'target_path' => $targetRel,
        ]);

        return [
            'old_path' => $cleanPath,
            'new_path' => $targetRel,
            'name' => $sanitizedNewName,
            'url' => $this->publicUrl($targetRel),
        ];
    }

    /**
     * Move file or folder to destination directory.
     */
    public function move(string $src, string $destDir): array
    {
        $cleanSrc = $this->sanitizePath($src);
        $cleanDestDir = $this->sanitizePath($destDir);

        $fullSrc = $this->publicPath($cleanSrc);
        $fullDestDir = $this->publicPath($cleanDestDir);

        if (!File::exists($fullSrc)) {
            throw new InvalidPathException("Source item [{$cleanSrc}] does not exist.");
        }

        if (!File::exists($fullDestDir) || !File::isDirectory($fullDestDir)) {
            throw new InvalidPathException("Destination directory [{$cleanDestDir}] does not exist.");
        }

        $baseName = basename($fullSrc);
        $targetRel = $cleanDestDir === '' ? $baseName : $cleanDestDir . '/' . $baseName;
        $targetFull = $this->publicPath($targetRel);

        if (File::exists($targetFull)) {
            throw new InvalidPathException("Destination already contains an item named [{$baseName}].");
        }

        File::move($fullSrc, $targetFull);

        if ($this->isImage($targetRel)) {
            app(ThumbnailService::class)->clear($cleanSrc);
        }

        ActivityLogger::log('move', [
            'path' => $cleanSrc,
            'target_path' => $targetRel,
        ]);

        return [
            'old_path' => $cleanSrc,
            'new_path' => $targetRel,
        ];
    }

    /**
     * Delete file or folder (delegates to RecycleBinService).
     */
    public function delete(string $path): array
    {
        return app(RecycleBinService::class)->move($path);
    }

    public function publicUrl(string $rel): string
    {
        $cleanRel = $this->sanitizePath($rel);
        return url('/' . ltrim($cleanRel, '/'));
    }

    public function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function isImage(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'], true);
    }

    public function isPdf(string $path): bool
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function isVideo(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'], true);
    }

    public function isAudio(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a'], true);
    }
}
