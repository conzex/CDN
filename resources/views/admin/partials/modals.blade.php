<div>
    <!-- CONTEXT MENU -->
    <div 
        x-show="contextMenu.show" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        :style="`top: ${contextMenu.y}px; left: ${contextMenu.x}px;`"
        class="fixed w-48 bg-[var(--bg-surface)] border border-[var(--border)] rounded-md shadow-xl py-1 z-[100] select-none text-sm text-[var(--text-primary)]">
        
        <template x-if="contextMenu.item && contextMenu.item.type === 'folder'">
            <button @click="fetchList(contextMenu.item.path); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left">
                <iconify-icon icon="solar:folder-open-bold" class="text-base text-amber-500"></iconify-icon>
                <span>Open Folder</span>
            </button>
        </template>

        <template x-if="contextMenu.item && contextMenu.item.type === 'file'">
            <button @click="openPreview(contextMenu.item); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left">
                <iconify-icon icon="solar:eye-bold" class="text-base text-sky-500"></iconify-icon>
                <span>Preview</span>
            </button>
        </template>

        <button @click="openShareModal(contextMenu.item); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left">
            <iconify-icon icon="solar:share-bold" class="text-base text-[var(--accent)]"></iconify-icon>
            <span>Share Link</span>
        </button>

        <button @click="copySelectedUrls(); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left">
            <iconify-icon icon="solar:link-bold" class="text-base text-emerald-500"></iconify-icon>
            <span>Copy Direct CDN Link</span>
        </button>

        <button @click="downloadSelected(); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left">
            <iconify-icon icon="solar:download-bold" class="text-base text-indigo-500"></iconify-icon>
            <span>Download</span>
        </button>

        <div class="border-t border-[var(--border)] my-1"></div>

        <button @click="openRenameModal(contextMenu.item); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left">
            <iconify-icon icon="solar:pen-bold" class="text-base text-slate-500"></iconify-icon>
            <span>Rename</span>
        </button>

        <button @click="openMoveModal(contextMenu.item); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left">
            <iconify-icon icon="solar:cursor-line-bold" class="text-base text-purple-500"></iconify-icon>
            <span>Move To...</span>
        </button>

        <div class="border-t border-[var(--border)] my-1"></div>

        <button @click="openDeleteModal(contextMenu.item); contextMenu.show = false" class="w-full flex items-center space-x-2 px-3 py-1.5 hover:bg-[var(--bg-hover)] text-left text-rose-600">
            <iconify-icon icon="solar:trash-bin-trash-bold" class="text-base"></iconify-icon>
            <span>Delete</span>
        </button>
    </div>

    <!-- NEW FOLDER MODAL -->
    <div x-show="modals.newFolder" class="fixed inset-0 z-[110] bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 select-none">
        <div @click.outside="modals.newFolder = false" class="w-full max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-[var(--text-primary)]">New Folder</h3>
                <button @click="modals.newFolder = false" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                    <iconify-icon icon="solar:close-circle-bold" class="text-xl"></iconify-icon>
                </button>
            </div>
            <form @submit.prevent="createFolder()">
                <div class="mb-4">
                    <label class="block text-xs text-[var(--text-secondary)] mb-1">Folder Name</label>
                    <input type="text" x-model="newFolderName" placeholder="e.g. documents" required autofocus class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none focus:border-[var(--accent)] text-sm">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="modals.newFolder = false" class="px-4 py-2 text-sm text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-[var(--accent)] text-white rounded font-medium hover:bg-[var(--accent-hover)]">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- RENAME MODAL -->
    <div x-show="modals.rename" class="fixed inset-0 z-[110] bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 select-none">
        <div @click.outside="modals.rename = false" class="w-full max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-[var(--text-primary)]">Rename</h3>
                <button @click="modals.rename = false" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                    <iconify-icon icon="solar:close-circle-bold" class="text-xl"></iconify-icon>
                </button>
            </div>
            <form @submit.prevent="submitRename()">
                <div class="mb-4">
                    <label class="block text-xs text-[var(--text-secondary)] mb-1">New Name</label>
                    <input type="text" x-model="renameName" required autofocus class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none focus:border-[var(--accent)] text-sm">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="modals.rename = false" class="px-4 py-2 text-sm text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-[var(--accent)] text-white rounded font-medium hover:bg-[var(--accent-hover)]">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MOVE MODAL -->
    <div x-show="modals.move" class="fixed inset-0 z-[110] bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 select-none">
        <div @click.outside="modals.move = false" class="w-full max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-[var(--text-primary)]">Move Item(s)</h3>
                <button @click="modals.move = false" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                    <iconify-icon icon="solar:close-circle-bold" class="text-xl"></iconify-icon>
                </button>
            </div>
            <form @submit.prevent="submitMove()">
                <div class="mb-4">
                    <label class="block text-xs text-[var(--text-secondary)] mb-1">Target Folder Path (Leave empty for root public/)</label>
                    <input type="text" x-model="moveDestDir" placeholder="e.g. bg or docs/2026" class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none focus:border-[var(--accent)] text-sm">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="modals.move = false" class="px-4 py-2 text-sm text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-[var(--accent)] text-white rounded font-medium hover:bg-[var(--accent-hover)]">Move</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div x-show="modals.delete" class="fixed inset-0 z-[110] bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 select-none">
        <div @click.outside="modals.delete = false" class="w-full max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xl p-6">
            <div class="flex items-center space-x-3 mb-4 text-rose-500">
                <iconify-icon icon="solar:danger-triangle-bold" class="text-3xl"></iconify-icon>
                <h3 class="text-lg font-semibold text-[var(--text-primary)]">Move to Recycle Bin?</h3>
            </div>
            <p class="text-sm text-[var(--text-secondary)] mb-6">
                Are you sure you want to move <span class="font-semibold text-[var(--text-primary)]" x-text="selected.length > 1 ? `${selected.length} items` : (activeItem ? activeItem.name : 'this item')"></span> to the Recycle Bin? You can restore items later.
            </p>
            <div class="flex justify-end space-x-2">
                <button type="button" @click="modals.delete = false" class="px-4 py-2 text-sm text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] rounded">Cancel</button>
                <button type="button" @click="confirmDelete()" class="px-4 py-2 text-sm bg-rose-600 text-white rounded font-medium hover:bg-rose-700">Delete</button>
            </div>
        </div>
    </div>

    <!-- SHARE LINK MODAL -->
    <div x-show="modals.share" class="fixed inset-0 z-[110] bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 select-none">
        <div @click.outside="modals.share = false" class="w-full max-w-lg bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-xl p-6">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center space-x-2">
                    <iconify-icon icon="solar:share-bold" class="text-xl text-[var(--accent)]"></iconify-icon>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Share Link Generator</h3>
                </div>
                <button @click="modals.share = false" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                    <iconify-icon icon="solar:close-circle-bold" class="text-xl"></iconify-icon>
                </button>
            </div>

            <template x-if="!generatedShareUrl">
                <form @submit.prevent="submitShare()">
                    <div class="mb-4">
                        <label class="block text-xs text-[var(--text-secondary)] mb-1">Expiration Period</label>
                        <select x-model="shareForm.expires_in" class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none text-sm">
                            <option value="1h">1 Hour</option>
                            <option value="24h">24 Hours</option>
                            <option value="7d">7 Days</option>
                            <option value="30d">30 Days</option>
                            <option value="never">Never Expire</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs text-[var(--text-secondary)] mb-1">Password Protection (Optional)</label>
                        <input type="password" x-model="shareForm.password" placeholder="Leave empty for public link" class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none text-sm">
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs text-[var(--text-secondary)] mb-1">Download Limit (Optional)</label>
                        <input type="number" min="1" x-model="shareForm.max_downloads" placeholder="e.g. 5 (Leave empty for unlimited)" class="w-full px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded focus:outline-none text-sm">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="modals.share = false" class="px-4 py-2 text-sm text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-[var(--accent)] text-white rounded font-medium hover:bg-[var(--accent-hover)]">Create Link</button>
                    </div>
                </form>
            </template>

            <template x-if="generatedShareUrl">
                <div class="space-y-4">
                    <p class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">Your share link is ready!</p>
                    <div class="flex items-center space-x-2">
                        <input type="text" readonly :value="generatedShareUrl" class="flex-1 px-3 py-2 border border-[var(--border)] bg-[var(--bg-app)] text-[var(--text-primary)] rounded text-xs select-all">
                        <button @click="copyShareUrl()" class="px-3 py-2 bg-[var(--accent)] text-white text-xs rounded hover:bg-[var(--accent-hover)]">Copy</button>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="modals.share = false" class="px-4 py-2 text-sm bg-[var(--bg-hover)] text-[var(--text-primary)] rounded">Done</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- FILE PREVIEW MODAL -->
    <div x-show="modals.preview" class="fixed inset-0 z-[120] bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="modals.preview = false" class="w-full max-w-4xl max-h-[90vh] bg-[var(--bg-surface)] border border-[var(--border)] rounded-lg shadow-2xl flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-[var(--border)] flex justify-between items-center bg-[var(--bg-surface)]">
                <div>
                    <h3 class="text-base font-semibold text-[var(--text-primary)]" x-text="previewData ? previewData.name : ''"></h3>
                    <p class="text-xs text-[var(--text-secondary)]" x-text="previewData ? `${previewData.mime} • ${previewData.human_size}` : ''"></p>
                </div>
                <div class="flex items-center space-x-2">
                    <a :href="previewData ? previewData.url : '#'" target="_blank" class="p-2 text-[var(--text-secondary)] hover:text-[var(--accent)] rounded hover:bg-[var(--bg-hover)]" title="Open Direct URL">
                        <iconify-icon icon="solar:link-bold" class="text-xl"></iconify-icon>
                    </a>
                    <button @click="modals.preview = false" class="p-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                        <iconify-icon icon="solar:close-circle-bold" class="text-xl"></iconify-icon>
                    </button>
                </div>
            </div>

            <!-- Content Container -->
            <div class="flex-1 overflow-auto p-6 flex items-center justify-center bg-[var(--bg-app)]">
                <template x-if="previewData && previewData.is_image">
                    <img :src="previewData.url" :alt="previewData.name" class="max-h-[70vh] object-contain rounded shadow">
                </template>

                <template x-if="previewData && previewData.is_video">
                    <video controls class="max-h-[70vh] max-w-full rounded shadow">
                        <source :src="previewData.url" :type="previewData.mime">
                    </video>
                </template>

                <template x-if="previewData && previewData.is_audio">
                    <audio controls class="w-full max-w-md">
                        <source :src="previewData.url" :type="previewData.mime">
                    </audio>
                </template>

                <template x-if="previewData && previewData.is_pdf">
                    <iframe :src="previewData.url" class="w-full h-[70vh] rounded border border-[var(--border)]"></iframe>
                </template>

                <template x-if="previewData && previewData.is_text">
                    <pre class="w-full max-h-[70vh] p-4 bg-slate-900 text-slate-100 text-xs font-mono rounded overflow-auto whitespace-pre-wrap select-text" x-text="previewData.content"></pre>
                </template>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="fixed top-4 right-4 z-[200] space-y-2 pointer-events-none select-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                :class="{
                    'bg-slate-900 text-white border-slate-700': toast.type === 'info',
                    'bg-emerald-800 text-white border-emerald-600': toast.type === 'success',
                    'bg-rose-800 text-white border-rose-600': toast.type === 'error'
                }"
                class="pointer-events-auto px-4 py-3 rounded-lg border shadow-xl flex items-center space-x-3 text-sm min-w-[280px]">
                
                <iconify-icon :icon="toast.type === 'success' ? 'solar:check-circle-bold' : (toast.type === 'error' ? 'solar:danger-triangle-bold' : 'solar:info-circle-bold')" class="text-xl flex-shrink-0"></iconify-icon>
                <span x-text="toast.message" class="flex-1"></span>
            </div>
        </template>
    </div>
</div>
