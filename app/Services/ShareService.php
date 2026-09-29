<?php

namespace App\Services;

use App\Models\Share;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShareService
{
    public function create(string $path, array $options = []): Share
    {
        $fileService = app(FileService::class);
        $cleanPath = $fileService->sanitizePath($path);
        $fullPath = $fileService->publicPath($cleanPath);

        $isDir = is_dir($fullPath);
        $type = $isDir ? 'folder' : 'file';

        $expiresAt = null;
        if (!empty($options['expires_in']) && $options['expires_in'] !== 'never') {
            $expiresAt = match ($options['expires_in']) {
                '1h' => now()->addHour(),
                '24h' => now()->addDay(),
                '7d' => now()->addDays(7),
                '30d' => now()->addDays(30),
                default => null,
            };
        }

        $passwordHash = !empty($options['password']) ? Hash::make($options['password']) : null;
        $maxDownloads = !empty($options['max_downloads']) ? (int)$options['max_downloads'] : null;

        $token = Str::random(32);

        $share = Share::create([
            'token' => $token,
            'path' => $cleanPath,
            'type' => $type,
            'password_hash' => $passwordHash,
            'expires_at' => $expiresAt,
            'max_downloads' => $maxDownloads,
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log('share_create', [
            'path' => $cleanPath,
            'meta' => ['token' => $token, 'expires_at' => $expiresAt?->toIso8601String()],
        ]);

        return $share;
    }

    public function revoke(string $token): bool
    {
        $share = Share::where('token', $token)->first();
        if ($share) {
            ActivityLogger::log('share_revoke', [
                'path' => $share->path,
                'meta' => ['token' => $token],
            ]);
            return $share->delete();
        }
        return false;
    }

    public function listForPath(string $path)
    {
        $fileService = app(FileService::class);
        $cleanPath = $fileService->sanitizePath($path);
        return Share::where('path', $cleanPath)->get();
    }
}
