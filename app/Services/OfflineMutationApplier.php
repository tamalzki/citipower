<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\SupplierDelivery;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfflineMutationApplier
{
    private const MAIN_BRANCH_LABEL = 'DAVAO -MAIN';

    /** @var array<string, list<string>> */
    private const KIND_ROLES = [
        'sale_create' => ['owner', 'cashier'],
        'sale_void' => ['owner'],
        'expense_create' => ['owner', 'cashier'],
        'expense_update' => ['owner', 'cashier'],
        'expense_delete' => ['owner'],
        'expense_category_create' => ['owner'],
        'expense_category_update' => ['owner'],
        'expense_category_delete' => ['owner'],
        'product_create' => ['owner', 'inventory'],
        'product_update' => ['owner', 'inventory'],
        'product_delete' => ['owner', 'inventory'],
        'inventory_add_stock' => ['owner', 'inventory'],
        'inventory_adjust_stock' => ['owner', 'inventory'],
        'supplier_create' => ['owner', 'inventory'],
        'supplier_update' => ['owner', 'inventory'],
        'supplier_delete' => ['owner', 'inventory'],
        'branch_create' => ['owner'],
        'branch_update' => ['owner'],
        'branch_delete' => ['owner'],
        'user_update' => ['owner'],
        'user_delete' => ['owner'],
        'profile_update' => ['owner', 'cashier', 'inventory'],
        'po_create' => ['owner', 'inventory'],
        'po_update' => ['owner', 'inventory'],
        'po_delete' => ['owner', 'inventory'],
        'po_receive' => ['owner', 'inventory'],
        'po_record_payment' => ['owner', 'inventory'],
        'stock_transfer_create' => ['owner', 'inventory'],
        'sale_payment_create' => ['owner', 'cashier'],
        'sale_payment_delete' => ['owner'],
        'ledger_delivery_create' => ['owner'],
        'ledger_payment_create' => ['owner'],
        'ledger_delivery_delete' => ['owner'],
        'ledger_payment_delete' => ['owner'],
    ];

    public function apply(Authenticatable $user, string $kind, array $payload): void
    {
        if (! isset(self::KIND_ROLES[$kind])) {
            throw ValidationException::withMessages(['kind' => ['Unknown mutation kind.']]);
        }
        $roles = self::KIND_ROLES[$kind];
        if (! $user instanceof User || ! in_array($user->role, $roles, true)) {
            throw new \RuntimeException('You are not authorized for this offline mutation.');
        }

        match ($kind) {
            'sale_create' => $this->saleCreate($payload),
            'sale_void' => $this->saleVoid($payload),
            'expense_create' => $this->expenseCreate($payload),
            'expense_update' => $this->expenseUpdate($payload),
            'expense_delete' => $this->expenseDelete($payload),
            'expense_category_create' => $this->expenseCategoryCreate($payload),
            'expense_category_update' => $this->expenseCategoryUpdate($payload),
            'expense_category_delete' => $this->expenseCategoryDelete($payload),
            'product_create' => $this->productCreate($payload),
            'product_update' => $this->productUpdate($payload),
            'product_delete' => $this->productDelete($payload),
            'inventory_add_stock' => $this->inventoryAddStock($payload),
            'inventory_adjust_stock' => $this->inventoryAdjustStock($payload),
            'supplier_create' => $this->supplierCreate($payload),
            'supplier_update' => $this->supplierUpdate($payload),
            'supplier_delete' => $this->supplierDelete($payload),
            'branch_create' => $this->branchCreate($payload),
            'branch_update' => $this->branchUpdate($payload),
            'branch_delete' => $this->branchDelete($payload),
            'user_update' => $this->userUpdate($payload),
            'user_delete' => $this->userDelete($payload, $user),
            'profile_update' => $this->profileUpdate($user, $payload),
            'po_create' => $this->poCreate($payload),
            'po_update' => $this->poUpdate($payload),
            'po_delete' => $this->poDelete($payload),
            'po_receive' => $this->poReceive($payload),
            'po_record_payment' => $this->poRecordPayment($payload),
            'stock_transfer_create' => $this->stockTransferCreate($payload, $user),
            'sale_payment_create' => $this->salePaymentCreate($payload),
            'sale_payment_delete' => $this->salePaymentDelete($payload),
            'ledger_delivery_create' => $this->ledgerDeliveryCreate($payload),
            'ledger_payment_create' => $this->ledgerPaymentCreate($payload),
            'ledger_delivery_delete' => $this->ledgerDeliveryDelete($payload),
            'ledger_payment_delete' => $this->ledgerPaymentDelete($payload),
            default => throw ValidationException::withMessages(['kind' => ['Unsupported kind.']]),
        };
    }

    private function saleCreate(array $p): void
    {
        $v = Validator::make($p, [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'issued_receipt' => 'required|in:0,1',
            'poc' => 'nullable|string|max:100',
        ])->validate();

        DB::transaction(function () use ($v) {
            $subtotalAmount = 0;
            $saleItemsData = [];
            foreach ($v['items'] as $item) {
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
            $discountType = $v['discount_type'] ?? null;
            $rawDiscountValue = (float) ($v['discount_value'] ?? 0);
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
                'note' => $v['note'] ?? null,
                'issued_receipt' => (bool) ($v['issued_receipt'] ?? false),
                'poc' => $v['poc'] ?? null,
            ]);
            foreach ($saleItemsData as $row) {
                $sale->items()->create($row);
            }
        });
    }

    private function saleVoid(array $p): void
    {
        Validator::make($p, ['sale_id' => 'required|integer'])->validate();
        $saleId = (int) $p['sale_id'];
        $sale = Sale::with('items.product')->find($saleId);
        if (! $sale) {
            return;
        }
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

    private function expenseCreate(array $p): void
    {
        $v = Validator::make($p, [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'vendor' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:1000',
        ])->validate();
        Expense::create([
            'expense_category_id' => $v['expense_category_id'],
            'expense_date' => $v['expense_date'],
            'reference_no' => $v['reference_no'] ?? null,
            'amount' => $v['amount'],
            'vendor' => $v['vendor'] ?? null,
            'description' => $v['description'] ?? null,
        ]);
    }

    private function expenseUpdate(array $p): void
    {
        $v = Validator::make($p, [
            'expense_id' => 'required|integer',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'vendor' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:1000',
        ])->validate();
        $expense = Expense::find((int) $v['expense_id']);
        if ($expense) {
            $expense->update([
                'expense_category_id' => $v['expense_category_id'],
                'expense_date' => $v['expense_date'],
                'reference_no' => $v['reference_no'] ?? null,
                'amount' => $v['amount'],
                'vendor' => $v['vendor'] ?? null,
                'description' => $v['description'] ?? null,
            ]);
        }
    }

    private function expenseDelete(array $p): void
    {
        Validator::make($p, ['expense_id' => 'required|integer'])->validate();
        $e = Expense::find((int) $p['expense_id']);
        if ($e) {
            $e->delete();
        }
    }

    private function expenseCategoryCreate(array $p): void
    {
        $v = Validator::make($p, [
            'name' => 'required|string|max:100|unique:expense_categories,name',
            'description' => 'nullable|string|max:255',
        ])->validate();
        ExpenseCategory::create(['name' => $v['name'], 'description' => $v['description'] ?? null]);
    }

    private function expenseCategoryUpdate(array $p): void
    {
        $v = Validator::make($p, [
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ])->validate();
        $cat = ExpenseCategory::findOrFail((int) $v['expense_category_id']);
        Validator::make($v, [
            'name' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'name')->ignore($cat->id)],
        ])->validate();
        $cat->update(['name' => $v['name'], 'description' => $v['description'] ?? null]);
    }

    private function expenseCategoryDelete(array $p): void
    {
        Validator::make($p, ['expense_category_id' => 'required|integer'])->validate();
        $cat = ExpenseCategory::find((int) $p['expense_category_id']);
        if ($cat && $cat->expenses()->exists()) {
            throw new \RuntimeException('Cannot delete category with existing expenses.');
        }
        if ($cat) {
            $cat->delete();
        }
    }

    private function syncProductSuppliers(Product $product, array $supplierIds, array $costs): void
    {
        if (empty($supplierIds)) {
            $product->suppliers()->detach();

            return;
        }
        $syncData = [];
        foreach ($supplierIds as $index => $supplierId) {
            $syncData[$supplierId] = ['cost_price' => (float) ($costs[$index] ?? 0)];
        }
        $product->suppliers()->sync($syncData);
    }

    private function productCreate(array $p): void
    {
        $v = Validator::make($p, [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'brand' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'supplier_costs' => 'nullable|array',
            'supplier_costs.*' => 'nullable|numeric|min:0',
        ])->validate();
        $product = Product::create(collect($v)->only([
            'name', 'sku', 'brand', 'category', 'model', 'description',
            'purchase_price', 'selling_price', 'stock_quantity', 'minimum_stock',
        ])->all());
        $this->syncProductSuppliers($product, $v['supplier_ids'] ?? [], $v['supplier_costs'] ?? []);
    }

    private function productUpdate(array $p): void
    {
        $v = Validator::make($p, [
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'supplier_costs' => 'nullable|array',
            'supplier_costs.*' => 'nullable|numeric|min:0',
        ])->validate();
        $product = Product::findOrFail((int) $v['product_id']);
        Validator::make($v, [
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
        ])->validate();
        $product->update(collect($v)->only([
            'name', 'sku', 'brand', 'category', 'model', 'description',
            'purchase_price', 'selling_price', 'stock_quantity', 'minimum_stock',
        ])->all());
        $this->syncProductSuppliers($product, $v['supplier_ids'] ?? [], $v['supplier_costs'] ?? []);
    }

    private function productDelete(array $p): void
    {
        Validator::make($p, ['product_id' => 'required|integer'])->validate();
        $product = Product::find((int) $p['product_id']);
        if ($product) {
            $product->delete();
        }
    }

    private function inventoryAddStock(array $p): void
    {
        $v = Validator::make($p, [
            'product_id' => 'required|integer|exists:products,id',
            'quantity_added' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ])->validate();
        $product = Product::findOrFail((int) $v['product_id']);
        $previousStock = (int) $product->stock_quantity;
        $newStock = $previousStock + (int) $v['quantity_added'];
        $product->update(['stock_quantity' => $newStock]);
        InventoryLog::create([
            'product_id' => $product->id,
            'type' => 'add',
            'quantity' => (int) $v['quantity_added'],
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'note' => $v['note'] ?? null,
        ]);
    }

    private function inventoryAdjustStock(array $p): void
    {
        $v = Validator::make($p, [
            'product_id' => 'required|integer|exists:products,id',
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ])->validate();
        $product = Product::findOrFail((int) $v['product_id']);
        $previousStock = (int) $product->stock_quantity;
        $newStock = (int) $v['new_quantity'];
        $product->update(['stock_quantity' => $newStock]);
        InventoryLog::create([
            'product_id' => $product->id,
            'type' => 'adjust',
            'quantity' => $newStock - $previousStock,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'note' => $v['reason'] ?? null,
        ]);
    }

    private function supplierCreate(array $p): void
    {
        $v = Validator::make($p, [
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ])->validate();
        Supplier::create(collect($v)->only(['name', 'contact_person', 'phone', 'email', 'address'])->all());
    }

    private function supplierUpdate(array $p): void
    {
        $v = Validator::make($p, [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ])->validate();
        Supplier::findOrFail((int) $v['supplier_id'])->update(collect($v)->only(['name', 'contact_person', 'phone', 'email', 'address'])->all());
    }

    private function supplierDelete(array $p): void
    {
        Validator::make($p, ['supplier_id' => 'required|integer'])->validate();
        $s = Supplier::find((int) $p['supplier_id']);
        if ($s && $s->purchaseOrders()->exists()) {
            throw new \RuntimeException('Cannot delete supplier with purchase orders.');
        }
        if ($s) {
            $s->delete();
        }
    }

    private function branchCreate(array $p): void
    {
        $v = Validator::make($p, [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:branches,code',
        ])->validate();
        Branch::create(['name' => $v['name'], 'code' => $v['code']]);
    }

    private function branchUpdate(array $p): void
    {
        $v = Validator::make($p, [
            'branch_id' => 'required|integer|exists:branches,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
        ])->validate();
        $branch = Branch::findOrFail((int) $v['branch_id']);
        Validator::make($v, [
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch->id)],
        ])->validate();
        $branch->update(['name' => $v['name'], 'code' => $v['code']]);
    }

    private function branchDelete(array $p): void
    {
        Validator::make($p, ['branch_id' => 'required|integer'])->validate();
        $b = Branch::find((int) $p['branch_id']);
        if ($b) {
            $b->delete();
        }
    }

    private function userUpdate(array $p): void
    {
        $v = Validator::make($p, [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'role' => 'required|in:owner,cashier,inventory',
        ])->validate();
        $user = User::findOrFail((int) $v['user_id']);
        Validator::make($v, [
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ])->validate();
        if ($user->role === 'owner' && $v['role'] !== 'owner') {
            $ownerCount = User::where('role', 'owner')->count();
            if ($ownerCount <= 1) {
                throw new \RuntimeException('Cannot change role — this is the only owner account.');
            }
        }
        $user->update([
            'name' => $v['name'],
            'email' => $v['email'],
            'role' => $v['role'],
        ]);
    }

    private function userDelete(array $p, Authenticatable $actor): void
    {
        Validator::make($p, ['user_id' => 'required|integer'])->validate();
        $userId = (int) $p['user_id'];
        if ($actor instanceof User && $userId === (int) $actor->id) {
            throw new \RuntimeException('You cannot delete your own account.');
        }
        $user = User::find($userId);
        if (! $user) {
            return;
        }
        if ($user->role === 'owner' && User::where('role', 'owner')->count() <= 1) {
            throw new \RuntimeException('Cannot delete the only owner account.');
        }
        $user->delete();
    }

    private function profileUpdate(Authenticatable $user, array $p): void
    {
        if (! $user instanceof User) {
            throw new \RuntimeException('Invalid user for profile update.');
        }
        $v = Validator::make($p, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ])->validate();
        $user->fill($v);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();
    }

    private function poCreate(array $p): void
    {
        $v = Validator::make($p, [
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_arrival_date' => 'required|date',
            'payment_terms_count' => 'nullable|integer|min:1|max:60',
            'payment_terms_days' => 'nullable|integer|min:1|max:3650',
            'note' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0.01',
        ])->validate();
        if (strtotime((string) $v['expected_arrival_date']) < strtotime((string) $v['order_date'])) {
            throw new \RuntimeException('Expected arrival / due date must be after or equal to order date.');
        }
        DB::transaction(function () use ($v) {
            $lastId = PurchaseOrder::lockForUpdate()->max('id') ?? 0;
            $poNumber = 'PO-'.now()->format('Ymd').'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            $total = 0;
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $v['supplier_id'],
                'order_date' => $v['order_date'],
                'expected_arrival_date' => $v['expected_arrival_date'],
                'payment_terms_count' => $v['payment_terms_count'] ?? null,
                'payment_terms_days' => $v['payment_terms_days'] ?? null,
                'status' => 'ordered',
                'note' => $v['note'] ?? null,
                'total_amount' => 0,
            ]);
            foreach ($v['items'] as $item) {
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
    }

    private function poUpdate(array $p): void
    {
        $v = Validator::make($p, [
            'purchase_order_id' => 'required|integer',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_arrival_date' => 'required|date',
            'payment_terms_count' => 'nullable|integer|min:1|max:60',
            'payment_terms_days' => 'nullable|integer|min:1|max:3650',
            'note' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0.01',
        ])->validate();
        if (strtotime((string) $v['expected_arrival_date']) < strtotime((string) $v['order_date'])) {
            throw new \RuntimeException('Expected arrival / due date must be after or equal to order date.');
        }
        $po = PurchaseOrder::find((int) $v['purchase_order_id']);
        if (! $po) {
            return;
        }
        if ($po->status !== 'ordered' || SupplierDelivery::where('purchase_order_id', $po->id)->exists() || $po->supplierPayments()->exists()) {
            throw new \RuntimeException('Purchase order cannot be updated in its current state.');
        }
        DB::transaction(function () use ($po, $v) {
            $total = 0;
            $po->update([
                'supplier_id' => $v['supplier_id'],
                'order_date' => $v['order_date'],
                'expected_arrival_date' => $v['expected_arrival_date'],
                'payment_terms_count' => $v['payment_terms_count'] ?? null,
                'payment_terms_days' => $v['payment_terms_days'] ?? null,
                'note' => $v['note'] ?? null,
            ]);
            $po->items()->delete();
            foreach ($v['items'] as $item) {
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

    private function poDelete(array $p): void
    {
        Validator::make($p, ['purchase_order_id' => 'required|integer'])->validate();
        $po = PurchaseOrder::find((int) $p['purchase_order_id']);
        if (! $po) {
            return;
        }
        if ($po->status !== 'ordered' || SupplierDelivery::where('purchase_order_id', $po->id)->exists() || $po->supplierPayments()->exists()) {
            throw new \RuntimeException('Purchase order cannot be deleted in its current state.');
        }
        DB::transaction(function () use ($po) {
            $po->items()->delete();
            $po->delete();
        });
    }

    private function poReceive(array $p): void
    {
        $v = Validator::make($p, [
            'purchase_order_id' => 'required|integer|exists:purchase_orders,id',
            'dr_number' => 'required|string|max:100',
            'arrival_date' => 'nullable|date',
            'arrival_notes' => 'nullable|string|max:500',
            'quantities' => 'required|array',
        ])->validate();
        $purchaseOrder = PurchaseOrder::findOrFail((int) $v['purchase_order_id']);
        if ($purchaseOrder->status === 'received') {
            throw new \RuntimeException('Purchase order already received.');
        }
        $quantities = collect($v['quantities'])
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => max(0, (int) $qty)])
            ->filter(fn ($qty) => $qty > 0);
        if ($quantities->isEmpty()) {
            throw new \RuntimeException('Enter at least one item quantity to receive.');
        }
        $purchaseOrder->load('items.product');
        DB::transaction(function () use ($v, $purchaseOrder, $quantities) {
            foreach ($purchaseOrder->items as $item) {
                $qty = $quantities->get($item->id, 0);
                if ($qty <= 0) {
                    continue;
                }
                $receiveQty = min($qty, (int) $item->quantity);
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $previousStock = (int) $product->stock_quantity;
                $newStock = $previousStock + $receiveQty;
                $product->update([
                    'stock_quantity' => $newStock,
                    'purchase_price' => $item->purchase_price,
                ]);
                InventoryLog::create([
                    'product_id' => $product->id,
                    'type' => 'add',
                    'quantity' => $receiveQty,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'note' => 'Received '.$purchaseOrder->po_number.' DR#'.$v['dr_number'],
                ]);
            }
            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now(),
                'dr_number' => $v['dr_number'],
                'arrival_date' => $v['arrival_date'] ?? null,
                'arrival_notes' => $v['arrival_notes'] ?? null,
            ]);
            SupplierDelivery::create([
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->id,
                'dr_number' => $v['dr_number'],
                'delivery_date' => $v['arrival_date'] ?: now()->toDateString(),
                'amount' => $purchaseOrder->total_amount,
                'notes' => $v['arrival_notes'] ?? null,
            ]);
        });
    }

    private function poRecordPayment(array $p): void
    {
        $v = Validator::make($p, [
            'purchase_order_id' => 'required|integer|exists:purchase_orders,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,check,bank_transfer,e_wallet,other',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ])->validate();
        $po = PurchaseOrder::findOrFail((int) $v['purchase_order_id']);
        SupplierPayment::create([
            'supplier_id' => $po->supplier_id,
            'purchase_order_id' => $po->id,
            'payment_date' => $v['payment_date'],
            'amount' => $v['amount'],
            'payment_method' => $v['payment_method'],
            'reference_no' => $v['reference_no'] ?? null,
            'notes' => ($v['notes'] ?? '') !== '' ? $v['notes'] : 'Supplier payment (purchase order)',
        ]);
    }

    private function stockTransferCreate(array $p, Authenticatable $user): void
    {
        $v = Validator::make($p, [
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ])->validate();
        $main = Branch::firstOrCreate(['code' => 'DAV-MAIN'], ['name' => 'DAVAO -MAIN']);
        $second = Branch::firstOrCreate(['code' => 'DIG-SECOND'], ['name' => 'DIGOS -SECOND']);
        if ($main->name !== 'DAVAO -MAIN') {
            $main->update(['name' => 'DAVAO -MAIN']);
        }
        if ($second->name !== 'DIGOS -SECOND') {
            $second->update(['name' => 'DIGOS -SECOND']);
        }
        foreach ($v['items'] as $item) {
            StockTransfer::create([
                'product_id' => $item['product_id'],
                'from_branch_id' => $main->id,
                'to_branch_id' => $second->id,
                'quantity' => (int) $item['quantity'],
                'note' => $v['note'] ?? null,
                'transferred_by' => $user instanceof User ? $user->id : null,
            ]);
        }
    }

    private function salePaymentCreate(array $p): void
    {
        $v = Validator::make($p, [
            'sale_id' => 'required|integer|exists:sales,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,card,bank_transfer,e_wallet,other',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ])->validate();
        $result = DB::transaction(function () use ($v) {
            $sale = Sale::lockForUpdate()->findOrFail((int) $v['sale_id']);
            $currentPaid = (float) $sale->payments()->sum('amount');
            $balance = max(0, (float) $sale->total_amount - $currentPaid);
            $amount = min((float) $v['amount'], $balance);
            if ($amount <= 0) {
                return 'skip';
            }
            $sale->payments()->create([
                'payment_date' => $v['payment_date'],
                'amount' => $amount,
                'payment_method' => $v['payment_method'],
                'reference_no' => $v['reference_no'] ?? null,
                'note' => $v['note'] ?? null,
            ]);

            return 'ok';
        });
        if ($result === 'skip') {
            return;
        }
    }

    private function salePaymentDelete(array $p): void
    {
        $v = Validator::make($p, [
            'sale_id' => 'required|integer',
            'payment_id' => 'required|integer',
        ])->validate();
        $payment = Payment::find((int) $v['payment_id']);
        if ($payment && (int) $payment->sale_id === (int) $v['sale_id']) {
            $payment->delete();
        }
    }

    private function ledgerDeliveryCreate(array $p): void
    {
        $v = Validator::make($p, [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'dr_number' => 'required|string|max:100',
            'delivery_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ])->validate();
        SupplierDelivery::create([
            'supplier_id' => $v['supplier_id'],
            'dr_number' => $v['dr_number'],
            'delivery_date' => $v['delivery_date'],
            'amount' => $v['amount'],
            'notes' => $v['notes'] ?? null,
        ]);
    }

    private function ledgerPaymentCreate(array $p): void
    {
        $v = Validator::make($p, [
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,check,bank_transfer,e_wallet,other',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ])->validate();
        SupplierPayment::create([
            'supplier_id' => $v['supplier_id'],
            'payment_date' => $v['payment_date'],
            'amount' => $v['amount'],
            'payment_method' => $v['payment_method'],
            'reference_no' => $v['reference_no'] ?? null,
            'notes' => $v['notes'] ?? null,
        ]);
    }

    private function ledgerDeliveryDelete(array $p): void
    {
        $v = Validator::make($p, [
            'supplier_id' => 'required|integer',
            'delivery_id' => 'required|integer',
        ])->validate();
        $d = SupplierDelivery::find((int) $v['delivery_id']);
        if ($d && (int) $d->supplier_id === (int) $v['supplier_id']) {
            $d->delete();
        }
    }

    private function ledgerPaymentDelete(array $p): void
    {
        $v = Validator::make($p, [
            'supplier_id' => 'required|integer',
            'payment_id' => 'required|integer',
        ])->validate();
        $pay = SupplierPayment::find((int) $v['payment_id']);
        if ($pay && (int) $pay->supplier_id === (int) $v['supplier_id']) {
            $pay->delete();
        }
    }
}
