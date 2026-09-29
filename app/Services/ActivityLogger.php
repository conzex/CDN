<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Log an action to activity_log.
     *
     * @param string $action
     * @param array $ctx (path, target_path, meta, user_id, etc.)
     * @return void
     */
    public static function log(string $action, array $ctx = []): void
    {
        try {
            $userId = $ctx['user_id'] ?? Auth::id();
            $path = $ctx['path'] ?? null;
            $targetPath = $ctx['target_path'] ?? null;
            $meta = $ctx['meta'] ?? null;

            ActivityLog::create([
                'user_id' => $userId,
                'action' => $action,
                'path' => $path,
                'target_path' => $targetPath,
                'meta' => $meta,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ActivityLogger failed: ' . $e->getMessage(), [
                'action' => $action,
                'exception' => $e,
            ]);
        }
    }
}
