<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\SupplierDelivery;
use App\Services\OfflineMutationApplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfflineSyncController extends Controller
{
    private const MAIN_BRANCH_LABEL = 'DAVAO -MAIN';

    public function syncMutations(Request $request, OfflineMutationApplier $applier)
    {
        $validated = $request->validate([
            'mutations' => 'required|array|min:1|max:100',
            'mutations.*.local_id' => 'required|string|max:100',
            'mutations.*.kind' => 'required|string|max:80',
            'mutations.*.payload' => 'required|array',
        ]);

        $user = $request->user();
        $synced = [];
        $failed = [];

        foreach ($validated['mutations'] as $entry) {
            try {
                $applier->apply($user, $entry['kind'], $entry['payload']);
                $synced[] = ['local_id' => $entry['local_id']];
            } catch (\Throwable $e) {
                $failed[] = [
                    'local_id' => $entry['local_id'] ?? 'unknown',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncSales(Request $request)
    {
        $validated = $request->validate([
            'sales' => 'required|array|min:1|max:100',
            'sales.*.local_id' => 'required|string|max:100',
            'sales.*.payload' => 'required|array',
            'sales.*.payload.items' => 'required|array|min:1',
            'sales.*.payload.items.*.product_id' => 'required|exists:products,id',
            'sales.*.payload.items.*.quantity' => 'required|integer|min:1',
            'sales.*.payload.discount_type' => 'nullable|in:percent,fixed',
            'sales.*.payload.discount_value' => 'nullable|numeric|min:0',
            'sales.*.payload.note' => 'nullable|string|max:255',
            'sales.*.payload.issued_receipt' => 'required|in:0,1',
            'sales.*.payload.poc' => 'nullable|string|max:100',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['sales'] as $entry) {
            try {
                $payload = $entry['payload'];
                $sale = DB::transaction(function () use ($payload) {
                    $subtotalAmount = 0;
                    $saleItemsData = [];

                    foreach ($payload['items'] as $item) {
                        $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                        $quantity = (int) $item['quantity'];
                        $price = (float) $product->selling_price;
                        $subtotal = $price * $quantity;

                        $previousStock = (int) $product->stock_quantity;
                        $newStock = $previousStock - $quantity;
                        $product->update(['stock_quantity' => $newStock]);

                        InventoryLog::create([
                            'product_id' => $product->id,
                            'type' => 'sale',
                            'quantity' => -$quantity,
                            'previous_stock' => $previousStock,
                            'new_stock' => $newStock,
                            'note' => 'Offline sync sale ('.self::MAIN_BRANCH_LABEL.')',
                        ]);

                        $subtotalAmount += $subtotal;
                        $saleItemsData[] = [
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $price,
                            'purchase_price' => $product->purchase_price,
                            'subtotal' => $subtotal,
                        ];
                    }

                    $discountType = $payload['discount_type'] ?? null;
                    $rawDiscountValue = (float) ($payload['discount_value'] ?? 0);
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
                        'total_amount' => $totalAmount,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'discount_amount' => $discountAmount,
                        'note' => $payload['note'] ?? null,
                        'issued_receipt' => (bool) ($payload['issued_receipt'] ?? false),
                        'poc' => $payload['poc'] ?? null,
                    ]);
                    foreach ($saleItemsData as $itemData) {
                        $sale->items()->create($itemData);
                    }

                    return $sale;
                });

                $synced[] = ['local_id' => $entry['local_id'], 'server_id' => $sale->id];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncSaleVoids(Request $request)
    {
        $validated = $request->validate([
            'sale_voids' => 'required|array|min:1|max:100',
            'sale_voids.*.local_id' => 'required|string|max:100',
            'sale_voids.*.payload' => 'required|array',
            'sale_voids.*.payload.sale_id' => 'required|integer',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['sale_voids'] as $entry) {
            try {
                $saleId = (int) ($entry['payload']['sale_id'] ?? 0);
                $sale = Sale::with('items.product')->find($saleId);
                if ($sale) {
                    DB::transaction(function () use ($sale) {
                        foreach ($sale->items as $item) {
                            $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                            $previousStock = (int) $product->stock_quantity;
                            $newStock = $previousStock + (int) $item->quantity;
                            $product->update(['stock_quantity' => $newStock]);

                            InventoryLog::create([
                                'product_id' => $product->id,
                                'type' => 'adjust',
                                'quantity' => (int) $item->quantity,
                                'previous_stock' => $previousStock,
                                'new_stock' => $newStock,
                                'note' => 'Offline void sale #'.$sale->id.' ('.self::MAIN_BRANCH_LABEL.')',
                            ]);
                        }
                        $sale->delete();
                    });
                }
                $synced[] = ['local_id' => $entry['local_id'], 'sale_id' => $saleId];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncSaleDeletes(Request $request)
    {
        $payload = $request->all();
        if (isset($payload['sale_deletes']) && ! isset($payload['sale_voids'])) {
            $payload['sale_voids'] = $payload['sale_deletes'];
        }
        $request->replace($payload);

        return $this->syncSaleVoids($request);
    }

    public function syncExpenses(Request $request)
    {
        $validated = $request->validate([
            'expenses' => 'required|array|min:1|max:100',
            'expenses.*.local_id' => 'required|string|max:100',
            'expenses.*.payload' => 'required|array',
            'expenses.*.payload.expense_category_id' => 'required|exists:expense_categories,id',
            'expenses.*.payload.expense_date' => 'required|date',
            'expenses.*.payload.reference_no' => 'nullable|string|max:100',
            'expenses.*.payload.amount' => 'required|numeric|min:0.01',
            'expenses.*.payload.vendor' => 'nullable|string|max:150',
            'expenses.*.payload.description' => 'nullable|string|max:1000',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['expenses'] as $entry) {
            try {
                $payload = $entry['payload'];
                Expense::create([
                    'expense_category_id' => $payload['expense_category_id'],
                    'expense_date' => $payload['expense_date'],
                    'reference_no' => $payload['reference_no'] ?? null,
                    'amount' => $payload['amount'],
                    'vendor' => $payload['vendor'] ?? null,
                    'description' => $payload['description'] ?? null,
                ]);
                $synced[] = ['local_id' => $entry['local_id']];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncExpenseUpdates(Request $request)
    {
        $validated = $request->validate([
            'expense_updates' => 'required|array|min:1|max:100',
            'expense_updates.*.local_id' => 'required|string|max:100',
            'expense_updates.*.payload' => 'required|array',
            'expense_updates.*.payload.expense_id' => 'required|integer',
            'expense_updates.*.payload.expense_category_id' => 'required|exists:expense_categories,id',
            'expense_updates.*.payload.expense_date' => 'required|date',
            'expense_updates.*.payload.reference_no' => 'nullable|string|max:100',
            'expense_updates.*.payload.amount' => 'required|numeric|min:0.01',
            'expense_updates.*.payload.vendor' => 'nullable|string|max:150',
            'expense_updates.*.payload.description' => 'nullable|string|max:1000',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['expense_updates'] as $entry) {
            try {
                $payload = $entry['payload'];
                $expenseId = (int) ($payload['expense_id'] ?? 0);
                $expense = Expense::find($expenseId);
                if ($expense) {
                    $expense->update([
                        'expense_category_id' => $payload['expense_category_id'],
                        'expense_date' => $payload['expense_date'],
                        'reference_no' => $payload['reference_no'] ?? null,
                        'amount' => $payload['amount'],
                        'vendor' => $payload['vendor'] ?? null,
                        'description' => $payload['description'] ?? null,
                    ]);
                }
                $synced[] = ['local_id' => $entry['local_id'], 'expense_id' => $expenseId];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncExpenseDeletes(Request $request)
    {
        $validated = $request->validate([
            'expense_deletes' => 'required|array|min:1|max:100',
            'expense_deletes.*.local_id' => 'required|string|max:100',
            'expense_deletes.*.payload' => 'required|array',
            'expense_deletes.*.payload.expense_id' => 'required|integer',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['expense_deletes'] as $entry) {
            try {
                $expenseId = (int) ($entry['payload']['expense_id'] ?? 0);
                $expense = Expense::find($expenseId);
                if ($expense) {
                    $expense->delete();
                }
                $synced[] = ['local_id' => $entry['local_id'], 'expense_id' => $expenseId];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncPurchaseOrders(Request $request)
    {
        $validated = $request->validate([
            'purchase_orders' => 'required|array|min:1|max:50',
            'purchase_orders.*.local_id' => 'required|string|max:100',
            'purchase_orders.*.payload' => 'required|array',
            'purchase_orders.*.payload.supplier_id' => 'required|exists:suppliers,id',
            'purchase_orders.*.payload.order_date' => 'required|date',
            'purchase_orders.*.payload.expected_arrival_date' => 'required|date',
            'purchase_orders.*.payload.payment_terms_count' => 'nullable|integer|min:1|max:60',
            'purchase_orders.*.payload.payment_terms_days' => 'nullable|integer|min:1|max:3650',
            'purchase_orders.*.payload.note' => 'nullable|string|max:255',
            'purchase_orders.*.payload.items' => 'required|array|min:1',
            'purchase_orders.*.payload.items.*.product_id' => 'required|exists:products,id',
            'purchase_orders.*.payload.items.*.quantity' => 'required|integer|min:1',
            'purchase_orders.*.payload.items.*.purchase_price' => 'required|numeric|min:0.01',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['purchase_orders'] as $entry) {
            try {
                $payload = $entry['payload'];
                if (strtotime((string) $payload['expected_arrival_date']) < strtotime((string) $payload['order_date'])) {
                    throw new \RuntimeException('Expected arrival / due date must be after or equal to order date.');
                }
                $po = DB::transaction(function () use ($payload) {
                    $lastId = PurchaseOrder::lockForUpdate()->max('id') ?? 0;
                    $poNumber = 'PO-'.now()->format('Ymd').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
                    $total = 0;
                    $purchaseOrder = PurchaseOrder::create([
                        'po_number' => $poNumber,
                        'supplier_id' => $payload['supplier_id'],
                        'order_date' => $payload['order_date'],
                        'expected_arrival_date' => $payload['expected_arrival_date'],
                        'payment_terms_count' => $payload['payment_terms_count'] ?? null,
                        'payment_terms_days' => $payload['payment_terms_days'] ?? null,
                        'status' => 'ordered',
                        'note' => $payload['note'] ?? null,
                        'total_amount' => 0,
                    ]);
                    foreach ($payload['items'] as $item) {
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

                    return $purchaseOrder;
                });
                $synced[] = ['local_id' => $entry['local_id'], 'purchase_order_id' => $po->id];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncPurchaseOrderUpdates(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_updates' => 'required|array|min:1|max:50',
            'purchase_order_updates.*.local_id' => 'required|string|max:100',
            'purchase_order_updates.*.payload' => 'required|array',
            'purchase_order_updates.*.payload.purchase_order_id' => 'required|integer',
            'purchase_order_updates.*.payload.supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_updates.*.payload.order_date' => 'required|date',
            'purchase_order_updates.*.payload.expected_arrival_date' => 'required|date',
            'purchase_order_updates.*.payload.payment_terms_count' => 'nullable|integer|min:1|max:60',
            'purchase_order_updates.*.payload.payment_terms_days' => 'nullable|integer|min:1|max:3650',
            'purchase_order_updates.*.payload.note' => 'nullable|string|max:255',
            'purchase_order_updates.*.payload.items' => 'required|array|min:1',
            'purchase_order_updates.*.payload.items.*.product_id' => 'required|exists:products,id',
            'purchase_order_updates.*.payload.items.*.quantity' => 'required|integer|min:1',
            'purchase_order_updates.*.payload.items.*.purchase_price' => 'required|numeric|min:0.01',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['purchase_order_updates'] as $entry) {
            try {
                $payload = $entry['payload'];
                if (strtotime((string) $payload['expected_arrival_date']) < strtotime((string) $payload['order_date'])) {
                    throw new \RuntimeException('Expected arrival / due date must be after or equal to order date.');
                }

                $poId = (int) ($payload['purchase_order_id'] ?? 0);
                $po = PurchaseOrder::find($poId);
                if ($po) {
                    if ($po->status !== 'ordered') {
                        throw new \RuntimeException('Purchase order is no longer ordered and cannot be updated.');
                    }
                    if (SupplierDelivery::where('purchase_order_id', $po->id)->exists() || $po->supplierPayments()->exists()) {
                        throw new \RuntimeException('Purchase order already has delivery/payment activity and cannot be updated.');
                    }

                    DB::transaction(function () use ($po, $payload) {
                        $total = 0;
                        $po->update([
                            'supplier_id' => $payload['supplier_id'],
                            'order_date' => $payload['order_date'],
                            'expected_arrival_date' => $payload['expected_arrival_date'],
                            'payment_terms_count' => $payload['payment_terms_count'] ?? null,
                            'payment_terms_days' => $payload['payment_terms_days'] ?? null,
                            'note' => $payload['note'] ?? null,
                        ]);
                        $po->items()->delete();
                        foreach ($payload['items'] as $item) {
                            $qty = (int) $item['quantity'];
                            $price = (float) $item['purchase_price'];
                            $subtotal = $qty * $price;
                            $total += $subtotal;
                            $po->items()->create([
                                'product_id' => $item['product_id'],
                                'quantity' => $qty,
                                'purchase_price' => $price,
                                'subtotal' => $subtotal,
                            ]);
                        }
                        $po->update(['total_amount' => $total]);
                    });
                }
                $synced[] = ['local_id' => $entry['local_id'], 'purchase_order_id' => $poId];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncPurchaseOrderDeletes(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_deletes' => 'required|array|min:1|max:50',
            'purchase_order_deletes.*.local_id' => 'required|string|max:100',
            'purchase_order_deletes.*.payload' => 'required|array',
            'purchase_order_deletes.*.payload.purchase_order_id' => 'required|integer',
        ]);

        $synced = [];
        $failed = [];

        foreach ($validated['purchase_order_deletes'] as $entry) {
            try {
                $poId = (int) ($entry['payload']['purchase_order_id'] ?? 0);
                $po = PurchaseOrder::find($poId);
                if ($po) {
                    if ($po->status !== 'ordered') {
                        throw new \RuntimeException('Purchase order is no longer ordered and cannot be deleted.');
                    }
                    if (SupplierDelivery::where('purchase_order_id', $po->id)->exists() || $po->supplierPayments()->exists()) {
                        throw new \RuntimeException('Purchase order already has delivery/payment activity and cannot be deleted.');
                    }
                    DB::transaction(function () use ($po) {
                        $po->items()->delete();
                        $po->delete();
                    });
                }
                $synced[] = ['local_id' => $entry['local_id'], 'purchase_order_id' => $poId];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }

    public function syncStockTransfers(Request $request)
    {
        $validated = $request->validate([
            'transfers' => 'required|array|min:1|max:100',
            'transfers.*.local_id' => 'required|string|max:100',
            'transfers.*.payload' => 'required|array',
            'transfers.*.payload.note' => 'nullable|string|max:500',
            'transfers.*.payload.items' => 'required|array|min:1',
            'transfers.*.payload.items.*.product_id' => 'required|exists:products,id',
            'transfers.*.payload.items.*.quantity' => 'required|integer|min:1',
        ]);

        $synced = [];
        $failed = [];

        $main = Branch::firstOrCreate(['code' => 'DAV-MAIN'], ['name' => 'DAVAO -MAIN']);
        $second = Branch::firstOrCreate(['code' => 'DIG-SECOND'], ['name' => 'DIGOS -SECOND']);
        if ($main->name !== 'DAVAO -MAIN') {
            $main->update(['name' => 'DAVAO -MAIN']);
        }
        if ($second->name !== 'DIGOS -SECOND') {
            $second->update(['name' => 'DIGOS -SECOND']);
        }

        foreach ($validated['transfers'] as $entry) {
            try {
                $payload = $entry['payload'];
                foreach ($payload['items'] as $item) {
                    StockTransfer::create([
                        'product_id' => $item['product_id'],
                        'from_branch_id' => $main->id,
                        'to_branch_id' => $second->id,
                        'quantity' => (int) $item['quantity'],
                        'note' => $payload['note'] ?? null,
                        'transferred_by' => auth()->id(),
                    ]);
                }
                $synced[] = ['local_id' => $entry['local_id']];
            } catch (\Throwable $e) {
                $failed[] = ['local_id' => $entry['local_id'] ?? 'unknown', 'message' => $e->getMessage()];
            }
        }

        return response()->json(compact('synced', 'failed'));
    }
}
