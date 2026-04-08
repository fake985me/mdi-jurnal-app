<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\CurrentStock;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $defaultWarehouse = Warehouse::getDefault();

        $lowStockQuery = DB::table('current_stocks')
            ->join('products', 'current_stocks.product_id', '=', 'products.id')
            ->whereRaw('current_stocks.quantity < 10');

        if ($defaultWarehouse) {
            $lowStockQuery->where('current_stocks.warehouse_id', $defaultWarehouse->id);
        }

        $stats = [
            'totalProducts' => Product::count(),
            'totalSales' => Sale::where('status', 'completed')->count(),
            'pendingSales' => Sale::where('status', 'pending')->count(),
            'totalPurchases' => Purchase::where('status', 'received')->count(),
            'lowStockItems' => $lowStockQuery->count(),
        ];

        return response()->json($stats);
    }

    public function stockChart(Request $request)
    {
        $categoryId = $request->query('category_id');

        $query = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->with('product');

        // Filter by category if provided (using pivot table)
        if ($categoryId) {
            $query->whereHas('product.productCategories', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $topProducts = $query->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        // Extract product names safely
        $categories = [];
        $salesData = [];
        
        foreach ($topProducts as $item) {
            if ($item->product) {
                $categories[] = $item->product->title ?? $item->product->name ?? 'Unknown';
                $salesData[] = (int) $item->total_sold;
            }
        }

        $data = [
            'categories' => $categories,
            'series' => [
                [
                    'name' => 'Units Sold',
                    'data' => $salesData,
                ]
            ],
        ];

        return response()->json($data);
    }

    public function topProducts()
    {
        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->with('product')
            ->get();

        $labels = [];
        $series = [];
        
        foreach ($topProducts as $item) {
            if ($item->product) {
                $labels[] = $item->product->title ?? $item->product->name ?? 'Unknown';
                $series[] = (int) $item->total_sold;
            }
        }

        $data = [
            'labels' => $labels,
            'series' => $series,
        ];

        return response()->json($data);
    }

    public function salesTrend(Request $request)
    {
        $months = $request->months ?? 12;

        $salesData = Sale::where('status', 'completed')
            ->where('sale_date', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(sale_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $data = [
            'categories' => $salesData->pluck('month')->toArray(),
            'series' => [
                [
                    'name' => 'Sales Count',
                    'data' => $salesData->pluck('total_sales')->toArray(),
                ],
                [
                    'name' => 'Revenue',
                    'data' => $salesData->pluck('total_amount')->toArray(),
                ]
            ],
        ];

        return response()->json($data);
    }

    /**
     * Get top 10 most sold products with stock info from default warehouse
     */
    public function topSellingStock()
    {
        try {
            $defaultWarehouse = Warehouse::getDefault();

            // Get top 10 products by total quantity sold
            $topSelling = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
                ->groupBy('product_id')
                ->orderBy('total_sold', 'desc')
                ->limit(10)
                ->pluck('total_sold', 'product_id');

            if ($topSelling->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'warehouse' => $defaultWarehouse ? [
                        'id' => $defaultWarehouse->id,
                        'name' => $defaultWarehouse->name,
                    ] : null,
                ]);
            }

            // Get stock data for these products
            $query = CurrentStock::with([
                    'product.productCategories.category',
                    'product.productCategories.subCategory',
                ])
                ->whereIn('product_id', $topSelling->keys());

            if ($defaultWarehouse) {
                $query->where('warehouse_id', $defaultWarehouse->id);
            }

            $stocks = $query->get();

            // Enrich with category pairs and total_sold
            $stocks->transform(function ($stock) use ($topSelling) {
                if ($stock->product) {
                    $stock->product->category_pairs = $stock->product->categoryPairs;
                }
                $stock->total_sold = $topSelling[$stock->product_id] ?? 0;
                return $stock;
            });

            // Sort by total_sold descending
            $stocks = $stocks->sortByDesc('total_sold')->values();

            return response()->json([
                'data' => $stocks,
                'warehouse' => $defaultWarehouse ? [
                    'id' => $defaultWarehouse->id,
                    'name' => $defaultWarehouse->name,
                ] : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Top Selling Stock Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading top selling stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock report for default warehouse
     */
    public function stockReport(Request $request)
    {
        try {
            $defaultWarehouse = Warehouse::getDefault();
            $perPage = $request->query('per_page', 10);

            $query = CurrentStock::with([
                    'product.productCategories.category',
                    'product.productCategories.subCategory',
                    'warehouse',
                    'location'
                ])
                ->whereHas('product', function ($q) {
                    $q->where('is_asset', false)->orWhereNull('is_asset');
                })
                ->orderBy('quantity', 'desc');

            // Filter by default warehouse
            if ($defaultWarehouse) {
                $query->where('warehouse_id', $defaultWarehouse->id);
            }

            $stocks = $query->paginate($perPage);

            // Append category_pairs to each product
            $stocks->getCollection()->transform(function ($stock) {
                if ($stock->product) {
                    $stock->product->category_pairs = $stock->product->categoryPairs;
                }
                return $stock;
            });

            return response()->json([
                'data' => $stocks->items(),
                'warehouse' => $defaultWarehouse ? [
                    'id' => $defaultWarehouse->id,
                    'name' => $defaultWarehouse->name,
                ] : null,
                'pagination' => [
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                    'per_page' => $stocks->perPage(),
                    'total' => $stocks->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Stock Report Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error loading stock report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
