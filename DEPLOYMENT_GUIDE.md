# 🚀 Deployment Guide

## ⚠️ Recent Fix: API Connection Issue

### Problem
The app was trying to connect to `http://localhost:8000` even in production, causing:
- `ERR_CONNECTION_REFUSED` errors
- Failed to fetch products/contact info
- Network errors in production

### Solution
Changed `bootstrap.js` to use dynamic API URL:
```javascript
// Before (hardcoded):
window.axios.defaults.baseURL = 'http://localhost:8000';

// After (dynamic):
window.axios.defaults.baseURL = import.meta.env.VITE_API_URL || window.location.origin;
```

### How it Works
- **Development**: Set `VITE_API_URL=http://localhost:8000` in `.env`
- **Production**: Don't set `VITE_API_URL`, app will use `window.location.origin` (current domain)

---

## 🔧 Local Development Setup

### 1. Environment Setup
```bash
# Copy .env.example to .env (if not exists)
copy .env.example .env

# Make sure you have this line in .env:
VITE_API_URL=http://localhost:8000
```

### 2. Install Dependencies
```bash
composer install
npm install --legacy-peer-deps
```

### 3. Database Setup
```bash
# Run migrations and seeders
php artisan migrate:fresh --seed
```

### 4. Run Development Servers

**Terminal 1 - Laravel Backend:**
```bash
php artisan serve
# Runs on http://localhost:8000
```

**Terminal 2 - Vite Frontend:**
```bash
npm run dev
# Compiles Vue assets with hot reload
```

### 5. Access Application
Open browser: **http://localhost:8000**

---

## 🌐 Production Deployment (Render.com)

### 1. Environment Variables in Render Dashboard

Go to your Render service → Environment → Add the following:

#### Required Variables:
```bash
APP_NAME=MAI-App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com  # Replace with actual URL
APP_KEY=base64:YOUR_KEY_HERE  # Generate with: php artisan key:generate --show

# Database (Auto-filled by Render if using render.yaml)
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=mai_app
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Sanctum
SANCTUM_STATEFUL_DOMAINS=your-app-name.onrender.com
SESSION_DOMAIN=.onrender.com
```

#### ⚠️ IMPORTANT: DO NOT SET `VITE_API_URL` in Production
The app will automatically use the current domain (`window.location.origin`)

### 2. Build Process

The `build.sh` script will:
1. Install PHP dependencies
2. Install Node dependencies
3. Build frontend assets with Vite
4. Run migrations
5. Optimize Laravel

### 3. Deploy
```bash
# Push to git repository connected to Render
git add .
git commit -m "Fixed API connection for production"
git push origin main
```

Render will automatically:
- Run `build.sh`
- Start the application
- Make it available at your domain

### 4. Verify Deployment
1. Check Render logs for any errors
2. Visit your app URL: `https://your-app-name.onrender.com`
3. Open browser console (F12) - should see no network errors
4. Test functionality:
   - Products should load
   - Contact info should appear
   - Dashboard should work

---

## 🐛 Troubleshooting

### Network Error in Production
**Symptom:** `ERR_CONNECTION_REFUSED`, `localhost:8000` in console

**Solution:**
1. Make sure `VITE_API_URL` is NOT set in Render environment variables
2. Rebuild the app on Render
3. Clear browser cache and hard refresh (Ctrl + Shift + R)

### Mixed Content Error (HTTP/HTTPS)
**Symptom:** HTTPS page trying to load HTTP resources

**Solution:**
- Ensure all URLs use relative paths or `https://`
- Check `APP_URL` in production uses `https://`

### API Not Found (404)
**Symptom:** API requests return 404

**Solution:**
1. Check `.htaccess` or nginx config for proper routing
2. Verify `routes/api.php` has the endpoints
3. Check Laravel logs in `storage/logs/`

### Assets Not Loading
**Symptom:** CSS/JS files missing, blank page

**Solution:**
1. Rebuild: `npm run build`
2. Check `public/build/manifest.json` exists
3. Verify `vite.config.js` has correct paths

---

## 📝 Important Notes

### Development vs Production

| Environment | VITE_API_URL | Actual Base URL |
|-------------|-------------|-----------------|
| Development | `http://localhost:8000` | `http://localhost:8000` |
| Production  | (not set) | `https://your-app.onrender.com` |

### After Code Changes
```bash
# Development - Vite will auto-reload
# Just save files, browser will update

# Production - Need to rebuild
git add .
git commit -m "Your changes"
git push  # Render auto-deploys
```

### Database Migrations in Production
Migrations run automatically via `build.sh` on every deployment.

To run manually:
```bash
# In Render shell
php artisan migrate --force
```

---

## 🔐 Security Checklist

Before going live:
- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] `SANCTUM_STATEFUL_DOMAINS` configured
- [ ] CORS settings reviewed
- [ ] SSL/HTTPS enabled
- [ ] No sensitive data in `.env` committed to git

---

## 📞 Support

For issues:
1. Check Render logs
2. Check browser console (F12)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Review this guide

## 🎉 Success Indicators

Your deployment is successful when:
- ✅ No console errors in browser
- ✅ Products load on homepage
- ✅ Can login to dashboard
- ✅ API requests use correct domain (not localhost)
- ✅ All pages render correctly
