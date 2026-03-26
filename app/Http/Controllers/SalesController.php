<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    private const MAIN_BRANCH_LABEL = 'DAVAO -MAIN';

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $sales = Sale::with('items.product', 'payments')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('id', 'like', "%{$search}%")
                        ->orWhere('poc', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('items.product', function ($qp) use ($search) {
                            $qp->where('name', 'like', "%{$search}%")
                               ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales', 'search'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole(['owner', 'cashier'])) {
            abort(403, 'Only owner or cashier can create sales.');
        }

        $products = Product::orderBy('name')->get();
        return view('sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'discount_type'      => 'nullable|in:percent,fixed',
            'discount_value'     => 'nullable|numeric|min:0',
            'note'               => 'nullable|string|max:255',
            'issued_receipt'     => 'required|in:0,1',
            'poc'                => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $subtotalAmount = 0;
            $saleItemsData = [];

            foreach ($request->items as $item) {
                $product  = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                $quantity = (int) $item['quantity'];
                $price    = $product->selling_price;
                $subtotal = $price * $quantity;

                $previousStock = $product->stock_quantity;
                $newStock      = $previousStock - $quantity;

                // Sales always deduct from main-branch product stock.
                $product->update(['stock_quantity' => $newStock]);

                // Inventory log
                InventoryLog::create([
                    'product_id'     => $product->id,
                    'type'           => 'sale',
                    'quantity'       => -$quantity,
                    'previous_stock' => $previousStock,
                    'new_stock'      => $newStock,
                    'note'           => 'Sale recorded (' . self::MAIN_BRANCH_LABEL . ')',
                ]);

                $subtotalAmount += $subtotal;

                $saleItemsData[] = [
                    'product_id'     => $product->id,
                    'quantity'       => $quantity,
                    'price'          => $price,
                    'purchase_price' => $product->purchase_price,
                    'subtotal'       => $subtotal,
                ];
            }

            $discountType = $request->discount_type;
            $rawDiscountValue = (float) ($request->discount_value ?? 0);
            $discountValue = 0;
            $discountAmount = 0;

            if ($discountType === 'percent' && $rawDiscountValue > 0) {
                $discountValue = min($rawDiscountValue, 100);
                $discountAmount = $subtotalAmount * ($discountValue / 100);
            } elseif ($discountType === 'fixed' && $rawDiscountValue > 0) {
                $discountValue = $rawDiscountValue;
                $discountAmount = min($rawDiscountValue, $subtotalAmount);
            } else {
                $discountType = null;
            }

            $totalAmount = max(0, $subtotalAmount - $discountAmount);

            $sale = Sale::create([
                'total_amount'   => $totalAmount,
                'discount_type'  => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'note'           => $request->note,
                'issued_receipt' => $request->boolean('issued_receipt'),
                'poc'            => $request->poc,
            ]);

            foreach ($saleItemsData as $itemData) {
                $sale->items()->create($itemData);
            }

        });

        return redirect()->route('sales.index')
            ->with('success', 'Sale recorded successfully.');
    }

    public function show(Sale $sale)
    {
        $sale->load('items.product', 'payments');
        return view('sales.show', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        if (!auth()->user()->hasRole('owner')) {
            abort(403, 'Only owner can void sales.');
        }

        DB::transaction(function () use ($sale) {
            // Restore stock
            foreach ($sale->items as $item) {
                $product       = $item->product;
                $previousStock = $product->stock_quantity;
                $newStock      = $previousStock + $item->quantity;

                $product->update(['stock_quantity' => $newStock]);

                InventoryLog::create([
                    'product_id'     => $product->id,
                    'type'           => 'adjust',
                    'quantity'       => $item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock'      => $newStock,
                    'note'           => 'Sale #' . $sale->id . ' voided (' . self::MAIN_BRANCH_LABEL . ')',
                ]);
            }

            $sale->delete();
        });

        return redirect()->route('sales.index')
            ->with('success', 'Sale voided and stock restored.');
    }
}