@extends('layouts.app')

@section('content')
<div x-data="{
    loading: false,
    async restoreItem(id) {
        if(!confirm('Restore this item to its original location?')) return;
        this.loading = true;
        try {
            const res = await fetch('/admin/api/recycle/restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } finally { this.loading = false; }
    },
    async purgeItem(id) {
        if(!confirm('Permanently delete this item? This action cannot be undone.')) return;
        this.loading = true;
        try {
            const res = await fetch('/admin/api/recycle/purge', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } finally { this.loading = false; }
    },
    async emptyBin() {
        if(!confirm('Empty entire recycle bin? ALL items will be permanently erased.')) return;
        this.loading = true;
        try {
            const res = await fetch('/admin/api/recycle/empty', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } finally { this.loading = false; }
    }
}" class="min-h-screen bg-[var(--bg-app)]">
    
    <header class="h-[60px] border-b border-[var(--border)] bg-[var(--bg-surface)] px-6 flex items-center justify-between shadow-sm">
        <div class="flex items-center space-x-3">
            <a href="/admin" class="flex items-center space-x-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                <iconify-icon icon="solar:alt-arrow-left-bold" class="text-xl"></iconify-icon>
                <span class="text-sm font-medium">Back to Files</span>
            </a>
            <span class="text-[var(--border)]">|</span>
            <div class="flex items-center space-x-2">
                <iconify-icon icon="solar:trash-bin-trash-bold" class="text-xl text-rose-500"></iconify-icon>
                <h1 class="text-lg font-semibold text-[var(--text-primary)]">Recycle Bin</h1>
            </div>
        </div>

        @if(count($items) > 0)
        <button @click="emptyBin()" class="flex items-center space-x-1.5 px-3 py-1.5 bg-rose-600 text-white text-xs font-medium rounded hover:bg-rose-700 transition-colors">
            <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="text-base"></iconify-icon>
            <span>Empty Recycle Bin</span>
        </button>
        @endif
    </header>

    <main class="p-6">
        @if(count($items) === 0)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <iconify-icon icon="solar:trash-bin-trash-bold" class="text-6xl text-[var(--text-secondary)] opacity-30 mb-3"></iconify-icon>
                <h3 class="text-base font-semibold text-[var(--text-primary)]">Recycle Bin is empty</h3>
                <p class="text-xs text-[var(--text-secondary)] mt-1">Deleted items will show up here before being permanently removed.</p>
            </div>
        @else
            <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[var(--border)] text-xs text-[var(--text-secondary)] uppercase bg-[var(--bg-app)]">
                            <th class="py-3 px-4 font-semibold">Original Path</th>
                            <th class="py-3 px-4 font-semibold">Type</th>
                            <th class="py-3 px-4 font-semibold">Size</th>
                            <th class="py-3 px-4 font-semibold">Deleted At</th>
                            <th class="py-3 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @foreach($items as $item)
                        <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                            <td class="py-3 px-4 font-medium text-[var(--text-primary)] truncate max-w-md">
                                {{ $item->original_path }}
                            </td>
                            <td class="py-3 px-4 text-xs text-[var(--text-secondary)] capitalize">
                                {{ $item->type }}
                            </td>
                            <td class="py-3 px-4 text-xs text-[var(--text-secondary)]">
                                {{ $item->type === 'folder' ? '--' : app(\App\Services\FileService::class)->humanSize($item->size) }}
                            </td>
                            <td class="py-3 px-4 text-xs text-[var(--text-secondary)]">
                                {{ $item->deleted_at?->format('M d, Y H:i') }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="restoreItem({{ $item->id }})" class="flex items-center space-x-1 px-2.5 py-1 text-xs bg-[var(--accent)] text-white rounded hover:bg-[var(--accent-hover)] transition-colors">
                                        <iconify-icon icon="solar:restart-bold" class="text-sm"></iconify-icon>
                                        <span>Restore</span>
                                    </button>
                                    <button @click="purgeItem({{ $item->id }})" class="flex items-center space-x-1 px-2.5 py-1 text-xs bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded hover:bg-rose-100 transition-colors">
                                        <iconify-icon icon="solar:trash-bin-trash-bold" class="text-sm"></iconify-icon>
                                        <span>Purge</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </main>
</div>
@endsection
