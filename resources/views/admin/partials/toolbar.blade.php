<div class="h-[48px] border-b border-[var(--border)] bg-[var(--bg-surface)] px-6 flex items-center justify-between shadow-xs select-none">
    <!-- Hidden File Upload Input -->
    <input type="file" id="global-file-input" multiple class="hidden" @change="uploadFiles($event.target.files)">

    <!-- NORMAL TOOLBAR (No selection) -->
    <div x-show="selected.length === 0" class="flex items-center justify-between w-full">
        <!-- Left: New button & Actions -->
        <div class="flex items-center space-x-3">
            <!-- New Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="flex items-center space-x-2 px-3 py-1.5 bg-[var(--accent)] text-white text-sm font-medium rounded-md hover:bg-[var(--accent-hover)] transition-colors">
                    <iconify-icon icon="solar:add-circle-bold" class="text-base"></iconify-icon>
                    <span>New</span>
                    <iconify-icon icon="solar:alt-arrow-down-bold" class="text-xs"></iconify-icon>
                </button>

                <div x-show="open" x-transition class="absolute left-0 mt-1 w-44 bg-[var(--bg-surface)] border border-[var(--border)] rounded-md shadow-lg py-1 z-50">
                    <button @click="open = false; document.getElementById('global-file-input').click()" class="w-full flex items-center space-x-2 px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] text-left">
                        <iconify-icon icon="solar:upload-minimalistic-bold" class="text-base text-[var(--accent)]"></iconify-icon>
                        <span>Upload Files</span>
                    </button>
                    <button @click="open = false; modals.newFolder = true" class="w-full flex items-center space-x-2 px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] text-left">
                        <iconify-icon icon="solar:folder-add-bold" class="text-base text-amber-500"></iconify-icon>
                        <span>New Folder</span>
                    </button>
                </div>
            </div>

            <!-- Refresh Button -->
            <button @click="fetchList(currentPath)" class="p-1.5 text-[var(--text-secondary)] hover:text-[var(--text-primary)] rounded-md hover:bg-[var(--bg-hover)] transition-colors" title="Refresh">
                <iconify-icon icon="solar:restart-bold" class="text-lg" :class="{ 'animate-spin': loading }"></iconify-icon>
            </button>

            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-1.5 text-sm text-[var(--text-secondary)] overflow-x-auto ml-2">
                <template x-for="(bc, index) in breadcrumbs" :key="bc.path">
                    <div class="flex items-center space-x-1.5">
                        <template x-if="index > 0">
                            <iconify-icon icon="solar:alt-arrow-right-bold" class="text-xs text-[var(--text-secondary)]"></iconify-icon>
                        </template>
                        <button @click="fetchList(bc.path)" :class="index === breadcrumbs.length - 1 ? 'font-semibold text-[var(--text-primary)] cursor-default' : 'hover:text-[var(--accent)] hover:underline'" class="transition-colors">
                            <span x-text="bc.name"></span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Right: Sort options -->
        <div class="flex items-center space-x-2">
            <span class="text-xs text-[var(--text-secondary)]">Sort:</span>
            <select x-model="sortBy" class="px-2 py-1 text-xs bg-[var(--bg-app)] text-[var(--text-primary)] border border-[var(--border)] rounded focus:outline-none">
                <option value="name">Name</option>
                <option value="date">Date Modified</option>
                <option value="size">Size</option>
            </select>
            <button @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'" class="p-1 text-[var(--text-secondary)] hover:text-[var(--text-primary)] rounded hover:bg-[var(--bg-hover)]" title="Toggle Sort Order">
                <iconify-icon :icon="sortOrder === 'asc' ? 'solar:sort-from-bottom-to-top-bold' : 'solar:sort-from-top-to-bottom-bold'" class="text-base"></iconify-icon>
            </button>
        </div>
    </div>

    <!-- SELECTION TOOLBAR (When items are selected) -->
    <div x-show="selected.length > 0" x-transition class="flex items-center justify-between w-full bg-[#EFF6FC] dark:bg-[#1C2C3D] -mx-6 px-6 h-full border-b border-[var(--accent)]">
        <div class="flex items-center space-x-4">
            <button @click="clearSelection()" class="p-1 text-[var(--text-secondary)] hover:text-[var(--text-primary)] rounded hover:bg-[var(--bg-hover)]" title="Clear selection">
                <iconify-icon icon="solar:close-circle-bold" class="text-xl"></iconify-icon>
            </button>
            <span class="text-sm font-medium text-[var(--accent)]" x-text="`${selected.length} selected`"></span>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Share (if 1 item selected) -->
            <button x-show="selected.length === 1" @click="openShareModal()" class="flex items-center space-x-1.5 px-3 py-1 text-xs font-medium bg-[var(--bg-surface)] border border-[var(--border)] rounded text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition-colors">
                <iconify-icon icon="solar:share-bold" class="text-sm text-[var(--accent)]"></iconify-icon>
                <span>Share</span>
            </button>

            <!-- Download -->
            <button @click="downloadSelected()" class="flex items-center space-x-1.5 px-3 py-1 text-xs font-medium bg-[var(--bg-surface)] border border-[var(--border)] rounded text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition-colors">
                <iconify-icon icon="solar:download-bold" class="text-sm text-emerald-600 dark:text-emerald-400"></iconify-icon>
                <span>Download</span>
            </button>

            <!-- Copy Links -->
            <button @click="copySelectedUrls()" class="flex items-center space-x-1.5 px-3 py-1 text-xs font-medium bg-[var(--bg-surface)] border border-[var(--border)] rounded text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition-colors">
                <iconify-icon icon="solar:link-bold" class="text-sm text-amber-500"></iconify-icon>
                <span>Copy Links</span>
            </button>

            <!-- Move -->
            <button @click="openMoveModal()" class="flex items-center space-x-1.5 px-3 py-1 text-xs font-medium bg-[var(--bg-surface)] border border-[var(--border)] rounded text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition-colors">
                <iconify-icon icon="solar:cursor-line-bold" class="text-sm text-indigo-500"></iconify-icon>
                <span>Move</span>
            </button>

            <!-- Delete -->
            <button @click="openDeleteModal()" class="flex items-center space-x-1.5 px-3 py-1 text-xs font-medium bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded hover:bg-rose-100 transition-colors">
                <iconify-icon icon="solar:trash-bin-trash-bold" class="text-sm"></iconify-icon>
                <span>Delete</span>
            </button>
        </div>
    </div>
</div>
