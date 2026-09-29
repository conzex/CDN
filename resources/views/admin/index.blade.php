@extends('layouts.app')

@section('content')
<div x-data="fileManager" 
     @dragover.prevent="" 
     @drop.prevent="uploadFiles($event.dataTransfer.files)" 
     class="flex flex-col h-screen overflow-hidden select-none">
    
    @include('admin.partials.header')
    @include('admin.partials.toolbar')

    <!-- Main Content Grid / List Area -->
    <main class="flex-1 overflow-auto p-6 bg-[var(--bg-app)] relative">
        <!-- Empty State -->
        <div x-show="!loading && filteredItems.length === 0" class="flex flex-col items-center justify-center h-64 text-center">
            <iconify-icon icon="solar:folder-open-bold" class="text-6xl text-[var(--text-secondary)] opacity-40 mb-3"></iconify-icon>
            <h3 class="text-base font-semibold text-[var(--text-primary)]">This folder is empty</h3>
            <p class="text-xs text-[var(--text-secondary)] mt-1">Drag and drop files here to upload or click "New"</p>
        </div>

        <!-- Loading Spinner -->
        <div x-show="loading" class="flex items-center justify-center h-64">
            <iconify-icon icon="solar:restart-bold" class="text-4xl text-[var(--accent)] animate-spin"></iconify-icon>
        </div>

        <div x-show="!loading && filteredItems.length > 0">
            @include('admin.partials.grid')
            @include('admin.partials.list')
        </div>
    </main>

    @include('admin.partials.modals')
</div>
@endsection
