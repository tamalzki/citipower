<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private const MAIN_BRANCH_CODE = 'DAV-MAIN';
    private const SECOND_BRANCH_CODE = 'DIG-SECOND';
    private const MAIN_BRANCH_LABEL = 'DAVAO -MAIN';
    private const SECOND_BRANCH_LABEL = 'DIGOS -SECOND';

    public function hub()
    {
        return view('reports.hub');
    }

    public function inventory(Request $request)
    {
        $mainBranch = Branch::firstOrCreate(['code' => self::MAIN_BRANCH_CODE], ['name' => self::MAIN_BRANCH_LABEL]);
        $secondBranch = Branch::firstOrCreate(['code' => self::SECOND_BRANCH_CODE], ['name' => self::SECOND_BRANCH_LABEL]);
        if ($mainBranch->name !== self::MAIN_BRANCH_LABEL) {
            $mainBranch->update(['name' => self::MAIN_BRANCH_LABEL]);
        }
        if ($secondBranch->name !== self::SECOND_BRANCH_LABEL) {
            $secondBranch->update(['name' => self::SECOND_BRANCH_LABEL]);
        }

        $status = $request->string('status')->toString() ?: 'all';
        $search = $request->string('search')->toString();

        $products = Product::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('suppliers', fn ($s) => $s->where('suppliers.name', 'like', "%{$search}%"));
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

        $toSecond = StockTransfer::where('to_branch_id', $secondBranch->id)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $fromSecond = StockTransfer::where('from_branch_id', $secondBranch->id)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $branchStocks = [];
        foreach ($products as $p) {
            $secondQty = max(0, (int) ($toSecond[$p->id] ?? 0) - (int) ($fromSecond[$p->id] ?? 0));
            $mainQty = (int) $p->stock_quantity;
            $branchStocks[$p->id] = [
                'main' => $mainQty,
                'second' => $secondQty,
                'total' => $mainQty + $secondQty,
            ];
        }

        $allProducts = Product::get(['id', 'stock_quantity', 'purchase_price', 'selling_price']);
        $mainTotal = (int) $allProducts->sum('stock_quantity');
        $secondTotal = 0;
        $combinedCostValue = 0.0;
        $combinedRetailValue = 0.0;
        foreach ($allProducts as $p) {
            $secondQty = max(0, (int) ($toSecond[$p->id] ?? 0) - (int) ($fromSecond[$p->id] ?? 0));
            $secondTotal += $secondQty;
            $combinedQty = (int) $p->stock_quantity + $secondQty;
            $combinedCostValue += ((float) $p->purchase_price * $combinedQty);
            $combinedRetailValue += ((float) $p->selling_price * $combinedQty);
        }

        $summary = [
            'total_products' => Product::count(),
            'total_units' => $mainTotal + $secondTotal,
            'low_stock_count' => Product::whereColumn('stock_quantity', '<=', 'minimum_stock')
                ->where('stock_quantity', '>', 0)
                ->count(),
            'out_of_stock_count' => Product::where('stock_quantity', '<=', 0)->count(),
            'inventory_cost_value' => $combinedCostValue,
            'inventory_retail_value' => $combinedRetailValue,
            'main_branch_units' => $mainTotal,
            'second_branch_units' => $secondTotal,
        ];

        return view('reports.inventory', compact(
            'products',
            'summary',
            'status',
            'search',
            'mainBranch',
            'secondBranch',
            'branchStocks'
        ));
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

    public function expenses(Request $request)
    {
        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        // Totals
        $totalAmount = (float) Expense::whereBetween('expense_date', [
            $dateFrom->toDateString(), $dateTo->toDateString()
        ])->sum('amount');

        $totalCount = Expense::whereBetween('expense_date', [
            $dateFrom->toDateString(), $dateTo->toDateString()
        ])->count();

        // Breakdown by category
        $byCategory = Expense::query()
            ->select('expense_category_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->with('category')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->get();

        // Line items (paginated)
        $expenses = Expense::with('category')
            ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('reports.expenses', [
            'dateFrom'    => $dateFrom->toDateString(),
            'dateTo'      => $dateTo->toDateString(),
            'totalAmount' => $totalAmount,
            'totalCount'  => $totalCount,
            'byCategory'  => $byCategory,
            'expenses'    => $expenses,
        ]);
    }
}
