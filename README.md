# Production-Ready CDN File Manager (Laravel 11 + OneDrive UI)

A self-hosted CDN file manager built with **Laravel 11**, **Blade**, **Tailwind CSS (Dark Mode)**, and **Alpine.js**. Direct public asset access via simple URLs without authentication, paired with an admin panel inspired by Microsoft OneDrive.

---

## Features

- ⚡ **Direct Public Access**: Files stored directly inside `public/` are accessible via simple URLs (e.g., `https://cdn.conzex.com/bg/dc.jpg`).
- 🎨 **OneDrive UI Shell**: Glassmorphism, light/dark mode persistence, clean breadcrumb navigation, search, and context menu.
- 🛡️ **Security First**:
  - Path traversal protection (`realpath` validation, null byte checks, `../` rejection).
  - Dangerous extension blocking (`php`, `phar`, `phtml`, `exe`, `sh`, `bat`, `py`, `rb`, etc.).
  - Dotfile hiding (`.env`, `.htaccess`, `.git`).
  - Public HTTP access blocked for `.trash`.
- 🖼️ **Thumbnail Generation**: Dynamic WebP thumbnails with cache header controls powered by `intervention/image:^3`.
- 🗑️ **Recycle Bin**: Soft-deletion to `public/.trash/` with restore, purge, and empty bin capabilities.
- 🔗 **Share Links**: Public share URLs with customizable expiration times (1h, 24h, 7d, 30d, never), optional password protection, and download limits.
- 📦 **Zip Streaming**: Multi-file/folder download streaming via `maennchen/zipstream-php`.
- 📜 **Activity Logger**: Automated activity logging for all operations (login, upload, rename, move, delete, share creation/download).

---

## Admin Credentials

- **Username**: `admin`
- **Password**: `Adm1n@123`
- **Login URL**: `/login`
- **Admin Dashboard**: `/admin`

---

## cPanel & Shared Hosting Deployment Steps

### 1. Upload Application Files
1. Compress and upload all application files to your server directory (e.g., `/home/username/cdn-app`).
2. Point your domain or subdomain (`cdn.conzex.com`) document root to `/home/username/cdn-app/public`.
3. If document root cannot be modified on shared hosting, keep files in root and the included `.htaccess` file will automatically redirect traffic to `public/`.

### 2. Set File Permissions
Ensure the web server has write access to necessary directories:
```bash
chmod -R 755 storage bootstrap/cache public
```

### 3. Environment & Database Configuration
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Update `.env` database parameters:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=your_cpanel_db
   DB_USERNAME=your_cpanel_user
   DB_PASSWORD=your_cpanel_password
   ```
3. Run migrations and seed the initial admin account:
   ```bash
   php artisan key:generate
   php artisan migrate:fresh --seed
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## Cron Schedule Configuration

Add the standard Laravel scheduler entry to your cPanel Cron Jobs (running every minute):
```cron
* * * * * cd /home/username/cdn-app && php artisan schedule:run >> /dev/null 2>&1
```

The application schedules the following daily cleanup tasks:
- `shares:prune` (Prunes expired/exhausted share links)
- `activity:prune --days=90` (Prunes audit logs older than 90 days)
- `recycle:purge --days=30` (Prunes trashed items older than 30 days)

Or manually add individual cron tasks:
```cron
0 0 * * * cd /home/username/cdn-app && php artisan shares:prune
0 0 * * * cd /home/username/cdn-app && php artisan activity:prune --days=90
0 0 * * * cd /home/username/cdn-app && php artisan recycle:purge --days=30
```

---

## Backup Recipe

### Database Backup
```bash
mysqldump -u cpanel_cdn_user -p cpanel_cdn_db > backup_cdn_db_$(date +%Y%m%d).sql
```

### CDN Files Backup
```bash
tar -czf backup_public_files_$(date +%Y%m%d).tar.gz public/
```

---

## Verification & Unit Testing

Run the test suite:
```bash
php artisan test
```
