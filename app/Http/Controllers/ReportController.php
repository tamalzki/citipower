<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function inventory(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'all';
        $search = $request->string('search')->toString();

        $products = Product::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($status === 'low', function ($query) {
                $query->whereColumn('stock_quantity', '<=', 'minimum_stock')
                    ->where('stock_quantity', '>', 0);
            })
            ->when($status === 'out', function ($query) {
                $query->where('stock_quantity', '<=', 0);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total_products' => Product::count(),
            'total_units' => (int) Product::sum('stock_quantity'),
            'low_stock_count' => Product::whereColumn('stock_quantity', '<=', 'minimum_stock')
                ->where('stock_quantity', '>', 0)
                ->count(),
            'out_of_stock_count' => Product::where('stock_quantity', '<=', 0)->count(),
            'inventory_cost_value' => (float) Product::selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as total')->value('total'),
            'inventory_retail_value' => (float) Product::selectRaw('COALESCE(SUM(stock_quantity * selling_price), 0) as total')->value('total'),
        ];

        return view('reports.inventory', compact('products', 'summary', 'status', 'search'));
    }

    public function sales(Request $request)
    {
        $dateFromInput = $request->input('date_from');
        $dateToInput = $request->input('date_to');

        $dateFrom = $dateFromInput
            ? Carbon::parse($dateFromInput)->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $dateTo = $dateToInput
            ? Carbon::parse($dateToInput)->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $saleItemsQuery = SaleItem::query()
            ->whereHas('sale', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            });

        $totals = $saleItemsQuery
            ->selectRaw('
                COALESCE(SUM(quantity), 0) as total_units_sold,
                COALESCE(SUM(subtotal), 0) as gross_sales,
                COALESCE(SUM(quantity * purchase_price), 0) as total_cost
            ')
            ->first();

        $salesQuery = Sale::whereBetween('created_at', [$dateFrom, $dateTo]);
        $salesCount = (clone $salesQuery)->count();
        $netSales = (float) (clone $salesQuery)->sum('total_amount');
        $totalDiscounts = (float) (clone $salesQuery)->sum('discount_amount');

        $productPerformance = SaleItem::query()
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('SUM(subtotal) as revenue'),
                DB::raw('SUM(quantity * purchase_price) as cost')
            )
            ->whereHas('sale', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->paginate(20)
            ->withQueryString();

        $recentSales = Sale::with('items.product')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $summary = [
            'sales_count' => $salesCount,
            'total_units_sold' => (int) ($totals->total_units_sold ?? 0),
            'gross_sales' => (float) ($totals->gross_sales ?? 0),
            'net_sales' => $netSales,
            'total_discounts' => $totalDiscounts,
            'total_cost' => (float) ($totals->total_cost ?? 0),
        ];
        $summary['gross_profit'] = $summary['net_sales'] - $summary['total_cost'];

        return view('reports.sales', [
            'summary' => $summary,
            'productPerformance' => $productPerformance,
            'recentSales' => $recentSales,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
        ]);
    }

    public function profitLoss(Request $request)
    {
        $dateFromInput = $request->input('date_from');
        $dateToInput = $request->input('date_to');

        $dateFrom = $dateFromInput
            ? Carbon::parse($dateFromInput)->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $dateTo = $dateToInput
            ? Carbon::parse($dateToInput)->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $salesSummary = Sale::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('
                COALESCE(SUM(total_amount), 0) as net_sales,
                COALESCE(SUM(discount_amount), 0) as total_discounts
            ')
            ->first();

        $cogs = (float) SaleItem::query()
            ->whereHas('sale', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->selectRaw('COALESCE(SUM(quantity * purchase_price), 0) as total')
            ->value('total');

        $totalExpenses = (float) Expense::query()
            ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->sum('amount');

        $expenseBreakdown = Expense::query()
            ->select('expense_category_id', DB::raw('SUM(amount) as total_amount'))
            ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->with('category')
            ->groupBy('expense_category_id')
            ->orderByDesc('total_amount')
            ->get();

        $salesSummaryRows = Sale::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with('items')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $productProfitability = SaleItem::query()
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('SUM(subtotal) as gross_revenue'),
                DB::raw('SUM(quantity * purchase_price) as cogs'),
                DB::raw('SUM(subtotal - (quantity * purchase_price)) as gross_profit')
            )
            ->whereHas('sale', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('gross_profit')
            ->limit(10)
            ->get();

        $summary = [
            'gross_sales' => (float) (($salesSummary->net_sales ?? 0) + ($salesSummary->total_discounts ?? 0)),
            'net_sales' => (float) ($salesSummary->net_sales ?? 0),
            'discounts' => (float) ($salesSummary->total_discounts ?? 0),
            'cogs' => $cogs,
            'gross_profit' => (float) ($salesSummary->net_sales ?? 0) - $cogs,
            'operating_expenses' => $totalExpenses,
        ];
        $summary['net_profit'] = $summary['gross_profit'] - $summary['operating_expenses'];

        return view('reports.profit-loss', [
            'summary' => $summary,
            'expenseBreakdown' => $expenseBreakdown,
            'salesSummaryRows' => $salesSummaryRows,
            'productProfitability' => $productProfitability,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
        ]);
    }
}
