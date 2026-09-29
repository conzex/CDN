<?php

namespace App\Http\Controllers;

use App\Services\ThumbnailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ThumbnailController extends Controller
{
    protected ThumbnailService $thumbnailService;

    public function __construct(ThumbnailService $thumbnailService)
    {
        $this->thumbnailService = $thumbnailService;
    }

    public function show(Request $request, string $size, string $path)
    {
        // Path can be wildcard/passed via query or path param
        $thumbPath = $this->thumbnailService->get($path, $size);

        if (!$thumbPath || !File::exists($thumbPath)) {
            return response()->json(['status' => 'error', 'message' => 'Thumbnail not available.'], 404);
        }

        return response()->file($thumbPath, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
