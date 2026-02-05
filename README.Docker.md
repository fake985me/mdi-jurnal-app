# 🐳 Docker Setup untuk MAI App

Dokumentasi lengkap untuk menjalankan aplikasi MAI menggunakan Docker.

## 📋 Prerequisites

-   Docker Desktop (Windows/Mac) atau Docker Engine (Linux)
-   Docker Compose v2.0+
-   Git

## 🚀 Quick Start (Development)

### 1. Clone & Setup

```bash
# Clone repository (jika belum)
git clone <repository-url>
cd mai-app-update

# Copy environment file
cp .env.example .env
```

### 2. Konfigurasi Environment

Edit file `.env` dengan konfigurasi berikut:

```env
APP_NAME="MAI App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=mai_app
DB_USERNAME=mai_user
DB_PASSWORD=root

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Build & Run

```bash
# Build dan jalankan semua container
docker-compose up -d

# Lihat logs
docker-compose logs -f app

# Masuk ke container untuk menjalankan perintah
docker-compose exec app sh
```

### 4. Setup Database

```bash
# Jalankan migration
docker-compose exec app php artisan migrate

# (Opsional) Seed database
docker-compose exec app php artisan db:seed

# (Opsional) Import SQL file
docker-compose exec -T mysql mysql -uroot -proot mai_app < mdi-app-local.sql
```

### 5. Akses Aplikasi

-   **Aplikasi**: http://localhost:8000
-   **Vite HMR**: http://localhost:5173
-   **phpMyAdmin**: http://localhost:8080

## 🏭 Production Deployment

### 1. Build Production Image

```bash
# Build production image
docker build -t mai-app:latest -f Dockerfile .

# Atau gunakan docker-compose
docker-compose -f docker-compose.prod.yml build
```

### 2. Setup Environment Production

```bash
# Copy dan edit .env untuk production
cp .env.example .env

# Edit nilai-nilai berikut:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://yourdomain.com
# Isi semua kredensial database dan services lainnya
```

### 3. Jalankan Production

```bash
# Jalankan dengan docker-compose
docker-compose -f docker-compose.prod.yml up -d

# Generate application key (jika belum)
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Optimize
docker-compose -f docker-compose.prod.yml exec app php artisan optimize
```

## 🛠️ Perintah Berguna

### Container Management

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# View logs
docker-compose logs -f [service-name]

# Remove all containers and volumes
docker-compose down -v
```

### Laravel Commands

```bash
# Masuk ke container
docker-compose exec app sh

# Artisan commands
docker-compose exec app php artisan [command]

# Composer
docker-compose exec app composer [command]

# NPM (development only)
docker-compose exec app npm [command]

# Queue worker
docker-compose exec app php artisan queue:work

# Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Database Management

```bash
# Access MySQL console
docker-compose exec mysql mysql -umai_user -proot mai_app

# Backup database
docker-compose exec mysql mysqldump -umai_user -proot mai_app > backup.sql

# Restore database
docker-compose exec -T mysql mysql -umai_user -proot mai_app < backup.sql

# Access Redis CLI
docker-compose exec redis redis-cli
```

## 📁 Struktur File Docker

```
.
├── Dockerfile                          # Production Dockerfile
├── Dockerfile.dev                      # Development Dockerfile
├── docker-compose.yml                  # Development compose
├── docker-compose.prod.yml             # Production compose
├── .dockerignore                       # Files to ignore
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf                  # Production Nginx config
│   │   └── nginx.dev.conf              # Development Nginx config
│   ├── supervisor/
│   │   ├── supervisord.conf            # Production Supervisor config
│   │   └── supervisord.dev.conf        # Development Supervisor config
│   ├── mysql/
│   │   └── my.cnf                      # MySQL configuration
│   └── scripts/
│       └── entrypoint.sh               # Container entrypoint script
└── README.Docker.md                    # This file
```

## 🔧 Troubleshooting

### Port sudah digunakan

```bash
# Cek port yang digunakan
netstat -ano | findstr :8000  # Windows
lsof -i :8000                 # Linux/Mac

# Ubah port di docker-compose.yml
ports:
  - "8001:80"  # Ganti 8000 ke 8001
```

### Permission Issues (Linux)

```bash
# Set ownership
sudo chown -R $USER:$USER .

# Set permissions
chmod -R 755 storage bootstrap/cache
```

### Database Connection Failed

```bash
# Pastikan MySQL sudah ready
docker-compose logs mysql

# Restart MySQL container
docker-compose restart mysql

# Cek koneksi
docker-compose exec app php artisan migrate:status
```

### Vite HMR tidak bekerja

```bash
# Edit vite.config.js, pastikan ada:
server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
        host: 'localhost'
    }
}

# Restart container
docker-compose restart app
```

## 🔐 Security Notes (Production)

1. **Gunakan strong passwords** untuk database
2. **Set APP_DEBUG=false** di production
3. **Gunakan HTTPS** dengan reverse proxy (Nginx, Caddy)
4. **Limit exposed ports** - hanya expose port yang diperlukan
5. **Update regularly** - keep Docker images up to date
6. **Use secrets** untuk credentials (Docker Swarm/Kubernetes)

## 📊 Monitoring & Logs

```bash
# View all logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f mysql

# Follow Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# Check container stats
docker stats

# Container health
docker-compose ps
```

## 🚀 CI/CD Integration

Contoh GitHub Actions:

```yaml
name: Docker Build & Deploy

on:
    push:
        branches: [main]

jobs:
    build:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v2

            - name: Build Docker Image
              run: docker build -t mai-app:latest .

            - name: Push to Registry
              run: |
                  docker tag mai-app:latest registry.example.com/mai-app:latest
                  docker push registry.example.com/mai-app:latest
```

## 📝 Notes

-   Development menggunakan volume mounts untuk hot-reload
-   Production build mengoptimalkan assets dan dependencies
-   Supervisor mengelola PHP-FPM, Nginx, dan Queue Workers
-   Redis digunakan untuk cache, session, dan queue

## 📞 Support

Jika ada masalah atau pertanyaan, silakan buat issue di repository.

---

**Happy Dockerizing! 🐳**
