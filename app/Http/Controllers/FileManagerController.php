<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use App\Services\ThumbnailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use ZipStream\ZipStream;

class FileManagerController extends Controller
{
    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        return view('admin.index');
    }

    public function list(Request $request)
    {
        $path = $request->query('path', '');
        $cleanPath = $this->fileService->sanitizePath($path);
        $items = $this->fileService->list($cleanPath);

        // Build breadcrumbs
        $breadcrumbs = [];
        $breadcrumbs[] = ['name' => 'Home', 'path' => ''];

        if ($cleanPath !== '') {
            $parts = explode('/', $cleanPath);
            $accumulated = '';
            foreach ($parts as $part) {
                $accumulated = $accumulated === '' ? $part : $accumulated . '/' . $part;
                $breadcrumbs[] = [
                    'name' => $part,
                    'path' => $accumulated,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'current_path' => $cleanPath,
            'breadcrumbs' => $breadcrumbs,
            'items' => $items,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'path' => 'nullable|string',
            'files' => 'required',
            'files.*' => 'file',
        ]);

        $dir = (string) ($request->input('path') ?? '');
        $uploaded = [];

        $files = $request->file('files');
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $uploaded[] = $this->fileService->upload($file, $dir);
        }

        return response()->json([
            'status' => 'success',
            'message' => count($uploaded) . ' file(s) uploaded successfully.',
            'data' => $uploaded,
        ]);
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'path' => 'nullable|string',
            'name' => 'required|string|max:255',
        ]);

        $folder = $this->fileService->createFolder(
            (string) ($request->input('path') ?? ''),
            (string) $request->input('name')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Folder created successfully.',
            'data' => $folder,
        ]);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        $result = $this->fileService->rename(
            $request->input('path'),
            $request->input('new_name')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Renamed successfully.',
            'data' => $result,
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $result = $this->fileService->delete($request->input('path'));

        return response()->json([
            'status' => 'success',
            'message' => 'Item moved to Recycle Bin.',
            'data' => $result,
        ]);
    }

    public function move(Request $request)
    {
        $request->validate([
            'src' => 'required|string',
            'dest_dir' => 'nullable|string',
        ]);

        $result = $this->fileService->move(
            $request->input('src'),
            $request->input('dest_dir', '')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Item moved successfully.',
            'data' => $result,
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $relPath = $this->fileService->sanitizePath($request->input('path'));
        $fullPath = $this->fileService->publicPath($relPath);

        if (!File::exists($fullPath) || File::isDirectory($fullPath)) {
            return response()->json(['status' => 'error', 'message' => 'File not found.'], 404);
        }

        $mime = File::mimeType($fullPath) ?: 'application/octet-stream';
        $size = File::size($fullPath);
        $isImg = $this->fileService->isImage($relPath);
        $isPdf = $this->fileService->isPdf($relPath);
        $isVideo = $this->fileService->isVideo($relPath);
        $isAudio = $this->fileService->isAudio($relPath);

        $textTypes = ['text/plain', 'text/html', 'text/css', 'application/json', 'application/xml', 'text/markdown', 'text/csv', 'text/x-php', 'text/javascript'];
        $isText = in_array($mime, $textTypes) || str_starts_with($mime, 'text/');

        $content = null;
        if ($isText && $size <= 500000) { // limit 500KB text preview
            $content = File::get($fullPath);
        }

        return response()->json([
            'status' => 'success',
            'name' => basename($fullPath),
            'path' => $relPath,
            'url' => $this->fileService->publicUrl($relPath),
            'mime' => $mime,
            'size' => $size,
            'human_size' => $this->fileService->humanSize($size),
            'is_image' => $isImg,
            'is_pdf' => $isPdf,
            'is_video' => $isVideo,
            'is_audio' => $isAudio,
            'is_text' => $isText,
            'content' => $content,
        ]);
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        $user = Auth::user();
        $user->theme = $request->input('theme');
        $user->save();

        return response()->json([
            'status' => 'success',
            'theme' => $user->theme,
        ]);
    }

    public function clearThumbnails(Request $request)
    {
        app(ThumbnailService::class)->clearAll();

        return response()->json([
            'status' => 'success',
            'message' => 'Thumbnail cache cleared successfully.',
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
        ]);

        $results = [];
        foreach ($request->input('paths') as $p) {
            $results[] = $this->fileService->delete($p);
        }

        return response()->json([
            'status' => 'success',
            'message' => count($results) . ' items moved to Recycle Bin.',
            'data' => $results,
        ]);
    }

    public function bulkMove(Request $request)
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
            'dest_dir' => 'nullable|string',
        ]);

        $destDir = $request->input('dest_dir', '');
        $results = [];
        foreach ($request->input('paths') as $p) {
            $results[] = $this->fileService->move($p, $destDir);
        }

        return response()->json([
            'status' => 'success',
            'message' => count($results) . ' items moved successfully.',
            'data' => $results,
        ]);
    }

    public function bulkDownload(Request $request)
    {
        $request->validate([
            'paths' => 'required|array',
            'paths.*' => 'string',
        ]);

        $paths = $request->input('paths');
        $zipName = 'download-' . date('Ymd-His') . '.zip';

        return response()->streamDownload(function () use ($paths) {
            $zip = new ZipStream(
                outputName: 'download.zip',
                sendHttpHeaders: true
            );

            foreach ($paths as $relPath) {
                try {
                    $cleanRel = $this->fileService->sanitizePath($relPath);
                    $fullPath = $this->fileService->publicPath($cleanRel);

                    if (File::isDirectory($fullPath)) {
                        $files = File::allFiles($fullPath);
                        foreach ($files as $f) {
                            $fRel = str_replace(public_path() . '/', '', $f->getPathname());
                            $zip->addFileFromPath($fRel, $f->getPathname());
                        }
                    } elseif (File::exists($fullPath)) {
                        $zip->addFileFromPath(basename($fullPath), $fullPath);
                    }
                } catch (\Throwable $e) {
                    // Skip invalid or missing paths safely
                }
            }

            $zip->finish();
        }, $zipName);
    }
}
