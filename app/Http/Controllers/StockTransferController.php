<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    private function ensureFixedBranches(): array
    {
        $main = Branch::firstOrCreate(
            ['code' => 'DAV-MAIN'],
            ['name' => 'DAVAO -MAIN']
        );

        $second = Branch::firstOrCreate(
            ['code' => 'DIG-SECOND'],
            ['name' => 'DIGOS -SECOND']
        );

        // Keep naming fixed even if edited before
        if ($main->name !== 'DAVAO -MAIN') {
            $main->update(['name' => 'DAVAO -MAIN']);
        }
        if ($second->name !== 'DIGOS -SECOND') {
            $second->update(['name' => 'DIGOS -SECOND']);
        }

        return [$main, $second];
    }

    public function index(Request $request)
    {
        [$mainBranch, $secondBranch] = $this->ensureFixedBranches();
        $search = $request->get('search');

        $transfers = StockTransfer::with(['product', 'fromBranch', 'toBranch', 'transferredBy'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('product', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('fromBranch', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('toBranch',   fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $products = Product::orderBy('name')
            ->get(['id', 'name', 'sku', 'stock_quantity']);

        $toSecond = StockTransfer::where('to_branch_id', $secondBranch->id)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $fromSecond = StockTransfer::where('from_branch_id', $secondBranch->id)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $branchStocks = $products->map(function ($p) use ($toSecond, $fromSecond) {
            $secondQty = max(0, (int) ($toSecond[$p->id] ?? 0) - (int) ($fromSecond[$p->id] ?? 0));
            $mainQty = (int) $p->stock_quantity;
            return [
                'product' => $p,
                'main_qty' => $mainQty,
                'second_qty' => $secondQty,
                'total_qty' => $mainQty + $secondQty,
            ];
        });

        $branchTotals = [
            'main' => $branchStocks->sum('main_qty'),
            'second' => $branchStocks->sum('second_qty'),
            'all' => $branchStocks->sum('total_qty'),
        ];

        $mainBranchProducts = $branchStocks->map(fn ($row) => [
            'name' => $row['product']->name,
            'sku' => $row['product']->sku,
            'qty' => $row['main_qty'],
        ])->sortBy('name')->values();

        $secondBranchProducts = $branchStocks->map(fn ($row) => [
            'name' => $row['product']->name,
            'sku' => $row['product']->sku,
            'qty' => $row['second_qty'],
        ])->sortBy('name')->values();

        return view('stock-transfers.index', compact(
            'transfers',
            'search',
            'mainBranch',
            'secondBranch',
            'branchStocks',
            'branchTotals',
            'mainBranchProducts',
            'secondBranchProducts'
        ));
    }

    public function create()
    {
        [$mainBranch, $secondBranch] = $this->ensureFixedBranches();
        $products = Product::orderBy('name')->get();
        return view('stock-transfers.create', compact('products', 'mainBranch', 'secondBranch'));
    }

    public function store(Request $request)
    {
        [$mainBranch, $secondBranch] = $this->ensureFixedBranches();

        $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|distinct|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'note'           => 'nullable|string|max:500',
        ]);

        foreach ($request->items as $item) {
            StockTransfer::create([
                'product_id'     => $item['product_id'],
                'from_branch_id' => $mainBranch->id,
                'to_branch_id'   => $secondBranch->id,
                'quantity'       => (int) $item['quantity'],
                'note'           => $request->note,
                'transferred_by' => auth()->id(),
            ]);
        }

        return redirect()->route('stock-transfers.index')
            ->with('success', 'Stock transfer(s) recorded successfully.');
    }
}
