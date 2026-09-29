@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[var(--bg-app)] flex flex-col items-center justify-center p-4">
    @if(isset($status) && in_array($status, ['expired', 'exhausted']))
        <div class="w-full max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-lg p-8 text-center">
            <iconify-icon icon="solar:hourglass-bold" class="text-6xl text-amber-500 mb-4"></iconify-icon>
            <h2 class="text-xl font-bold text-[var(--text-primary)] mb-2">Share Link Expired</h2>
            <p class="text-sm text-[var(--text-secondary)]">
                {{ $status === 'expired' ? 'This link has reached its expiration time and is no longer active.' : 'This share link has reached its maximum download limit.' }}
            </p>
        </div>
    @else
        <div class="w-full max-w-2xl bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-[var(--border)] flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <img src="https://cdn.conzex.com/bg/dc.jpg" alt="Logo" class="w-10 h-10 rounded-lg object-cover border border-[var(--border)]">
                    <div>
                        <h1 class="text-lg font-bold text-[var(--text-primary)]">{{ $filename ?? 'Shared File' }}</h1>
                        <p class="text-xs text-[var(--text-secondary)]">{{ $human_size ?? '--' }}</p>
                    </div>
                </div>

                <a href="/s/{{ $share->token }}/download" class="flex items-center space-x-2 px-4 py-2 bg-[var(--accent)] text-white text-sm font-semibold rounded-md hover:bg-[var(--accent-hover)] transition-colors shadow">
                    <iconify-icon icon="solar:download-bold" class="text-lg"></iconify-icon>
                    <span>Download</span>
                </a>
            </div>

            <!-- Content -->
            <div class="p-6">
                @if(isset($is_dir) && $is_dir)
                    <h3 class="text-sm font-semibold text-[var(--text-primary)] mb-3">Folder Contents</h3>
                    <div class="space-y-2">
                        @foreach($contents as $item)
                        <div class="flex items-center justify-between p-3 rounded bg-[var(--bg-app)] border border-[var(--border)]">
                            <div class="flex items-center space-x-3">
                                <iconify-icon icon="{{ $item['type'] === 'folder' ? 'solar:folder-bold' : 'solar:document-bold' }}" class="text-xl {{ $item['type'] === 'folder' ? 'text-amber-400' : 'text-slate-400' }}"></iconify-icon>
                                <span class="text-sm font-medium text-[var(--text-primary)]">{{ $item['name'] }}</span>
                            </div>
                            <span class="text-xs text-[var(--text-secondary)]">{{ $item['human_size'] }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center p-8 bg-[var(--bg-app)] rounded-lg border border-[var(--border)] text-center">
                        <iconify-icon icon="solar:document-bold" class="text-6xl text-[var(--accent)] mb-3"></iconify-icon>
                        <p class="text-sm font-medium text-[var(--text-primary)]">Ready for download</p>
                        <p class="text-xs text-[var(--text-secondary)] mt-1">Direct URL: <a href="{{ $public_url }}" target="_blank" class="text-[var(--accent)] underline">{{ $public_url }}</a></p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
