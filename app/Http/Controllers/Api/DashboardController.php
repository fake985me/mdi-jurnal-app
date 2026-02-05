<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\CurrentStock;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $stats = [
            'totalProducts' => Product::count(),
            'totalSales' => Sale::where('status', 'completed')->count(),
            'pendingSales' => Sale::where('status', 'pending')->count(),
            'totalPurchases' => Purchase::where('status', 'received')->count(),
            'lowStockItems' => DB::table('current_stocks')
                ->join('products', 'current_stocks.product_id', '=', 'products.id')
                ->whereRaw('current_stocks.quantity < 10') // Default threshold since min_stock removed
                ->count(),
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
}
