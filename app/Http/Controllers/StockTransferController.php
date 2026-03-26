<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $search     = $request->get('search');
        $branchId   = $request->get('branch_id');
        $dateFrom   = $request->get('date_from');
        $dateTo     = $request->get('date_to');

        $transfers = StockTransfer::with(['product', 'fromBranch', 'toBranch', 'transferredBy'])
            ->when($search, fn($q) => $q->whereHas('product', fn($q2) =>
                $q2->where('name', 'like', "%{$search}%")
            ))
            ->when($branchId, fn($q) =>
                $q->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId)
            )
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get();

        return view('stock-transfers.index', compact('transfers', 'branches', 'search', 'branchId', 'dateFrom', 'dateTo'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('stock-transfers.create', compact('branches', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id'   => 'required|exists:branches,id|different:from_branch_id',
            'quantity'       => 'required|integer|min:1',
            'note'           => 'nullable|string|max:500',
        ]);

        StockTransfer::create([
            'product_id'     => $request->product_id,
            'from_branch_id' => $request->from_branch_id,
            'to_branch_id'   => $request->to_branch_id,
            'quantity'       => $request->quantity,
            'note'           => $request->note,
            'transferred_by' => auth()->id(),
        ]);

        return redirect()->route('stock-transfers.index')
            ->with('success', 'Stock transfer recorded successfully.');
    }
}
