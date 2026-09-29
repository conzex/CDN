<?php

namespace App\Services;

use App\Exceptions\InvalidPathException;
use App\Models\RecycleBin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RecycleBinService
{
    /**
     * Move a file or directory from public/ to public/.trash/ and log in database.
     */
    public function move(string $relPath): array
    {
        $fileService = app(FileService::class);
        $cleanRel = $fileService->sanitizePath($relPath);
        $fullPath = $fileService->publicPath($cleanRel);

        if (!File::exists($fullPath)) {
            throw new InvalidPathException("File or directory [{$cleanRel}] does not exist.");
        }

        $baseName = basename($fullPath);
        $isDir = File::isDirectory($fullPath);
        $type = $isDir ? 'folder' : 'file';
        $size = $isDir ? 0 : File::size($fullPath);

        $trashDirName = date('Ymd_His') . '_' . Str::random(6);
        $trashRelDir = '.trash/' . $trashDirName;
        $trashFullDir = public_path($trashRelDir);

        if (!File::exists($trashFullDir)) {
            File::makeDirectory($trashFullDir, 0755, true);
        }

        $trashedRelPath = $trashRelDir . '/' . $baseName;
        $trashedFullPath = public_path($trashedRelPath);

        // Move to .trash directory
        File::move($fullPath, $trashedFullPath);

        $record = RecycleBin::create([
            'original_path' => $cleanRel,
            'trashed_path' => $trashedRelPath,
            'type' => $type,
            'size' => $size,
            'deleted_at' => now(),
            'deleted_by' => Auth::id(),
        ]);

        ActivityLogger::log('delete', [
            'path' => $cleanRel,
            'target_path' => $trashedRelPath,
            'meta' => ['size' => $size, 'type' => $type, 'recycle_bin_id' => $record->id],
        ]);

        return [
            'id' => $record->id,
            'original_path' => $cleanRel,
            'trashed_path' => $trashedRelPath,
            'type' => $type,
            'size' => $size,
        ];
    }

    /**
     * Restore item back to original path.
     */
    public function restore(int $id): array
    {
        $record = RecycleBin::findOrFail($id);
        $trashedFullPath = public_path($record->trashed_path);

        if (!File::exists($trashedFullPath)) {
            $record->delete();
            throw new InvalidPathException("Trashed item file missing on disk.");
        }

        $fileService = app(FileService::class);
        $targetRel = $record->original_path;
        $targetFull = public_path($targetRel);

        // Handle path collision if file already exists at original location
        if (File::exists($targetFull)) {
            $ext = pathinfo($targetRel, PATHINFO_EXTENSION);
            $filename = pathinfo($targetRel, PATHINFO_FILENAME);
            $dir = dirname($targetRel);
            $dir = ($dir === '.' || $dir === '/') ? '' : $dir;

            $suffix = '-restored-' . time();
            $newBase = $filename . $suffix . ($ext ? '.' . $ext : '');
            $targetRel = $dir ? ($dir . '/' . $newBase) : $newBase;
            $targetFull = public_path($targetRel);
        }

        // Ensure parent directory exists
        $targetParentDir = dirname($targetFull);
        if (!File::exists($targetParentDir)) {
            File::makeDirectory($targetParentDir, 0755, true);
        }

        File::move($trashedFullPath, $targetFull);

        // Clean up empty parent trash folder if needed
        $trashParentDir = dirname($trashedFullPath);
        if (File::isDirectory($trashParentDir) && count(File::allFiles($trashParentDir)) === 0 && count(File::directories($trashParentDir)) === 0) {
            File::deleteDirectory($trashParentDir);
        }

        $record->delete();

        ActivityLogger::log('restore', [
            'path' => $record->original_path,
            'target_path' => $targetRel,
        ]);

        return [
            'restored_path' => $targetRel,
        ];
    }

    /**
     * Permanently purge item from disk and database.
     */
    public function purge(int $id): bool
    {
        $record = RecycleBin::findOrFail($id);
        $trashedFullPath = public_path($record->trashed_path);

        if (File::exists($trashedFullPath)) {
            if (File::isDirectory($trashedFullPath)) {
                File::deleteDirectory($trashedFullPath);
            } else {
                File::delete($trashedFullPath);
            }
        }

        $trashParentDir = dirname($trashedFullPath);
        if (File::exists($trashParentDir) && File::isDirectory($trashParentDir)) {
            if (count(File::allFiles($trashParentDir)) === 0 && count(File::directories($trashParentDir)) === 0) {
                File::deleteDirectory($trashParentDir);
            }
        }

        ActivityLogger::log('purge', [
            'path' => $record->original_path,
            'target_path' => $record->trashed_path,
        ]);

        return $record->delete();
    }

    /**
     * Empty entire recycle bin.
     */
    public function emptyAll(): int
    {
        $items = RecycleBin::all();
        $count = 0;
        foreach ($items as $item) {
            $this->purge($item->id);
            $count++;
        }
        return $count;
    }

    /**
     * List all recycle bin entries.
     */
    public function list()
    {
        return RecycleBin::orderBy('deleted_at', 'desc')->get();
    }
}
