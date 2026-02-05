# Render.com Deployment Troubleshooting

## Build Failed - Error Analysis

Jika mendapat error "Exited with status 1", ikuti langkah berikut:

### Step 1: Check Deploy Logs

Di Render Dashboard:

1. Buka Web Service yang failed
2. Klik tab **Logs**
3. Scroll ke bagian error (biasanya di akhir)
4. Cari kata kunci error seperti:
    - `ERROR`
    - `FAILED`
    - `npm ERR!`
    - `composer error`

### Step 2: Common Build Errors & Solutions

#### ❌ Error: GD Extension Build Failed

**Error message:**

```
configure: error: libpng not found
```

**Solution:**
Fixed in latest `Dockerfile.render` dengan menambahkan dependencies:

```dockerfile
libjpeg-turbo-dev \
freetype-dev \
libwebp-dev
```

#### ❌ Error: npm ci failed

**Error message:**

```
npm ERR! `--only` is deprecated. Use `--omit=dev` instead.
```

**Solution:**
Update npm command dari:

```bash
npm ci --only=production
```

Menjadi:

```bash
npm ci --prefer-offline --omit=dev
```

#### ❌ Error: Composer dependencies conflict

**Error message:**

```
Your requirements could not be resolved to an installable set of packages.
```

**Solution:**

1. Check `composer.json` untuk conflicting versions
2. Update composer.lock:
    ```bash
    composer update --lock
    git add composer.lock
    git commit -m "Update composer.lock"
    git push
    ```

#### ❌ Error: Out of Memory

**Error message:**

```
FATAL ERROR: Reached heap limit Allocation failed
```

**Solution:**
Tambahkan di Dockerfile sebelum npm build:

```dockerfile
ENV NODE_OPTIONS="--max-old-space-size=2048"
```

#### ❌ Error: Database connection during build

**Error message:**

```
SQLSTATE[HY000] [2002] Connection refused
```

**Cause:**
Laravel commands seperti `config:cache` atau `route:cache` mencoba connect ke database.

**Solution:**
Skip database-dependent commands saat build, atau set dummy DB:

```dockerfile
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=:memory:
```

#### ❌ Error: Missing .env file

**Error message:**

```
ERROR: Please provide a valid cache path.
```

**Solution:**
Tambahkan di Dockerfile sebelum artisan commands:

```dockerfile
RUN cp .env.example .env && \
    php artisan key:generate
```

### Step 3: Quick Fixes

**Rebuild dengan latest fixes:**

```bash
git pull  # jika ada update dari saya
git add .
git commit -m "Fix Dockerfile build issues"
git push
```

Render akan auto-deploy lagi.

### Step 4: Manual Deploy

Di Render Dashboard:

1. Klik **Manual Deploy**
2. Pilih **Clear build cache & deploy**
3. Monitor logs

### Step 5: Test Locally

Test Docker build di lokal:

```bash
docker build -f Dockerfile.render -t mai-app-test .
```

Jika berhasil di lokal, issue ada di Render environment.

## Debug Checklist

-   [ ] Check deploy logs untuk specific error
-   [ ] Verify all files committed and pushed
-   [ ] Check composer.json & package.json valid
-   [ ] Verify .env.render template correct
-   [ ] Test Dockerfile locally
-   [ ] Check Render service plan (free tier limitations)
-   [ ] Verify PostgreSQL database created and running
-   [ ] Check environment variables set correctly

## Get Help

**Share dengan saya:**

1. Screenshot atau copy full error dari deploy logs
2. Output dari `git log -1` (last commit)
3. Screenshot dari Render environment variables

**Render Support:**

-   Community: https://community.render.com
-   Docs: https://render.com/docs

---

**Next Steps After Fix:**

Once build succeeds:

1. Verify app running: `https://your-app.onrender.com`
2. Check database migrations: `php artisan migrate:status`
3. Test application functionality
4. Enable auto-deploy if satisfied
