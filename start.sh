#!/usr/bin/env bash
# Start script untuk Render.com

# Alternatif 1: PHP Built-in Server (Simple, tapi tidak untuk production berat)
# php -S 0.0.0.0:$PORT -t public

# Alternatif 2: Artisan Serve (Lebih baik untuk Laravel)
php artisan serve --host=0.0.0.0 --port=$PORT --no-reload

# Note: Untuk production yang lebih robust, gunakan Nginx/Apache
# Tapi Render.com free tier tidak support full web server
# Jika upgrade ke paid plan, bisa gunakan Docker dengan Nginx
