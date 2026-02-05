# 📦 Warehouse Management System

A comprehensive warehouse management Single Page Application (SPA) built with **Laravel 10** and **Vue 3**.

## Features

-   ✅ **Complete Authentication** - Laravel Sanctum with role-based access (SuperAdmin, Admin, Sales)
-   ✅ **Product Management** - Categories, subcategories, and product product
-   ✅ **Stock Tracking** - Real-time stock levels with transaction history
-   ✅ **Sales & Purchases** - Full order management system
-   ✅ **Warranty Tracking** - Product warranty management
-   ✅ **Lending System** - Equipment lending with return tracking
-   ✅ **Delivery Tracking** - Shipment status by PO/Project
-   ✅ **Dashboard Analytics** - ApexCharts visualization (Bar, Pie, Line charts)
-   ✅ **Modern SPA** - Vue 3 with Vue Router and Pinia
-   🚀 **Excel Import/Export** - (Routes ready, implementation pending)
-   🚀 **Image Upload** - (Structure ready, implementation pending)

## Tech Stack

**Backend:**

-   Laravel 10
-   Laravel Sanctum (Authentication)
-   MySQL Database
-   Laravel Excel (Ready)

**Frontend:**

-   Vue 3 (Composition API)
-   Vue Router 4
-   Pinia (State Management)
-   Axios
-   ApexCharts
-   Vite

## Quick Start

### Prerequisites

-   PHP 8.1+
-   Composer
-   Node.js 16+
-   MySQL
-   Laragon (Recommended)

### Installation

1. **Clone & Navigate**

```bash
cd c:\laragon\www\warehouse-app
```

2. **Install Dependencies**

```bash
composer install
npm install --legacy-peer-deps
```

3. **Configure Environment**

```bash
# Copy .env.example if needed
copy .env.example .env

# Generate application key if not done
php artisan key:generate
```

4. **Setup Database**

```bash
# Make sure MySQL is running
# Create database: warehouse_db

# Run migrations with seed data
php artisan migrate:fresh --seed
```

5. **Run Development Servers**

**Terminal 1 - Laravel:**

```bash
php artisan serve
# Runs on http://localhost:8000
```

**Terminal 2 - Vite:**

```bash
npm run dev
# Compiles Vue assets
```

6. **Access Application**

Open browser: **http://localhost:8000**

## Demo Credentials

| Role           | Email               | Password |
| -------------- | ------------------- | -------- |
| **SuperAdmin** | admin@warehouse.com | password |
| **Admin**      | admin@example.com   | password |
| **Sales**      | sales@example.com   | password |

## Database Structure

The application includes **15 database tables**:

-   Users (with roles)
-   Categories (with subcategories)
-   Products & Product Images
-   Current Stocks & Stock Transactions
-   Sales & Sale Items
-   Purchases & Purchase Items
-   Sales People
-   Warranties
-   Lendings & Lending Returns
-   Deliveries & Delivery Items

## Project Structure

```
warehouse-app/
├── app/Models/          # 15 Eloquent models
├── app/Http/Controllers/Api/
├── database/migrations/ # 15 migrations
├── resources/js/
│   ├── pages/          # Vue pages
│   ├── layouts/        # Dashboard layout
│   ├── router/         # Vue Router config
│   ├── stores/         # Pinia stores
│   └── services/       # API service
└── routes/
    ├── api.php         # API endpoints
    └── web.php         # SPA routes
```

## API Endpoints

### Public

-   `POST /api/login`
-   `GET /api/guest/products`

### Protected (Requires Auth)

-   `/api/products` - Product management
-   `/api/categories` - Category management
-   `/api/sales` - Sales transactions
-   `/api/purchases` - Purchase orders
-   `/api/stock-transactions` - Stock operations
-   `/api/warranties` - Warranty tracking
-   `/api/lendings` - Lending management
-   `/api/deliveries` - Delivery status
-   `/api/dashboard/*` - Dashboard data

### SuperAdmin Only

-   `/api/users` - User management

## Development Notes

### Completed

-   ✅ Full database schema
-   ✅ All Eloquent models with relationships
-   ✅ Authentication with Sanctum
-   ✅ API route structure
-   ✅ Vue SPA with routing
-   ✅ Dashboard with charts
-   ✅ Landing page
-   ✅ Login system

### In Progress / TODO

-   🔄 Product CRUD interface
-   🔄 Stock management UI
-   🔄 Sales/Purchase forms
-   🔄 Category management
-   🔄 Excel import/export controllers
-   🔄 Image upload functionality
-   🔄 Warranty management UI
-   🔄 Lending system UI
-   🔄 Delivery tracking UI
-   🔄 User management (SuperAdmin)

## Troubleshooting

### Vite Build Errors

```bash
# Clear node modules and reinstall
rm -rf node_modules
npm install --legacy-peer-deps
```

### Database Issues

```bash
# Reset database
php artisan migrate:fresh --seed
```

### Permission Errors

```bash
# Fix storage permissions
php artisan storage:link
```

## License

MIT License

## Support

For issues or questions, refer to the `walkthrough.md` file for detailed documentation.
