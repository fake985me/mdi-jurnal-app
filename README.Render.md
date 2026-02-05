# 🚀 Deploy ke Render.com - Panduan Lengkap

## 📋 Prerequisites

> [!IMPORTANT] > **Render.com tidak support PHP runtime secara native.** Deployment menggunakan **Docker**, jadi pastikan `Dockerfile.render` sudah ada di repository.

Persiapan yang diperlukan:

-   ✅ Git repository (GitHub/GitLab/Bitbucket)
-   ✅ Docker configuration (`Dockerfile.render`)
-   ✅ Render.com account (gratis)

---

## 🚀 Method 1: Deploy dengan render.yaml (Recommended)

### Step 1: Review File Konfigurasi

File yang diperlukan sudah dibuat:

-   `render.yaml` - Infrastructure as Code
-   `Dockerfile.render` - Docker image untuk Render.com
-   `.env.render` - Template environment variables

### Step 2: Generate APP_KEY

```bash
php artisan key:generate --show
```

Simpan hasilnya (format: `base64:xxx...`).

### Step 3: Push ke Git

```bash
git add .
git commit -m "Add Render.com configuration"
git branch -M main
git push origin main
```

### Step 4: Deploy di Render

1. **Login ke [Render.com](https://render.com)**

2. **New + → Blueprint**

3. **Connect Repository**

    - Pilih repository `mai-app-update`
    - Authorize Render

4. **Render detect render.yaml**

    - Review services:
        - ✅ Web Service: `mai-app` (Docker)
        - ✅ PostgreSQL: `mai-app-db`

5. **Set APP_KEY** (CRITICAL!)

    - Setelah services dibuat, buka Web Service
    - Environment → Edit → Tambahkan:

    ```
    APP_KEY=base64:xxx...  # dari step 2
    ```

6. **Update APP_URL**

    ```
    APP_URL=https://mai-app-xxxxx.onrender.com
    ```

    (URL akan diberikan setelah service dibuat)

7. **Monitor Build**
    - Build time: ~10-15 menit
    - Tunggu status "Live" 🟢

---

## 🛠️ Method 2: Deploy Manual (Without render.yaml)

### Step 1: Create PostgreSQL

1. Dashboard → **New + → PostgreSQL**
2. Settings:
    - Name: `mai-app-db`
    - Database: `mai_app`
    - Region: Singapore
    - Plan: Free
3. **Create Database**
4. Simpan **Internal Database URL**

### Step 2: Create Web Service

1. Dashboard → **New + → Web Service**
2. Connect repository `mai-app-update`
3. Settings:

    - **Name**: `mai-app`
    - **Region**: Singapore
    - **Branch**: `main`
    - **Environment**: **Docker**
    - **Dockerfile Path**: `./Dockerfile.render`

4. **Environment Variables** (lihat `.env.render` untuk lengkap):

```bash
APP_NAME=MAI-App
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxx...
APP_URL=https://your-app.onrender.com

# Database (dari Internal Database URL)
DB_CONNECTION=pgsql
DB_HOST=dpg-xxxxx.singapore-postgres.render.com
DB_PORT=5432
DB_DATABASE=mai_app
DB_USERNAME=mai_app_user
DB_PASSWORD=xxxxx

# Cache & Session
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

**Parse Database URL:**

```
postgresql://username:password@host:5432/database
           └─────────┘└────────┘└────────────────┘└──────┘
         DB_USERNAME DB_PASSWORD    DB_HOST    DB_DATABASE
```

5. **Create Web Service**

---

## ✅ Post-Deployment

### 1. Verify Migration

```bash
# Di Render Shell
php artisan migrate:status
```

Jika belum running:

```bash
php artisan migrate --force
```

### 2. (Optional) Seed Database

```bash
php artisan db:seed --force
```

### 3. Test Aplikasi

-   ✅ Buka URL aplikasi
-   ✅ Test authentication
-   ✅ Test CRUD operations
-   ✅ Verify assets loading

### 4. Enable Auto-Deploy

Settings → **Auto-Deploy: ON**

Setiap push ke `main` akan auto-deploy.

---

## 🔧 Troubleshooting

### ❌ Docker Build Failed

**Problem**: Build gagal dengan error Docker

**Solutions**:

1. Check `Dockerfile.render` exists
2. Review build logs untuk specific error
3. Verify PHP extensions configuration
4. Check Node.js build step

### ❌ Error 500

**Problem**: White screen/500 error

**Solutions**:

1. Enable debug temporary:
    ```
    APP_DEBUG=true
    ```
2. Check logs:
    ```bash
    tail -f storage/logs/laravel.log
    ```
3. Common causes:
    - Missing `APP_KEY`
    - Database connection failed
    - Storage permissions

### ❌ Database Connection Failed

**Problem**: Can't connect to PostgreSQL

**Solutions**:

1. Verify DB credentials in env vars
2. db credentials match Internal Database URL
3. Check PostgreSQL status = "Available"
4. Test connection:
    ```bash
    php artisan tinker
    DB::connection()->getPdo();
    ```

### ❌ Assets Not Loading

**Problem**: CSS/JS 404 Not Found

**Solutions**:

1. Verify `npm run build` succeeded in Docker build
2. Check `public/build/manifest.json` exists
3. Verify `APP_URL` matches actual URL
4. Check browser console for errors

### ⚠️ Free Tier Sleep

**Problem**: App sleeps after 15 min inactivity

**Solutions**:

-   **Upgrade to Starter** ($7/month) untuk always-on
-   Use external ping service (UptimeRobot)
-   Optimize cold start time

---

## 💰 Pricing

### Free Tier

-   **Web Service**: 750 hrs/month, sleeps after 15 min
-   **PostgreSQL**: 1GB, 90-day retention
-   **Cost**: $0/month

### Starter Tier (Recommended for Production)

-   **Web Service**: $7/month (always on)
-   **PostgreSQL**: $7/month (backups, no time limit)
-   **Total**: $14/month

---

## 🔐 Security Checklist

Before going live:

-   ✅ `APP_DEBUG=false`
-   ✅ `APP_ENV=production`
-   ✅ Strong database password
-   ✅ HTTPS enabled (automatic)
-   ✅ Environment variables secured

---

## � Files Created

Konfigurasi Render.com meliputi:

-   `render.yaml` - Blueprint deployment
-   `Dockerfile.render` - Optimized Docker image
-   `.env.render` - Environment template
-   `build.sh` - Build script (not used in Docker mode)
-   `start.sh` - Start script (not used in Docker mode)

---

**Selamat deploy! 🚀**

Jika ada masalah, check logs di Render Dashboard atau lihat dokumentasi Render.
