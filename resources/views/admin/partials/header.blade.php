<header class="h-[60px] border-b border-[var(--border)] bg-[var(--bg-surface)] px-6 flex items-center justify-between shadow-sm select-none">
    <!-- Left: Logo & Title -->
    <div class="flex items-center space-x-3">
        <a href="/admin" class="flex items-center space-x-3 group">
            <img src="https://cdn.conzex.com/bg/dc.jpg" alt="CDN Logo" class="w-10 h-10 rounded-lg object-cover border border-[var(--border)] group-hover:scale-105 transition-transform">
            <span class="font-semibold text-lg tracking-tight text-[var(--text-primary)]">CDN Manager</span>
        </a>
    </div>

    <!-- Center: Search Input -->
    <div class="flex-1 max-w-md mx-6">
        <div class="relative">
            <iconify-icon icon="solar:magnifer-bold" class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] text-lg"></iconify-icon>
            <input type="text" x-model="searchQuery" placeholder="Search files and folders..." class="w-full pl-10 pr-4 py-2 text-sm bg-[var(--bg-app)] text-[var(--text-primary)] border border-[var(--border)] rounded-md focus:outline-none focus:border-[var(--accent)] transition-colors">
            <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                <iconify-icon icon="solar:close-circle-bold" class="text-base"></iconify-icon>
            </button>
        </div>
    </div>

    <!-- Right: Actions & Profile -->
    <div class="flex items-center space-x-2">
        <!-- View Toggle -->
        <div class="flex items-center bg-[var(--bg-app)] border border-[var(--border)] rounded-md p-0.5">
            <button @click="toggleViewMode('grid')" :class="viewMode === 'grid' ? 'bg-[var(--bg-surface)] shadow-sm text-[var(--accent)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'" class="p-1.5 rounded transition-colors" title="Grid View">
                <iconify-icon icon="solar:widget-bold" class="text-lg"></iconify-icon>
            </button>
            <button @click="toggleViewMode('list')" :class="viewMode === 'list' ? 'bg-[var(--bg-surface)] shadow-sm text-[var(--accent)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'" class="p-1.5 rounded transition-colors" title="List View">
                <iconify-icon icon="solar:list-bold" class="text-lg"></iconify-icon>
            </button>
        </div>

        <!-- Theme Toggle -->
        <button @click="toggleTheme()" class="p-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)] rounded-md hover:bg-[var(--bg-hover)] transition-colors" title="Toggle Light/Dark Theme">
            <template x-if="theme === 'dark'">
                <iconify-icon icon="solar:sun-bold" class="text-xl text-amber-400"></iconify-icon>
            </template>
            <template x-if="theme !== 'dark'">
                <iconify-icon icon="solar:moon-bold" class="text-xl text-slate-700"></iconify-icon>
            </template>
        </button>

        <!-- User Dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.outside="open = false" class="flex items-center space-x-2 p-1.5 rounded-md hover:bg-[var(--bg-hover)] transition-colors">
                <div class="w-8 h-8 rounded-full bg-[var(--accent)] text-white flex items-center justify-center font-medium text-sm">
                    A
                </div>
                <iconify-icon icon="solar:alt-arrow-down-bold" class="text-xs text-[var(--text-secondary)]"></iconify-icon>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="absolute right-0 mt-2 w-48 bg-[var(--bg-surface)] border border-[var(--border)] rounded-md shadow-lg py-1 z-50">
                <div class="px-4 py-2 border-b border-[var(--border)]">
                    <p class="text-xs text-[var(--text-secondary)]">Signed in as</p>
                    <p class="text-sm font-semibold text-[var(--text-primary)] truncate">{{ auth()->user()->username ?? 'admin' }}</p>
                </div>

                <a href="/admin/activity" class="flex items-center space-x-2 px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition-colors">
                    <iconify-icon icon="solar:history-bold" class="text-base text-[var(--accent)]"></iconify-icon>
                    <span>Activity Log</span>
                </a>

                <a href="/admin/recycle-bin" class="flex items-center space-x-2 px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition-colors">
                    <iconify-icon icon="solar:trash-bin-trash-bold" class="text-base text-rose-500"></iconify-icon>
                    <span>Recycle Bin</span>
                </a>

                <button @click="clearThumbnailsCache()" class="w-full flex items-center space-x-2 px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition-colors text-left">
                    <iconify-icon icon="solar:restart-bold" class="text-base text-amber-500"></iconify-icon>
                    <span>Clear Thumb Cache</span>
                </button>

                <div class="border-t border-[var(--border)] my-1"></div>

                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-2 px-4 py-2 text-sm text-red-600 hover:bg-[var(--bg-hover)] transition-colors text-left">
                        <iconify-icon icon="solar:logout-2-bold" class="text-base"></iconify-icon>
                        <span>Sign out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
