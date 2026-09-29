<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\ThumbnailController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return Auth::check() ? redirect()->route('admin.index') : redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/_thumb/{size}/{path}', [ThumbnailController::class, 'show'])
    ->where('path', '.*')
    ->name('thumb');

Route::get('/s/{token}', [ShareController::class, 'show'])->name('share.show');
Route::post('/s/{token}/verify', [ShareController::class, 'verify'])->middleware('throttle:10,1')->name('share.verify');
Route::get('/s/{token}/download', [ShareController::class, 'download'])->name('share.download');

// Authenticated admin routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [FileManagerController::class, 'index'])->name('index');
    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity');

    // API endpoints
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/list', [FileManagerController::class, 'list'])->name('list');
        Route::post('/upload', [FileManagerController::class, 'upload'])->name('upload');
        Route::post('/folder', [FileManagerController::class, 'createFolder'])->name('folder');
        Route::post('/rename', [FileManagerController::class, 'rename'])->name('rename');
        Route::post('/delete', [FileManagerController::class, 'delete'])->name('delete');
        Route::post('/move', [FileManagerController::class, 'move'])->name('move');
        Route::get('/preview', [FileManagerController::class, 'preview'])->name('preview');

        Route::post('/preferences/theme', [FileManagerController::class, 'updateTheme'])->name('theme');
        Route::post('/thumbnails/clear', [FileManagerController::class, 'clearThumbnails'])->name('thumbnails.clear');

        // Bulk actions
        Route::post('/bulk/delete', [FileManagerController::class, 'bulkDelete'])->name('bulk.delete');
        Route::post('/bulk/move', [FileManagerController::class, 'bulkMove'])->name('bulk.move');
        Route::post('/bulk/download', [FileManagerController::class, 'bulkDownload'])->name('bulk.download');

        // Recycle bin actions
        Route::post('/recycle/restore', [RecycleBinController::class, 'restore'])->name('recycle.restore');
        Route::post('/recycle/purge', [RecycleBinController::class, 'purge'])->name('recycle.purge');
        Route::post('/recycle/empty', [RecycleBinController::class, 'empty'])->name('recycle.empty');

        // Share actions
        Route::post('/share/create', [ShareController::class, 'create'])->name('share.create');
        Route::post('/share/revoke', [ShareController::class, 'revoke'])->name('share.revoke');
        Route::get('/share/list', [ShareController::class, 'list'])->name('share.list');

        // Activity API
        Route::get('/activity', [ActivityController::class, 'api'])->name('activity');
    });
});
