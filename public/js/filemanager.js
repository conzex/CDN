document.addEventListener('alpine:init', () => {
    Alpine.data('fileManager', () => ({
        currentPath: '',
        breadcrumbs: [],
        items: [],
        selected: [],
        lastClickedIndex: null,
        viewMode: localStorage.getItem('cdn-view-mode') || 'grid', // 'grid' or 'list'
        searchQuery: '',
        sortBy: 'name', // 'name', 'date', 'size'
        sortOrder: 'asc',
        loading: false,
        theme: localStorage.getItem('cdn-theme') || 'light',
        
        // Context Menu
        contextMenu: {
            show: false,
            x: 0,
            y: 0,
            item: null
        },

        // Modals state
        modals: {
            newFolder: false,
            rename: false,
            move: false,
            delete: false,
            share: false,
            preview: false
        },

        // Active items for modal actions
        activeItem: null,
        newFolderName: '',
        renameName: '',
        moveDestDir: '',
        
        // Share modal state
        shareForm: {
            expires_in: '7d',
            password: '',
            max_downloads: ''
        },
        generatedShareUrl: null,

        // Preview state
        previewData: null,

        // Toast notifications
        toasts: [],

        init() {
            this.fetchList('');
            
            // Global keybindings
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.clearSelection();
                    this.contextMenu.show = false;
                    this.closeAllModals();
                }
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a' && !this.isModalOpen()) {
                    e.preventDefault();
                    this.selectAll();
                }
            });

            window.addEventListener('click', () => {
                this.contextMenu.show = false;
            });
        },

        isModalOpen() {
            return Object.values(this.modals).some(Boolean);
        },

        closeAllModals() {
            Object.keys(this.modals).forEach(k => this.modals[k] = false);
        },

        showToast(message, type = 'info') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        },

        async fetchList(path = '') {
            this.loading = true;
            this.selected = [];
            try {
                const res = await fetch(`/admin/api/list?path=${encodeURIComponent(path)}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.currentPath = data.current_path;
                    this.breadcrumbs = data.breadcrumbs;
                    this.items = data.items;
                } else {
                    this.showToast(data.message || 'Failed to load directory.', 'error');
                }
            } catch (err) {
                this.showToast('Network error loading files.', 'error');
            } finally {
                this.loading = false;
            }
        },

        get filteredItems() {
            let result = [...this.items];

            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(item => item.name.toLowerCase().includes(q));
            }

            result.sort((a, b) => {
                if (a.type !== b.type) {
                    return a.type === 'folder' ? -1 : 1;
                }
                let comp = 0;
                if (this.sortBy === 'name') {
                    comp = a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' });
                } else if (this.sortBy === 'date') {
                    comp = a.mtime - b.mtime;
                } else if (this.sortBy === 'size') {
                    comp = a.size - b.size;
                }
                return this.sortOrder === 'asc' ? comp : -comp;
            });

            return result;
        },

        toggleViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('cdn-view-mode', mode);
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('cdn-theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            fetch('/admin/api/preferences/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ theme: this.theme })
            }).catch(() => {});
        },

        // Item selection handlers
        handleItemClick(item, index, event) {
            if (event.shiftKey && this.lastClickedIndex !== null) {
                const start = Math.min(this.lastClickedIndex, index);
                const end = Math.max(this.lastClickedIndex, index);
                const rangePaths = this.filteredItems.slice(start, end + 1).map(i => i.path);
                this.selected = Array.from(new Set([...this.selected, ...rangePaths]));
            } else if (event.ctrlKey || event.metaKey) {
                if (this.selected.includes(item.path)) {
                    this.selected = this.selected.filter(p => p !== item.path);
                } else {
                    this.selected.push(item.path);
                }
                this.lastClickedIndex = index;
            } else {
                this.selected = [item.path];
                this.lastClickedIndex = index;
            }
        },

        selectAll() {
            this.selected = this.filteredItems.map(i => i.path);
        },

        clearSelection() {
            this.selected = [];
            this.lastClickedIndex = null;
        },

        isSelected(path) {
            return this.selected.includes(path);
        },

        handleDoubleClick(item) {
            if (item.type === 'folder') {
                this.fetchList(item.path);
            } else {
                this.openPreview(item);
            }
        },

        // Context Menu
        openContextMenu(event, item) {
            event.preventDefault();
            if (!this.selected.includes(item.path)) {
                this.selected = [item.path];
            }
            this.contextMenu.item = item;
            this.contextMenu.x = Math.min(event.clientX, window.innerWidth - 200);
            this.contextMenu.y = Math.min(event.clientY, window.innerHeight - 250);
            this.contextMenu.show = true;
        },

        // Upload
        async uploadFiles(fileList) {
            if (!fileList || fileList.length === 0) return;

            const formData = new FormData();
            formData.append('path', this.currentPath);
            for (let i = 0; i < fileList.length; i++) {
                formData.append('files[]', fileList[i]);
            }

            this.showToast('Uploading file(s)...', 'info');
            try {
                const res = await fetch('/admin/api/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.showToast(data.message, 'success');
                    this.fetchList(this.currentPath);
                } else {
                    this.showToast(data.message || 'Upload failed.', 'error');
                }
            } catch (err) {
                this.showToast('Error uploading files.', 'error');
            }
        },

        // Create Folder
        async createFolder() {
            if (!this.newFolderName.trim()) return;

            try {
                const res = await fetch('/admin/api/folder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        path: this.currentPath,
                        name: this.newFolderName.trim()
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.showToast(data.message, 'success');
                    this.modals.newFolder = false;
                    this.newFolderName = '';
                    this.fetchList(this.currentPath);
                } else {
                    this.showToast(data.message || 'Failed to create folder.', 'error');
                }
            } catch (err) {
                this.showToast('Error creating folder.', 'error');
            }
        },

        // Rename
        openRenameModal(item = null) {
            const target = item || this.getSelectedItem();
            if (!target) return;
            this.activeItem = target;
            this.renameName = target.name;
            this.modals.rename = true;
        },

        async submitRename() {
            if (!this.activeItem || !this.renameName.trim()) return;

            try {
                const res = await fetch('/admin/api/rename', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        path: this.activeItem.path,
                        new_name: this.renameName.trim()
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.showToast(data.message, 'success');
                    this.modals.rename = false;
                    this.fetchList(this.currentPath);
                } else {
                    this.showToast(data.message || 'Rename failed.', 'error');
                }
            } catch (err) {
                this.showToast('Error renaming item.', 'error');
            }
        },

        // Move
        openMoveModal(item = null) {
            this.activeItem = item || this.getSelectedItem();
            this.moveDestDir = '';
            this.modals.move = true;
        },

        async submitMove() {
            const paths = this.selected.length > 0 ? this.selected : (this.activeItem ? [this.activeItem.path] : []);
            if (paths.length === 0) return;

            try {
                const endpoint = paths.length > 1 ? '/admin/api/bulk/move' : '/admin/api/move';
                const body = paths.length > 1 
                    ? { paths, dest_dir: this.moveDestDir }
                    : { src: paths[0], dest_dir: this.moveDestDir };

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.showToast(data.message, 'success');
                    this.modals.move = false;
                    this.fetchList(this.currentPath);
                } else {
                    this.showToast(data.message || 'Move failed.', 'error');
                }
            } catch (err) {
                this.showToast('Error moving item(s).', 'error');
            }
        },

        // Delete
        openDeleteModal(item = null) {
            this.activeItem = item || this.getSelectedItem();
            this.modals.delete = true;
        },

        async confirmDelete() {
            const paths = this.selected.length > 0 ? this.selected : (this.activeItem ? [this.activeItem.path] : []);
            if (paths.length === 0) return;

            try {
                const endpoint = paths.length > 1 ? '/admin/api/bulk/delete' : '/admin/api/delete';
                const body = paths.length > 1 ? { paths } : { path: paths[0] };

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.showToast(data.message, 'success');
                    this.modals.delete = false;
                    this.clearSelection();
                    this.fetchList(this.currentPath);
                } else {
                    this.showToast(data.message || 'Delete failed.', 'error');
                }
            } catch (err) {
                this.showToast('Error deleting item(s).', 'error');
            }
        },

        // Download
        downloadSelected() {
            if (this.selected.length === 0) return;
            if (this.selected.length === 1) {
                const item = this.items.find(i => i.path === this.selected[0]);
                if (item && item.type === 'file') {
                    window.location.href = item.url;
                    return;
                }
            }
            // Trigger bulk zip download form submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/api/bulk/download';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrf);

            this.selected.forEach(path => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'paths[]';
                input.value = path;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        // Copy Public URL / CDN Links
        copySelectedUrls() {
            if (this.selected.length === 0) return;
            const urls = this.selected.map(p => {
                const item = this.items.find(i => i.path === p);
                return item ? item.url : `${window.location.origin}/${p}`;
            }).join('\n');

            navigator.clipboard.writeText(urls).then(() => {
                this.showToast(`${this.selected.length} URL(s) copied to clipboard!`, 'success');
            }).catch(() => {
                this.showToast('Failed to copy URLs.', 'error');
            });
        },

        // Share
        openShareModal(item = null) {
            this.activeItem = item || this.getSelectedItem();
            if (!this.activeItem) return;
            this.shareForm = { expires_in: '7d', password: '', max_downloads: '' };
            this.generatedShareUrl = null;
            this.modals.share = true;
        },

        async submitShare() {
            if (!this.activeItem) return;

            try {
                const res = await fetch('/admin/api/share/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        path: this.activeItem.path,
                        ...this.shareForm
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.generatedShareUrl = data.url;
                    this.showToast('Share link created successfully.', 'success');
                } else {
                    this.showToast(data.message || 'Failed to create share link.', 'error');
                }
            } catch (err) {
                this.showToast('Error creating share link.', 'error');
            }
        },

        copyShareUrl() {
            if (!this.generatedShareUrl) return;
            navigator.clipboard.writeText(this.generatedShareUrl).then(() => {
                this.showToast('Share URL copied to clipboard!', 'success');
            });
        },

        // Preview Modal
        async openPreview(item) {
            this.activeItem = item;
            try {
                const res = await fetch(`/admin/api/preview?path=${encodeURIComponent(item.path)}`);
                const data = await res.json();
                if (data.status === 'success') {
                    this.previewData = data;
                    this.modals.preview = true;
                } else {
                    this.showToast(data.message || 'Failed to load preview.', 'error');
                }
            } catch (err) {
                this.showToast('Error fetching preview.', 'error');
            }
        },

        // Clear Thumbnails Cache
        async clearThumbnailsCache() {
            try {
                const res = await fetch('/admin/api/thumbnails/clear', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.showToast(data.message, 'success');
                    this.fetchList(this.currentPath);
                }
            } catch (err) {
                this.showToast('Failed to clear thumbnail cache.', 'error');
            }
        },

        getSelectedItem() {
            if (this.selected.length === 0) return null;
            return this.items.find(i => i.path === this.selected[0]) || null;
        }
    }));
});
