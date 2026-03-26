<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierDelivery;
use App\Models\SupplierPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $purchaseOrders = PurchaseOrder::with(['supplier', 'supplierPayments'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->whereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                        ->orWhere('dr_number', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                    if (ctype_digit($search)) {
                        $q2->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('purchase-orders.index', compact('purchaseOrders', 'search'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchase-orders.create', compact('suppliers'));
    }

    public function productsJson(Request $request)
    {
        $search = $request->string('search')->toString();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = Product::orderBy('name')
            ->select(['id', 'name', 'sku', 'purchase_price', 'stock_quantity'])
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('sku', 'like', "%{$search}%");
            }));

        $total = $query->count();
        $products = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $products,
            'page' => $page,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'expected_arrival_date' => $request->input('expected_arrival_date') ?: null,
        ]);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_arrival_date' => 'nullable|date|after_or_equal:order_date',
            'payment_terms_count' => 'nullable|integer|min:1|max:60',
            'payment_terms_days' => 'nullable|integer|min:1|max:3650',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            // Lock the table row to safely compute the next sequential number
            $lastId   = PurchaseOrder::lockForUpdate()->max('id') ?? 0;
            $poNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            $total = 0;

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_arrival_date' => $request->filled('expected_arrival_date') ? $request->expected_arrival_date : null,
                'payment_terms_count' => $request->filled('payment_terms_count') ? (int) $request->payment_terms_count : null,
                'payment_terms_days' => $request->filled('payment_terms_days') ? (int) $request->payment_terms_days : null,
                'status' => 'ordered',
                'note' => $request->note,
                'total_amount' => 0,
            ]);

            foreach ($request->items as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['purchase_price'];
                $subtotal = $qty * $price;
                $total += $subtotal;

                $purchaseOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $purchaseOrder->update(['total_amount' => $total]);
        });

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('supplier', 'items.product', 'supplierPayments');
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function itemsJson(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load('supplier', 'items.product');

        return response()->json([
            'po_number'    => $purchaseOrder->po_number,
            'supplier'     => $purchaseOrder->supplier?->name,
            'order_date'   => $purchaseOrder->order_date->format('M d, Y'),
            'status'       => $purchaseOrder->status,
            'dr_number'    => $purchaseOrder->dr_number,
            'arrival_date' => $purchaseOrder->arrival_date?->format('Y-m-d'),
            'items'        => $purchaseOrder->items->map(fn ($item) => [
                'id'             => $item->id,
                'product_name'   => $item->product?->name ?? '—',
                'sku'            => $item->product?->sku ?? '',
                'ordered_qty'    => (int) $item->quantity,
                'purchase_price' => (float) $item->purchase_price,
                'subtotal'       => (float) $item->subtotal,
            ]),
        ]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->with('error', 'Purchase order already received.');
        }

        $request->merge([
            'arrival_date' => $request->input('arrival_date') ?: null,
        ]);

        $request->validate([
            'dr_number'     => 'required|string|max:100',
            'arrival_date'  => 'nullable|date',
            'arrival_notes' => 'nullable|string|max:500',
        ]);

        $purchaseOrder->load('items.product');

        $quantities = collect($request->input('quantities', []))
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => max(0, (int) $qty)])
            ->filter(fn ($qty) => $qty > 0);

        if ($quantities->isEmpty()) {
            return back()->with('error', 'Enter at least one item quantity to receive.');
        }

        DB::transaction(function () use ($request, $purchaseOrder, $quantities) {
            foreach ($purchaseOrder->items as $item) {
                $qty = $quantities->get($item->id, 0);
                if ($qty <= 0) continue;

                $receiveQty = min($qty, (int) $item->quantity);

                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $previousStock = (int) $product->stock_quantity;
                $newStock      = $previousStock + $receiveQty;

                $product->update([
                    'stock_quantity' => $newStock,
                    'purchase_price' => $item->purchase_price,
                ]);

                InventoryLog::create([
                    'product_id'     => $product->id,
                    'type'           => 'add',
                    'quantity'       => $receiveQty,
                    'previous_stock' => $previousStock,
                    'new_stock'      => $newStock,
                    'note'           => 'Received ' . $purchaseOrder->po_number . ' DR#' . $request->dr_number,
                ]);
            }

            $purchaseOrder->update([
                'status'        => 'received',
                'received_at'   => now(),
                'dr_number'     => $request->dr_number,
                'arrival_date'  => $request->arrival_date,
                'arrival_notes' => $request->arrival_notes,
            ]);

            // Auto-create Supplier Ledger delivery entry
            SupplierDelivery::create([
                'supplier_id'       => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->id,
                'dr_number'         => $request->dr_number,
                'delivery_date'     => $request->arrival_date ?: now()->toDateString(),
                'amount'            => $purchaseOrder->total_amount,
                'notes'             => $request->arrival_notes,
            ]);
        });

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Items received and inventory updated.');
    }

    public function recordPayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'payment_date'   => 'required|date',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,check,bank_transfer,e_wallet,other',
            'reference_no'   => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ]);

        SupplierPayment::create([
            'supplier_id'       => $purchaseOrder->supplier_id,
            'purchase_order_id' => $purchaseOrder->id,
            'payment_date'      => $request->payment_date,
            'amount'            => $request->amount,
            'payment_method'    => $request->payment_method,
            'reference_no'      => $request->reference_no,
            'notes'             => $request->notes
                                     ?: 'Supplier payment (purchase order)',
        ]);

        return redirect()->back()
            ->with('success', 'Payment recorded and reflected in Supplier Ledger.');
    }
}
