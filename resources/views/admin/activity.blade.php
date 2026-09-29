@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[var(--bg-app)]">
    <!-- Header -->
    <header class="h-[60px] border-b border-[var(--border)] bg-[var(--bg-surface)] px-6 flex items-center justify-between shadow-sm">
        <div class="flex items-center space-x-3">
            <a href="/admin" class="flex items-center space-x-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                <iconify-icon icon="solar:alt-arrow-left-bold" class="text-xl"></iconify-icon>
                <span class="text-sm font-medium">Back to Files</span>
            </a>
            <span class="text-[var(--border)]">|</span>
            <div class="flex items-center space-x-2">
                <img src="https://cdn.conzex.com/bg/dc.jpg" alt="Logo" class="w-8 h-8 rounded object-cover border border-[var(--border)]">
                <h1 class="text-lg font-semibold text-[var(--text-primary)]">Activity Log</h1>
            </div>
        </div>
    </header>

    <main class="p-6">
        <!-- Filter Bar -->
        <form method="GET" action="/admin/activity" class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg p-4 mb-6 shadow-xs flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs text-[var(--text-secondary)] mb-1">Action</label>
                <select name="action" class="px-3 py-1.5 text-xs bg-[var(--bg-app)] text-[var(--text-primary)] border border-[var(--border)] rounded focus:outline-none">
                    <option value="">All Actions</option>
                    <option value="login" {{ request('action') == 'login' ? 'selected' : '' }}>Login</option>
                    <option value="login_failed" {{ request('action') == 'login_failed' ? 'selected' : '' }}>Login Failed</option>
                    <option value="logout" {{ request('action') == 'logout' ? 'selected' : '' }}>Logout</option>
                    <option value="upload" {{ request('action') == 'upload' ? 'selected' : '' }}>Upload</option>
                    <option value="folder_create" {{ request('action') == 'folder_create' ? 'selected' : '' }}>Create Folder</option>
                    <option value="rename" {{ request('action') == 'rename' ? 'selected' : '' }}>Rename</option>
                    <option value="move" {{ request('action') == 'move' ? 'selected' : '' }}>Move</option>
                    <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                    <option value="restore" {{ request('action') == 'restore' ? 'selected' : '' }}>Restore</option>
                    <option value="purge" {{ request('action') == 'purge' ? 'selected' : '' }}>Purge</option>
                    <option value="share_create" {{ request('action') == 'share_create' ? 'selected' : '' }}>Create Share</option>
                    <option value="share_download" {{ request('action') == 'share_download' ? 'selected' : '' }}>Share Download</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-[var(--text-secondary)] mb-1">Path</label>
                <input type="text" name="path" value="{{ request('path') }}" placeholder="Filter by path..." class="px-3 py-1.5 text-xs bg-[var(--bg-app)] text-[var(--text-primary)] border border-[var(--border)] rounded focus:outline-none">
            </div>

            <div>
                <label class="block text-xs text-[var(--text-secondary)] mb-1">From Date</label>
                <input type="date" name="from" value="{{ request('from') }}" class="px-3 py-1.5 text-xs bg-[var(--bg-app)] text-[var(--text-primary)] border border-[var(--border)] rounded focus:outline-none">
            </div>

            <div>
                <label class="block text-xs text-[var(--text-secondary)] mb-1">To Date</label>
                <input type="date" name="to" value="{{ request('to') }}" class="px-3 py-1.5 text-xs bg-[var(--bg-app)] text-[var(--text-primary)] border border-[var(--border)] rounded focus:outline-none">
            </div>

            <div class="flex items-end space-x-2 pt-4">
                <button type="submit" class="px-4 py-1.5 bg-[var(--accent)] text-white text-xs font-medium rounded hover:bg-[var(--accent-hover)] transition-colors">
                    Filter
                </button>
                <a href="/admin/activity" class="px-3 py-1.5 text-xs text-[var(--text-secondary)] hover:text-[var(--text-primary)]">Reset</a>
            </div>
        </form>

        <!-- Activities Table -->
        <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xs overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[var(--border)] text-xs text-[var(--text-secondary)] uppercase bg-[var(--bg-app)]">
                        <th class="py-3 px-4 font-semibold">Time</th>
                        <th class="py-3 px-4 font-semibold">Action</th>
                        <th class="py-3 px-4 font-semibold">Path / Details</th>
                        <th class="py-3 px-4 font-semibold">User</th>
                        <th class="py-3 px-4 font-semibold">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse($activities as $act)
                    <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                        <td class="py-3 px-4 text-xs text-[var(--text-secondary)] whitespace-nowrap">
                            {{ $act->created_at?->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $badgeClass = match($act->action) {
                                    'login' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                                    'login_failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                                    'upload' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
                                    'delete', 'purge' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
                                    'restore' => 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300',
                                    'share_create', 'share_download' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300',
                                    default => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                {{ $act->action }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-[var(--text-primary)]">
                            <div>{{ $act->path ?? '--' }}</div>
                            @if($act->target_path)
                                <div class="text-[10px] text-[var(--text-secondary)]">→ {{ $act->target_path }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-xs text-[var(--text-secondary)]">
                            {{ $act->user?->username ?? 'System / Guest' }}
                        </td>
                        <td class="py-3 px-4 text-xs text-[var(--text-secondary)] font-mono">
                            {{ $act->ip ?? '--' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-[var(--text-secondary)] text-xs">No activity records found matching filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 border-t border-[var(--border)]">
                {{ $activities->links() }}
            </div>
        </div>
    </main>
</div>
@endsection
