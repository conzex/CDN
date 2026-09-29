<div x-show="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4 select-none">
    <template x-for="(item, index) in filteredItems" :key="item.path">
        <div 
            @click="handleItemClick(item, index, $event)"
            @dblclick="handleDoubleClick(item)"
            @contextmenu="openContextMenu($event, item)"
            :class="isSelected(item.path) ? 'border-2 border-[var(--accent)] bg-[#EFF6FC] dark:bg-[#1C2C3D] shadow-md' : 'border border-[var(--border)] bg-[var(--bg-surface)] hover:bg-[var(--bg-hover)] shadow-xs'"
            class="group relative rounded-lg overflow-hidden flex flex-col justify-between transition-all duration-150 p-2 cursor-pointer h-40">
            
            <!-- Checkbox Top-Left -->
            <div class="absolute top-2 left-2 z-10" :class="isSelected(item.path) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 transition-opacity'">
                <input type="checkbox" :checked="isSelected(item.path)" class="w-4 h-4 rounded text-[var(--accent)] border-[var(--border)] focus:ring-0 cursor-pointer">
            </div>

            <!-- Thumbnail / Icon Area -->
            <div class="flex-1 flex items-center justify-center overflow-hidden rounded relative">
                <!-- Image Thumbnail -->
                <template x-if="item.is_image && item.thumb">
                    <img :src="item.thumb" :alt="item.name" class="w-full h-full object-cover rounded" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                </template>

                <!-- Icon Fallbacks -->
                <div class="flex items-center justify-center w-full h-full" :style="(item.is_image && item.thumb) ? 'display:none;' : ''">
                    <template x-if="item.type === 'folder'">
                        <iconify-icon icon="solar:folder-bold" class="text-amber-400 text-5xl drop-shadow-sm"></iconify-icon>
                    </template>
                    <template x-if="item.type === 'file' && item.is_image">
                        <iconify-icon icon="solar:gallery-bold" class="text-sky-500 text-5xl"></iconify-icon>
                    </template>
                    <template x-if="item.type === 'file' && item.is_pdf">
                        <iconify-icon icon="solar:file-check-bold" class="text-rose-500 text-5xl"></iconify-icon>
                    </template>
                    <template x-if="item.type === 'file' && item.is_video">
                        <iconify-icon icon="solar:videocamera-record-bold" class="text-purple-500 text-5xl"></iconify-icon>
                    </template>
                    <template x-if="item.type === 'file' && item.is_audio">
                        <iconify-icon icon="solar:music-note-bold" class="text-emerald-500 text-5xl"></iconify-icon>
                    </template>
                    <template x-if="item.type === 'file' && !item.is_image && !item.is_pdf && !item.is_video && !item.is_audio">
                        <iconify-icon icon="solar:document-bold" class="text-slate-400 text-5xl"></iconify-icon>
                    </template>
                </div>
            </div>

            <!-- Info Bar -->
            <div class="mt-2 text-center">
                <p class="text-xs font-medium text-[var(--text-primary)] truncate w-full" :title="item.name" x-text="item.name"></p>
                <p class="text-[10px] text-[var(--text-secondary)] mt-0.5" x-text="item.type === 'folder' ? 'Folder' : item.human_size"></p>
            </div>
        </div>
    </template>
</div>
