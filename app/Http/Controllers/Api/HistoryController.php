<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    /**
     * Get unified sales and purchases history
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'all'); // 'sale', 'purchase', 'all'
        $perPage = $request->get('per_page', 15);
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $status = $request->get('status');

        $data = collect();

        // Fetch Sales
        if ($type === 'sale' || $type === 'all') {
            $salesQuery = Sale::with(['user', 'salesPerson', 'items.product'])
                ->select([
                    'id',
                    'invoice_number as reference_number',
                    'customer_name as party_name',
                    'total_amount',
                    'status',
                    'sale_date as date',
                    'user_id',
                    'sales_person_id',
                    'created_at',
                    'updated_at'
                ])
                ->selectRaw("'sale' as type");

            if ($dateFrom) {
                $salesQuery->where('sale_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $salesQuery->where('sale_date', '<=', $dateTo);
            }
            if ($status) {
                $salesQuery->where('status', $status);
            }

            $sales = $salesQuery->get()->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'type' => 'sale',
                    'reference_number' => $sale->reference_number,
                    'party_name' => $sale->party_name,
                    'total_amount' => $sale->total_amount,
                    'status' => $sale->status,
                    'date' => $sale->date,
                    'items_count' => $sale->items->count(),
                    'created_by' => $sale->user->name ?? 'Unknown',
                    'sales_person' => $sale->salesPerson->name ?? null,
                    'created_at' => $sale->created_at,
                    'updated_at' => $sale->updated_at,
                ];
            });

            $data = $data->merge($sales);
        }

        // Fetch Purchases
        if ($type === 'purchase' || $type === 'all') {
            $purchasesQuery = Purchase::with(['user', 'items.product'])
                ->select([
                    'id',
                    'po_number as reference_number',
                    'supplier_name as party_name',
                    'total_amount',
                    'status',
                    'order_date as date',
                    'user_id',
                    'created_at',
                    'updated_at'
                ])
                ->selectRaw("'purchase' as type");

            if ($dateFrom) {
                $purchasesQuery->where('order_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $purchasesQuery->where('order_date', '<=', $dateTo);
            }
            if ($status) {
                $purchasesQuery->where('status', $status);
            }

            $purchases = $purchasesQuery->get()->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'type' => 'purchase',
                    'reference_number' => $purchase->reference_number,
                    'party_name' => $purchase->party_name,
                    'total_amount' => $purchase->total_amount,
                    'status' => $purchase->status,
                    'date' => $purchase->date,
                    'items_count' => $purchase->items->count(),
                    'created_by' => $purchase->user->name ?? 'Unknown',
                    'sales_person' => null,
                    'created_at' => $purchase->created_at,
                    'updated_at' => $purchase->updated_at,
                ];
            });

            $data = $data->merge($purchases);
        }

        // Sort by date (newest first)
        $data = $data->sortByDesc('created_at')->values();

        // Paginate manually
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedData = $data->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $paginatedData,
            'meta' => [
                'current_page' => (int) $currentPage,
                'per_page' => (int) $perPage,
                'total' => $data->count(),
                'last_page' => ceil($data->count() / $perPage),
            ]
        ]);
    }

    /**
     * Get statistics
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $salesTotal = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('total_amount');

        $salesCount = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->count();

        $purchasesTotal = Purchase::whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('status', 'received')
            ->sum('total_amount');

        $purchasesCount = Purchase::whereBetween('order_date', [$dateFrom, $dateTo])
            ->count();

        return response()->json([
            'sales' => [
                'total' => $salesTotal,
                'count' => $salesCount,
            ],
            'purchases' => [
                'total' => $purchasesTotal,
                'count' => $purchasesCount,
            ],
            'net' => $salesTotal - $purchasesTotal,
        ]);
    }
}
