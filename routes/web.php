<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierLedgerController;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        $today      = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd   = now()->endOfMonth()->toDateString();

        $totalProducts   = Product::count();
        $outOfStockCount = Product::where('stock_quantity', '<=', 0)->count();
        $lowStockCount   = Product::whereColumn('stock_quantity', '<=', 'minimum_stock')
                               ->where('stock_quantity', '>', 0)->count();
        $totalStockUnits = (int) Product::sum('stock_quantity');

        $todaySales  = (float) Sale::whereDate('created_at', $today)->sum('total_amount');
        $monthSales  = (float) Sale::whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year)->sum('total_amount');

        $todayExpenses  = (float) Expense::whereDate('expense_date', $today)->sum('amount');
        $monthExpenses  = (float) Expense::whereMonth('expense_date', now()->month)
                              ->whereYear('expense_date', now()->year)->sum('amount');

        $recentSales = Sale::orderByDesc('created_at')->limit(5)->get();
        $recentExpenses = Expense::with('category')->orderByDesc('expense_date')->orderByDesc('id')->limit(5)->get();

        return view('dashboard', compact(
            'totalProducts',
            'lowStockCount',
            'outOfStockCount',
            'totalStockUnits',
            'todaySales',
            'monthSales',
            'todayExpenses',
            'monthExpenses',
            'recentSales',
            'recentExpenses'
        ));
    })->name('dashboard');

    // Products
    Route::resource('products', ProductController::class)
        ->middleware('role:owner,inventory');

    // Inventory Actions
    Route::middleware('role:owner,inventory')->group(function () {
        Route::get('/products/{product}/add-stock', [InventoryController::class, 'addStockForm'])->name('inventory.add-stock');
        Route::post('/products/{product}/add-stock', [InventoryController::class, 'addStock']);
        Route::get('/products/{product}/adjust-stock', [InventoryController::class, 'adjustStockForm'])->name('inventory.adjust-stock');
        Route::post('/products/{product}/adjust-stock', [InventoryController::class, 'adjustStock']);
    });

    // Inventory Logs
    Route::get('/inventory-logs', [InventoryController::class, 'logs'])
        ->middleware('role:owner,inventory')
        ->name('inventory-logs.index');

    // Sales
    Route::resource('sales', SalesController::class)
        ->except(['edit', 'update'])
        ->middleware('role:owner,cashier');
    Route::post('/sales/{sale}/payments', [PaymentController::class, 'store'])
        ->middleware('role:owner,cashier')
        ->name('sales.payments.store');
    Route::delete('/sales/{sale}/payments/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('role:owner')
        ->name('sales.payments.destroy');

    // Expenses
    Route::resource('expenses', ExpenseController::class)
        ->except(['show'])
        ->middleware('role:owner,cashier');
    Route::resource('expense-categories', ExpenseCategoryController::class)
        ->except(['show'])
        ->middleware('role:owner');

    // Procurement
    Route::resource('suppliers', SupplierController::class)
        ->except(['show'])
        ->middleware('role:owner,inventory');
    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('role:owner,inventory');
    Route::get('/purchase-orders-products', [PurchaseOrderController::class, 'productsJson'])
        ->middleware('role:owner,inventory')
        ->name('purchase-orders.products-json');
    Route::get('/purchase-orders/{purchase_order}/items-json', [PurchaseOrderController::class, 'itemsJson'])
        ->middleware('role:owner,inventory')
        ->name('purchase-orders.items-json');
    Route::post('/purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])
        ->middleware('role:owner,inventory')
        ->name('purchase-orders.receive');

    // Branches & Stock Transfers
    Route::resource('branches', BranchController::class)
        ->except(['show'])
        ->middleware('role:owner');
    Route::resource('stock-transfers', StockTransferController::class)
        ->only(['index', 'create', 'store'])
        ->middleware('role:owner,inventory');

    // Supplier Ledger
    Route::middleware('role:owner')->group(function () {
        Route::get('/supplier-ledger', [SupplierLedgerController::class, 'index'])->name('supplier-ledger.index');
        Route::get('/supplier-ledger/{supplier}', [SupplierLedgerController::class, 'show'])->name('supplier-ledger.show');
        Route::post('/supplier-ledger/{supplier}/deliveries', [SupplierLedgerController::class, 'storeDelivery'])->name('supplier-ledger.store-delivery');
        Route::post('/supplier-ledger/{supplier}/payments', [SupplierLedgerController::class, 'storePayment'])->name('supplier-ledger.store-payment');
        Route::delete('/supplier-ledger/{supplier}/deliveries/{delivery}', [SupplierLedgerController::class, 'destroyDelivery'])->name('supplier-ledger.destroy-delivery');
        Route::delete('/supplier-ledger/{supplier}/payments/{payment}', [SupplierLedgerController::class, 'destroyPayment'])->name('supplier-ledger.destroy-payment');
    });

    // Reports
    Route::middleware('role:owner')->group(function () {
        Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    });

    // User management (owner only)
    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('role:owner');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';