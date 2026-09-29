<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Services\ActivityLogger;
use App\Services\FileService;
use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use ZipStream\ZipStream;

class ShareController extends Controller
{
    protected ShareService $shareService;
    protected FileService $fileService;

    public function __construct(ShareService $shareService, FileService $fileService)
    {
        $this->shareService = $shareService;
        $this->fileService = $fileService;
    }

    public function create(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'expires_in' => 'nullable|string|in:1h,24h,7d,30d,never',
            'password' => 'nullable|string|max:100',
            'max_downloads' => 'nullable|integer|min:1',
        ]);

        $share = $this->shareService->create($request->input('path'), $request->only(['expires_in', 'password', 'max_downloads']));

        return response()->json([
            'status' => 'success',
            'message' => 'Share link created.',
            'share' => $share,
            'url' => $share->publicUrl(),
        ]);
    }

    public function revoke(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        $this->shareService->revoke($request->input('token'));

        return response()->json([
            'status' => 'success',
            'message' => 'Share link revoked.',
        ]);
    }

    public function list(Request $request)
    {
        $path = $request->query('path', '');
        $shares = $this->shareService->listForPath($path);

        return response()->json([
            'status' => 'success',
            'shares' => $shares,
        ]);
    }

    public function show(Request $request, string $token)
    {
        $share = Share::where('token', $token)->first();

        if (!$share) {
            abort(404, 'Share link not found.');
        }

        if (!$share->isActive()) {
            return response()->view('share.show', [
                'share' => $share,
                'status' => $share->isExpired() ? 'expired' : 'exhausted',
            ], 410)->header('X-Robots-Tag', 'noindex');
        }

        if ($share->isPasswordProtected() && !session("share_verified_{$token}")) {
            return response()->view('share.password', [
                'share' => $share,
            ])->header('X-Robots-Tag', 'noindex');
        }

        $fullPath = $this->fileService->publicPath($share->path);

        if (!File::exists($fullPath)) {
            abort(404, 'Shared file no longer exists.');
        }

        $isDir = File::isDirectory($fullPath);
        $contents = [];

        if ($isDir) {
            $subPath = $request->query('sub', '');
            $targetSub = $subPath ? $share->path . '/' . $this->fileService->sanitizePath($subPath) : $share->path;
            
            // Prevent navigating above the shared folder
            if (!str_starts_with($targetSub, $share->path)) {
                $targetSub = $share->path;
            }

            $contents = $this->fileService->list($targetSub);
        }

        return response()->view('share.show', [
            'share' => $share,
            'status' => 'active',
            'is_dir' => $isDir,
            'contents' => $contents,
            'public_url' => $this->fileService->publicUrl($share->path),
            'filename' => basename($fullPath),
            'size' => $isDir ? 0 : File::size($fullPath),
            'human_size' => $isDir ? '--' : $this->fileService->humanSize(File::size($fullPath)),
        ])->header('X-Robots-Tag', 'noindex');
    }

    public function verify(Request $request, string $token)
    {
        $share = Share::where('token', $token)->firstOrFail();

        $request->validate([
            'password' => 'required|string',
        ]);

        if (Hash::check($request->input('password'), $share->password_hash)) {
            session(["share_verified_{$token}" => true]);
            return redirect("/s/{$token}");
        }

        return back()->withErrors(['password' => 'Incorrect password provided.']);
    }

    public function download(Request $request, string $token)
    {
        $share = Share::where('token', $token)->firstOrFail();

        if (!$share->isActive()) {
            abort(410, 'Share link is expired or limit reached.');
        }

        if ($share->isPasswordProtected() && !session("share_verified_{$token}")) {
            return redirect("/s/{$token}");
        }

        $fullPath = $this->fileService->publicPath($share->path);

        if (!File::exists($fullPath)) {
            abort(404, 'File not found on disk.');
        }

        $share->increment('download_count');

        ActivityLogger::log('share_download', [
            'path' => $share->path,
            'meta' => ['token' => $token, 'download_count' => $share->download_count + 1],
        ]);

        if (File::isDirectory($fullPath)) {
            $zipName = basename($fullPath) . '.zip';
            return response()->streamDownload(function () use ($fullPath) {
                $zip = new ZipStream(outputName: 'folder.zip', sendHttpHeaders: true);
                $files = File::allFiles($fullPath);
                foreach ($files as $f) {
                    $rel = str_replace($fullPath . '/', '', $f->getPathname());
                    $zip->addFileFromPath($rel, $f->getPathname());
                }
                $zip->finish();
            }, $zipName)->header('X-Robots-Tag', 'noindex');
        }

        return response()->download($fullPath, basename($fullPath), [
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
