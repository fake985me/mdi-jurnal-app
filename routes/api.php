<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-2fa', [AuthController::class, 'verify2fa']);
Route::post('/resend-2fa', [AuthController::class, 'resend2fa']);

// TODO: FIX AUTH - History routes temporarily public for local testing
// Move back inside auth:sanctum middleware before production deployment
Route::get('/history', [App\Http\Controllers\Api\HistoryController::class, 'index']);
Route::get('/history/stats', [App\Http\Controllers\Api\HistoryController::class, 'stats']);

// Guest routes for public product and CMS content
Route::prefix('guest')->group(function () {
    Route::get('/products', [\App\Http\Controllers\Api\GuestController::class, 'products']);
    Route::get('/products/{id}', [\App\Http\Controllers\Api\GuestController::class, 'show']);
    Route::get('/categories', [\App\Http\Controllers\Api\GuestController::class, 'categories']);

    // CMS Content - Public Access
    Route::get('/solutions', [\App\Http\Controllers\Api\SolutionController::class, 'index']);
    Route::get('/site-settings', [\App\Http\Controllers\Api\SiteSettingController::class, 'index']);
    Route::get('/contact-info', [\App\Http\Controllers\Api\ContactInfoController::class, 'index']);
    Route::get('/carousel-slides', [\App\Http\Controllers\Api\CarouselController::class, 'index']);

    // Public Products - Website Content
    Route::get('/public-products', [\App\Http\Controllers\Api\PublicProductController::class, 'index']);
    Route::get('/public-products/{id}', [\App\Http\Controllers\Api\PublicProductController::class, 'show']);

    // Page Builder - Public Pages
    Route::get('/pages/slug/{slug}', [\App\Http\Controllers\Api\PageController::class, 'showBySlug']);
    Route::get('/navigation', [\App\Http\Controllers\Api\PageController::class, 'getNavigation']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats']);
        Route::get('/stock-chart', [App\Http\Controllers\Api\DashboardController::class, 'stockChart']);
        Route::get('/top-products', [App\Http\Controllers\Api\DashboardController::class, 'topProducts']);
        Route::get('/sales-trend', [App\Http\Controllers\Api\DashboardController::class, 'salesTrend']);
    });

    // Categories
    Route::apiResource('categories', App\Http\Controllers\Api\CategoryController::class);

    // Subcategories
    Route::apiResource('subcategories', App\Http\Controllers\Api\SubCategoryController::class);

    // Products
    Route::get('/products/filter-options', [App\Http\Controllers\Api\ProductController::class, 'filterOptions']);
    Route::apiResource('products', App\Http\Controllers\Api\ProductController::class);
    Route::post('/products/{product}/images', [App\Http\Controllers\Api\ProductController::class, 'uploadImages']);

    // Stock Management
    Route::prefix('stock')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\StockController::class, 'index']);
        Route::get('/transactions', [App\Http\Controllers\Api\StockController::class, 'transactions']);
        Route::post('/transaction', [App\Http\Controllers\Api\StockController::class, 'store']);
        Route::get('/rma-inventory', [App\Http\Controllers\Api\StockController::class, 'rmaStock']);
        Route::get('/investment-inventory', [App\Http\Controllers\Api\StockController::class, 'investmentStock']);
        Route::get('/msa-inventory', [App\Http\Controllers\Api\StockController::class, 'msaStock']);
        Route::get('/defective', [App\Http\Controllers\Api\StockController::class, 'defectiveStock']);
    });
    // Legacy routes for backwards compatibility
    Route::get('/current-stocks', [App\Http\Controllers\Api\StockController::class, 'index']);
    Route::get('/stock-transactions', [App\Http\Controllers\Api\StockController::class, 'transactions']);
    Route::post('/stock-transactions', [App\Http\Controllers\Api\StockController::class, 'store']);

    // Stock Adjustments
    Route::prefix('stock-adjustments')->group(function () {
        Route::get('/reasons', [App\Http\Controllers\Api\StockAdjustmentController::class, 'reasons']);
        Route::get('/stats', [App\Http\Controllers\Api\StockAdjustmentController::class, 'stats']);
        Route::get('/product/{productId}', [App\Http\Controllers\Api\StockAdjustmentController::class, 'byProduct']);
    });
    Route::apiResource('stock-adjustments', App\Http\Controllers\Api\StockAdjustmentController::class)->only(['index', 'store', 'show']);

    // Sales
    Route::get('/sales/{id}/export', [App\Http\Controllers\Api\SaleController::class, 'exportInvoice']);
    Route::get('/sales/{id}/pdf', [App\Http\Controllers\Api\SaleController::class, 'downloadPdf']);
    Route::apiResource('sales', App\Http\Controllers\Api\SaleController::class);
    Route::get('/sales-statistics', [App\Http\Controllers\Api\SaleController::class, 'statistics']);

    // Purchases
    Route::apiResource('purchases', App\Http\Controllers\Api\PurchaseController::class);

    // Sales People
    Route::apiResource('sales-people', App\Http\Controllers\Api\SalesPersonController::class);

    // Assets
    Route::get('/assets/filter-options', [App\Http\Controllers\Api\AssetController::class, 'filterOptions']);
    Route::post('/assets/create-product', [App\Http\Controllers\Api\AssetController::class, 'createAssetProduct']);
    Route::apiResource('assets', App\Http\Controllers\Api\AssetController::class);

    // Warranties
    Route::apiResource('warranties', App\Http\Controllers\Api\WarrantyController::class);

    // Lendings
    Route::apiResource('lendings', App\Http\Controllers\Api\LendingController::class);
    Route::post('/lendings/{lending}/return', [App\Http\Controllers\Api\LendingController::class, 'processReturn']);

    // RMAs (Return Merchandise Authorization)
    Route::get('/rmas/sales-with-warranty', [App\Http\Controllers\Api\RMAController::class, 'getSalesWithWarranty']);
    Route::post('/rmas/check-eligibility', [App\Http\Controllers\Api\RMAController::class, 'checkEligibility']);
    Route::apiResource('rmas', App\Http\Controllers\Api\RMAController::class);
    Route::post('/rmas/{rma}/mark-received', [App\Http\Controllers\Api\RMAController::class, 'markReceived']);
    Route::post('/rmas/{rma}/process', [App\Http\Controllers\Api\RMAController::class, 'process']);

    // Project Investments
    Route::get('/project-investments/{id}/export', [App\Http\Controllers\Api\ProjectInvestmentController::class, 'export']);
    Route::post('/project-investments/{id}/approve', [App\Http\Controllers\Api\ProjectInvestmentController::class, 'approve']);
    Route::post('/project-investments/{id}/start', [App\Http\Controllers\Api\ProjectInvestmentController::class, 'start']);
    Route::post('/project-investments/{id}/complete', [App\Http\Controllers\Api\ProjectInvestmentController::class, 'complete']);
    Route::post('/project-investments/{id}/cancel', [App\Http\Controllers\Api\ProjectInvestmentController::class, 'cancel']);
    Route::apiResource('project-investments', App\Http\Controllers\Api\ProjectInvestmentController::class);

    // Project Tasks
    Route::prefix('project-investments/{project}')->group(function () {
        Route::get('/tasks', [App\Http\Controllers\Api\ProjectTaskController::class, 'index']);
        Route::post('/tasks', [App\Http\Controllers\Api\ProjectTaskController::class, 'store']);
        Route::post('/tasks/reorder', [App\Http\Controllers\Api\ProjectTaskController::class, 'reorder']);
    });
    Route::put('/project-tasks/{task}', [App\Http\Controllers\Api\ProjectTaskController::class, 'update']);
    Route::delete('/project-tasks/{task}', [App\Http\Controllers\Api\ProjectTaskController::class, 'destroy']);
    Route::post('/project-tasks/{task}/status', [App\Http\Controllers\Api\ProjectTaskController::class, 'updateStatus']);

    // Project Progress & Materials
    Route::post('/project-investments/{id}/update-progress', [App\Http\Controllers\Api\ProjectInvestmentController::class, 'updateProgress']);
    Route::get('/project-investments/{id}/progress-history', [App\Http\Controllers\Api\ProjectInvestmentController::class, 'progressHistory']);
    Route::prefix('project-investments/{project}')->group(function () {
        Route::get('/materials', [App\Http\Controllers\Api\ProjectMaterialController::class, 'index']);
        Route::post('/materials', [App\Http\Controllers\Api\ProjectMaterialController::class, 'store']);
        Route::post('/materials/reorder', [App\Http\Controllers\Api\ProjectMaterialController::class, 'reorder']);
    });
    Route::put('/project-materials/{material}', [App\Http\Controllers\Api\ProjectMaterialController::class, 'update']);
    Route::post('/project-materials/{material}/quantities', [App\Http\Controllers\Api\ProjectMaterialController::class, 'updateQuantities']);
    Route::delete('/project-materials/{material}', [App\Http\Controllers\Api\ProjectMaterialController::class, 'destroy']);

    // MSA Projects (Maintenance Service Agreement)
    Route::post('/msa-projects/{id}/start-repair', [App\Http\Controllers\Api\MSAProjectController::class, 'startRepair']);
    Route::post('/msa-projects/{id}/mark-returned', [App\Http\Controllers\Api\MSAProjectController::class, 'markReturned']);
    Route::post('/msa-projects/{id}/replace', [App\Http\Controllers\Api\MSAProjectController::class, 'replaceItem']);
    Route::post('/msa-projects/{id}/close', [App\Http\Controllers\Api\MSAProjectController::class, 'close']);
    Route::apiResource('msa-projects', App\Http\Controllers\Api\MSAProjectController::class);

    // Warehouse Management
    Route::prefix('warehouses')->group(function () {
        Route::get('/statistics', [App\Http\Controllers\Api\WarehouseController::class, 'statistics']);
        Route::get('/products', [App\Http\Controllers\Api\WarehouseController::class, 'products']);
        Route::get('/default-stocks', [App\Http\Controllers\Api\WarehouseController::class, 'defaultWarehouseStocks']);
        Route::post('/add-product-stock', [App\Http\Controllers\Api\WarehouseController::class, 'addProductStock']);
        Route::post('/{warehouse}/set-default', [App\Http\Controllers\Api\WarehouseController::class, 'setDefault']);
        Route::get('/{warehouse}/locations', [App\Http\Controllers\Api\WarehouseController::class, 'locations']);
        Route::post('/{warehouse}/locations', [App\Http\Controllers\Api\WarehouseController::class, 'storeLocation']);
    });
    Route::apiResource('warehouses', App\Http\Controllers\Api\WarehouseController::class);
    Route::put('/warehouse-locations/{location}', [App\Http\Controllers\Api\WarehouseController::class, 'updateLocation']);
    Route::delete('/warehouse-locations/{location}', [App\Http\Controllers\Api\WarehouseController::class, 'destroyLocation']);

    // Stock Transfers
    Route::prefix('stock-transfers')->group(function () {
        Route::get('/statistics', [App\Http\Controllers\Api\StockTransferController::class, 'statistics']);
        Route::post('/{stockTransfer}/start', [App\Http\Controllers\Api\StockTransferController::class, 'startTransfer']);
        Route::post('/{stockTransfer}/complete', [App\Http\Controllers\Api\StockTransferController::class, 'completeTransfer']);
        Route::post('/{stockTransfer}/cancel', [App\Http\Controllers\Api\StockTransferController::class, 'cancelTransfer']);
    });
    Route::apiResource('stock-transfers', App\Http\Controllers\Api\StockTransferController::class)->except(['update', 'destroy']);

    // Tax Rates
    Route::get('/tax-rates/active', [App\Http\Controllers\Api\TaxRateController::class, 'active']);
    Route::post('/tax-rates/{taxRate}/toggle', [App\Http\Controllers\Api\TaxRateController::class, 'toggle']);
    Route::apiResource('tax-rates', App\Http\Controllers\Api\TaxRateController::class);

    // Invoices & Accounting
    Route::get('/invoices/tax-report', [App\Http\Controllers\Api\InvoiceController::class, 'taxReport']);
    Route::get('/invoices/monthly-summary', [App\Http\Controllers\Api\InvoiceController::class, 'monthlySummary']);
    Route::get('/invoices/{invoice}/pdf', [App\Http\Controllers\Api\InvoiceController::class, 'downloadPdf']);
    Route::post('/invoices/{invoice}/mark-paid', [App\Http\Controllers\Api\InvoiceController::class, 'markAsPaid']);
    Route::post('/invoices/{invoice}/mark-sent', [App\Http\Controllers\Api\InvoiceController::class, 'markAsSent']);
    Route::apiResource('invoices', App\Http\Controllers\Api\InvoiceController::class);

    // Deliveries
    Route::apiResource('deliveries', App\Http\Controllers\Api\DeliveryController::class);

    // Dashboard
    Route::get('/dashboard/stock-chart', [App\Http\Controllers\Api\DashboardController::class, 'stockChart']);
    Route::get('/dashboard/top-products', [App\Http\Controllers\Api\DashboardController::class, 'topProducts']);
    Route::get('/dashboard/sales-trend', [App\Http\Controllers\Api\DashboardController::class, 'salesTrend']);
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats']);

    // Excel Import/Export for Products
    Route::get('/products/export/all', [App\Http\Controllers\Api\ProductController::class, 'exportProducts']);
    Route::get('/products/export/template', [App\Http\Controllers\Api\ProductController::class, 'exportTemplate']);
    Route::post('/products/import', [App\Http\Controllers\Api\ProductController::class, 'importProducts']);

    // Other Excel exports (keep if needed)
    Route::get('/export/sales', [App\Http\Controllers\Api\ExcelController::class, 'exportSales']);

    // CMS Content Management - Admin Only
    Route::apiResource('solutions', App\Http\Controllers\Api\SolutionController::class);
    Route::apiResource('site-settings', App\Http\Controllers\Api\SiteSettingController::class);
    Route::post('/site-settings/bulk-update', [App\Http\Controllers\Api\SiteSettingController::class, 'bulkUpdate']);
    Route::apiResource('contact-info', App\Http\Controllers\Api\ContactInfoController::class);
    Route::apiResource('carousel-slides', App\Http\Controllers\Api\CarouselController::class);

    // Public Products Management
    // IMPORTANT: Specific routes MUST come BEFORE apiResource
    Route::get('/public-products/filters/categories', [App\Http\Controllers\Api\PublicProductController::class, 'categories']);
    Route::get('/public-products/filters/subcategories', [App\Http\Controllers\Api\PublicProductController::class, 'subCategories']);
    Route::get('/public-products/filters/brands', [App\Http\Controllers\Api\PublicProductController::class, 'brands']);
    Route::get('/public-products/export/all', [App\Http\Controllers\Api\PublicProductController::class, 'exportProducts']);
    Route::get('/public-products/export/template', [App\Http\Controllers\Api\PublicProductController::class, 'exportTemplate']);
    Route::post('/public-products/import', [App\Http\Controllers\Api\PublicProductController::class, 'importProducts']);
    Route::post('/public-products/{id}/toggle-active', [App\Http\Controllers\Api\PublicProductController::class, 'toggleActive']);
    Route::apiResource('public-products', App\Http\Controllers\Api\PublicProductController::class);

    // Page Builder - Admin Only
    Route::apiResource('pages', App\Http\Controllers\Api\PageController::class);
    Route::post('/pages/{id}/publish', [App\Http\Controllers\Api\PageController::class, 'publish']);
    Route::post('/pages/{id}/unpublish', [App\Http\Controllers\Api\PageController::class, 'unpublish']);
    Route::post('/pages/{id}/duplicate', [App\Http\Controllers\Api\PageController::class, 'duplicate']);
    Route::post('/pages/{pageId}/sections/reorder', [App\Http\Controllers\Api\PageSectionController::class, 'reorder']);
    Route::apiResource('page-sections', App\Http\Controllers\Api\PageSectionController::class)->except(['index', 'show']);
    Route::apiResource('page-templates', App\Http\Controllers\Api\PageTemplateController::class);

    // User Management (Superadmin only)
    Route::middleware('can:manage-users')->group(function () {
        Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
    });
});
