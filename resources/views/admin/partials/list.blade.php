<div x-show="viewMode === 'list'" class="overflow-x-auto select-none">
    <table class="w-full text-left border-collapse text-sm">
        <thead>
            <tr class="border-b border-[var(--border)] text-xs text-[var(--text-secondary)] uppercase">
                <th class="py-2.5 px-3 w-10">
                    <input type="checkbox" @change="$event.target.checked ? selectAll() : clearSelection()" :checked="selected.length === filteredItems.length && filteredItems.length > 0" class="w-4 h-4 rounded text-[var(--accent)] border-[var(--border)] focus:ring-0 cursor-pointer">
                </th>
                <th class="py-2.5 px-3 font-semibold">Name</th>
                <th class="py-2.5 px-3 font-semibold">Date Modified</th>
                <th class="py-2.5 px-3 font-semibold">Type</th>
                <th class="py-2.5 px-3 font-semibold">Size</th>
                <th class="py-2.5 px-3 font-semibold text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
            <template x-for="(item, index) in filteredItems" :key="item.path">
                <tr 
                    @click="handleItemClick(item, index, $event)"
                    @dblclick="handleDoubleClick(item)"
                    @contextmenu="openContextMenu($event, item)"
                    :class="isSelected(item.path) ? 'bg-[#EFF6FC] dark:bg-[#1C2C3D]' : 'hover:bg-[var(--bg-hover)]'"
                    class="cursor-pointer transition-colors">
                    
                    <td class="py-2.5 px-3">
                        <input type="checkbox" :checked="isSelected(item.path)" class="w-4 h-4 rounded text-[var(--accent)] border-[var(--border)] focus:ring-0 cursor-pointer">
                    </td>

                    <td class="py-2.5 px-3 flex items-center space-x-3">
                        <!-- Icon -->
                        <div class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                            <template x-if="item.type === 'folder'">
                                <iconify-icon icon="solar:folder-bold" class="text-amber-400 text-xl"></iconify-icon>
                            </template>
                            <template x-if="item.type === 'file' && item.is_image">
                                <iconify-icon icon="solar:gallery-bold" class="text-sky-500 text-xl"></iconify-icon>
                            </template>
                            <template x-if="item.type === 'file' && item.is_pdf">
                                <iconify-icon icon="solar:file-check-bold" class="text-rose-500 text-xl"></iconify-icon>
                            </template>
                            <template x-if="item.type === 'file' && item.is_video">
                                <iconify-icon icon="solar:videocamera-record-bold" class="text-purple-500 text-xl"></iconify-icon>
                            </template>
                            <template x-if="item.type === 'file' && item.is_audio">
                                <iconify-icon icon="solar:music-note-bold" class="text-emerald-500 text-xl"></iconify-icon>
                            </template>
                            <template x-if="item.type === 'file' && !item.is_image && !item.is_pdf && !item.is_video && !item.is_audio">
                                <iconify-icon icon="solar:document-bold" class="text-slate-400 text-xl"></iconify-icon>
                            </template>
                        </div>
                        <span class="font-medium text-[var(--text-primary)] truncate max-w-xs sm:max-w-md" x-text="item.name"></span>
                    </td>

                    <td class="py-2.5 px-3 text-xs text-[var(--text-secondary)] whitespace-nowrap" x-text="new Date(item.mtime * 1000).toLocaleString()"></td>

                    <td class="py-2.5 px-3 text-xs text-[var(--text-secondary)] capitalize" x-text="item.type === 'folder' ? 'Folder' : (item.extension || 'File')"></td>

                    <td class="py-2.5 px-3 text-xs text-[var(--text-secondary)]" x-text="item.human_size"></td>

                    <td class="py-2.5 px-3 text-right">
                        <div class="flex items-center justify-end space-x-1" @click.stop>
                            <button @click="openShareModal(item)" class="p-1 text-[var(--text-secondary)] hover:text-[var(--accent)] rounded hover:bg-[var(--bg-hover)]" title="Share">
                                <iconify-icon icon="solar:share-bold" class="text-base"></iconify-icon>
                            </button>
                            <button @click="openRenameModal(item)" class="p-1 text-[var(--text-secondary)] hover:text-[var(--text-primary)] rounded hover:bg-[var(--bg-hover)]" title="Rename">
                                <iconify-icon icon="solar:pen-bold" class="text-base"></iconify-icon>
                            </button>
                            <button @click="openDeleteModal(item)" class="p-1 text-rose-500 hover:text-rose-700 rounded hover:bg-[var(--bg-hover)]" title="Delete">
                                <iconify-icon icon="solar:trash-bin-trash-bold" class="text-base"></iconify-icon>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
