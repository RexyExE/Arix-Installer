# Arix Theme v2.0.8 — Installation & Debugging Guide

Welcome to the official **Arix Theme v2.0.8** installation and troubleshooting guide for Pterodactyl Panel (v1.12.x). This guide details step-by-step installation, asset compilation, maintenance commands, and solutions to common errors.

---

## ⚡ Quick Start: Interactive VPS Master Script

You can run the interactive CLI management suite directly on your VPS:

```bash
# Run locally from panel or root directory
sudo bash arix-manager.sh
```

**Features included:**
* **Arix Theme**: Install, Remove, Rebuild, Repair.
* **Pterodactyl Panel**: Install, Remove, Rebuild, Repair.
* **Sectionized Backups**: Theme-only, Panel files only, MySQL Database only, and Full Sectionized System Bundle.
* **Backup Restore**: Interactive selection and restore.
* **System Doctor**: Comprehensive diagnostic audit of PHP, Node, MySQL, permissions, and configs.

---

## 📋 1. System Requirements & Prerequisites

Before installing or updating the Arix theme, ensure your environment meets the following specifications:

* **Pterodactyl Version**: `v1.12.x` (or latest 1.x series)
* **PHP Version**: `PHP 8.1` or `PHP 8.2` (with `pdo`, `mbstring`, `xml`, `json` extensions)
* **Node.js Environment**: `Node.js >= 16.x` & `Yarn` / `npm`
* **User Permissions**: Superuser / Root CLI access on your web server (`www-data` or `nginx` user permissions)

---

## 🚀 2. Step-by-Step Installation Guide

### Step 1: Create a System Backup
Always back up your panel directory and database before performing updates:
```bash
# Backup Pterodactyl Files
tar -czvf panel-backup-$(date +%F).tar.gz /var/www/pterodactyl

# Backup Database
mysqldump -u root -p panel > panel-db-backup-$(date +%F).sql
```

### Step 2: Extract & Overlay Theme Files
Navigate to your panel root directory and copy the Arix theme files:
```bash
cd /var/www/pterodactyl

# Copy theme files into the Pterodactyl root directory
cp -r /path/to/arix/v2.0.8/* ./
```

### Step 3: Run Database Migrations
Run the migrations to create required theme settings and server order tables:
```bash
php artisan migrate --force
```

### Step 4: Run Theme Setup Command
Arix includes a dedicated Artisan command that automates configuration seeding and asset syncing:
```bash
php artisan arix install
```

### Step 5: Build Frontend Assets (Optional / Production Rebuild)
If you modified stylesheets, React components, or Vite/Webpack configurations:
```bash
# Enable legacy SSL provider if running Node 17+
export NODE_OPTIONS=--openssl-legacy-provider

# Install dependencies and build production assets
yarn install
yarn build:production
```

### Step 6: Clear Cache & Set File Permissions
Ensure Laravel views and application caches are refreshed, and fix permissions:
```bash
# Clear Laravel caches
php artisan optimize:clear
php artisan view:clear
php artisan config:clear

# Set correct file ownership
chown -R www-data:www-data /var/www/pterodactyl/*
# Or for Nginx on RHEL/CentOS:
# chown -R nginx:nginx /var/www/pterodactyl/*
```

---

## 🛠️ 3. Automated 1-Click Auto-Fixing (`arix:fix`)

Arix Theme features an automated self-healing CLI command to quickly restore panel operation after configuration errors or cache issues:

```bash
php artisan arix:fix
```

### What `arix:fix` does automatically:
1. Clears compiled Blade views, route caches, and application settings.
2. Checks the database for missing default Arix setting keys and seeds defaults.
3. Fixes storage and public directory ownership (`www-data:www-data`).
4. Verifies theme assets in `public/arix/`.

---

## 🔍 4. Comprehensive Error Debugging Guide

### 💥 Error 1: `500 Internal Server Error` After Saving Settings
* **Symptom**: Panel returns HTTP 500 when saving settings in `/admin/arix` or navigating the admin area.
* **Root Cause**: Stale Blade template cache or missing database setting key.
* **Resolution**:
  ```bash
  php artisan view:clear
  php artisan config:clear
  php artisan arix:fix
  ```
  Check the log file for exact trace details:
  ```bash
  tail -n 50 /var/www/pterodactyl/storage/logs/laravel-$(date +%Y-%m-%d).log
  ```

---

### 💥 Error 2: `Call to undefined function array_get()` or `starts_with()`
* **Symptom**: PHP Fatal error mentioning `array_get()` or `starts_with()`.
* **Root Cause**: PHP 8.x / Laravel 9+ removed legacy helper functions.
* **Resolution**: Ensure you are using **Arix v2.0.8** (which replaces all legacy helper calls with `\Illuminate\Support\Arr::get()` and `\Illuminate\Support\Str::startsWith()`).

---

### 💥 Error 3: Drag & Drop Dashboard Widgets Not Saving
* **Symptom**: Rearranging dashboard widgets resets upon page reload.
* **Root Cause**: Malformed JSON string stored in `settings::arix:dashboardWidgets`.
* **Resolution**:
  1. Open `/admin/arix/dashboard` and click **Save** to refresh the JSON state.
  2. Alternatively, reset widget configuration via artisan:
     ```bash
     php artisan tinker
     >>> \Pterodactyl\Models\Setting::where('key', 'settings::arix:dashboardWidgets')->delete();
     ```

---

### 💥 Error 4: Admin Panel Missing Styles / Unstyled Pages
* **Symptom**: The panel loads as unstyled HTML text.
* **Root Cause**: Public assets are missing from `/public/arix/` or web server file permissions are incorrect.
* **Resolution**:
  ```bash
  php artisan arix
  chown -R www-data:www-data /var/www/pterodactyl/public
  chmod -R 755 /var/www/pterodactyl/public
  ```

---

### 💥 Error 5: Node.js Asset Build Error (`ERR_OSSL_EVP_UNSUPPORTED`)
* **Symptom**: `yarn build:production` fails with an OpenSSL digital envelope error.
* **Root Cause**: Node.js v17+ strict OpenSSL provider policies.
* **Resolution**:
  ```bash
  export NODE_OPTIONS=--openssl-legacy-provider
  yarn build:production
  ```

---

## 📞 5. Maintenance Checklist

| Task | Command | Frequency |
| :--- | :--- | :--- |
| Clear View Cache | `php artisan view:clear` | After updating theme files |
| Fix Permissions | `chown -R www-data:www-data /var/www/pterodactyl/*` | After uploading assets |
| Self-Healing Fix | `php artisan arix:fix` | When encountering 500 errors |
| Recompile Assets | `yarn build:production` | After modifying CSS/React source |
