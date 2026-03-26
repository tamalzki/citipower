<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    // ── ADD STOCK ──
    public function addStockForm(Product $product)
    {
        return view('inventory.add-stock', compact('product'));
    }

    public function addStock(Request $request, Product $product)
    {
        if (!auth()->user()->hasRole(['owner', 'inventory'])) {
            abort(403, 'Only owner or inventory user can add stock.');
        }

        $request->validate([
            'quantity_added' => 'required|integer|min:1',
            'note'           => 'nullable|string|max:255',
        ]);

        $previousStock = $product->stock_quantity;
        $newStock      = $previousStock + $request->quantity_added;

        $product->update(['stock_quantity' => $newStock]);

        InventoryLog::create([
            'product_id'     => $product->id,
            'type'           => 'add',
            'quantity'       => $request->quantity_added,
            'previous_stock' => $previousStock,
            'new_stock'      => $newStock,
            'note'           => $request->note,
        ]);

        return redirect()->route('products.index')
            ->with('success', "Stock added for {$product->name}. New stock: {$newStock}.");
    }

    // ── ADJUST STOCK ──
    public function adjustStockForm(Product $product)
    {
        return view('inventory.adjust-stock', compact('product'));
    }

    public function adjustStock(Request $request, Product $product)
    {
        if (!auth()->user()->hasRole(['owner', 'inventory'])) {
            abort(403, 'Only owner or inventory user can adjust stock.');
        }

        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason'       => 'nullable|string|max:255',
        ]);

        $previousStock = $product->stock_quantity;
        $newStock      = $request->new_quantity;

        $product->update(['stock_quantity' => $newStock]);

        InventoryLog::create([
            'product_id'     => $product->id,
            'type'           => 'adjust',
            'quantity'       => $newStock - $previousStock,
            'previous_stock' => $previousStock,
            'new_stock'      => $newStock,
            'note'           => $request->reason,
        ]);

        return redirect()->route('products.index')
            ->with('success', "Stock adjusted for {$product->name}. New stock: {$newStock}.");
    }

    // ── INVENTORY LOGS ──
    public function logs()
    {
        $logs = InventoryLog::with('product')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('inventory.logs', compact('logs'));
    }
}