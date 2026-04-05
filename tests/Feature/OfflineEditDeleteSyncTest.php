<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineEditDeleteSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_expense_update_and_delete_sync(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $catA = ExpenseCategory::firstOrCreate(['name' => 'Utilities']);
        $catB = ExpenseCategory::firstOrCreate(['name' => 'Fuel']);

        $expense = Expense::create([
            'expense_category_id' => $catA->id,
            'expense_date' => now()->toDateString(),
            'reference_no' => 'E-001',
            'amount' => 100,
            'vendor' => 'A',
            'description' => 'Old',
        ]);

        $updatePayload = [
            'expense_updates' => [[
                'local_id' => 'exp-up-1',
                'payload' => [
                    'expense_id' => $expense->id,
                    'expense_category_id' => $catB->id,
                    'expense_date' => now()->toDateString(),
                    'reference_no' => 'E-002',
                    'amount' => 200.25,
                    'vendor' => 'B',
                    'description' => 'Updated offline',
                ],
            ]],
        ];

        $this->actingAs($owner)
            ->postJson(route('offline-sync.expenses.updates'), $updatePayload)
            ->assertOk()
            ->assertJsonPath('synced.0.local_id', 'exp-up-1');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'expense_category_id' => $catB->id,
            'reference_no' => 'E-002',
            'vendor' => 'B',
        ]);

        $deletePayload = [
            'expense_deletes' => [[
                'local_id' => 'exp-del-1',
                'payload' => ['expense_id' => $expense->id],
            ]],
        ];

        $this->actingAs($owner)
            ->postJson(route('offline-sync.expenses.deletes'), $deletePayload)
            ->assertOk()
            ->assertJsonPath('synced.0.local_id', 'exp-del-1');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_offline_sale_delete_sync_restores_stock(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::create([
            'name' => 'P1',
            'sku' => 'P1',
            'brand' => null,
            'category' => null,
            'model' => null,
            'description' => null,
            'purchase_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 8,
            'minimum_stock' => 1,
        ]);

        $sale = Sale::create([
            'total_amount' => 400,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'note' => null,
            'issued_receipt' => true,
            'poc' => null,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 200,
            'purchase_price' => 100,
            'subtotal' => 400,
        ]);

        $payload = [
            'sale_deletes' => [[
                'local_id' => 'sale-del-1',
                'payload' => ['sale_id' => $sale->id],
            ]],
        ];

        $this->actingAs($owner)
            ->postJson(route('offline-sync.sales.deletes'), $payload)
            ->assertOk()
            ->assertJsonPath('synced.0.local_id', 'sale-del-1');

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);
    }

    public function test_offline_purchase_order_update_and_delete_sync(): void
    {
        $inventory = User::factory()->create(['role' => 'inventory']);
        $supplier = Supplier::create(['name' => 'Supplier X']);
        $p1 = Product::create([
            'name' => 'PO-P1',
            'sku' => 'PO-P1',
            'brand' => null,
            'category' => null,
            'model' => null,
            'description' => null,
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'minimum_stock' => 1,
        ]);
        $p2 = Product::create([
            'name' => 'PO-P2',
            'sku' => 'PO-P2',
            'brand' => null,
            'category' => null,
            'model' => null,
            'description' => null,
            'purchase_price' => 200,
            'selling_price' => 250,
            'stock_quantity' => 10,
            'minimum_stock' => 1,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'expected_arrival_date' => now()->toDateString(),
            'status' => 'ordered',
            'total_amount' => 100,
        ]);
        $po->items()->create([
            'product_id' => $p1->id,
            'quantity' => 1,
            'purchase_price' => 100,
            'subtotal' => 100,
        ]);

        $updatePayload = [
            'purchase_order_updates' => [[
                'local_id' => 'po-up-1',
                'payload' => [
                    'purchase_order_id' => $po->id,
                    'supplier_id' => $supplier->id,
                    'order_date' => now()->toDateString(),
                    'expected_arrival_date' => now()->toDateString(),
                    'payment_terms_count' => 2,
                    'payment_terms_days' => 60,
                    'note' => 'Updated offline PO',
                    'items' => [
                        ['product_id' => $p2->id, 'quantity' => 3, 'purchase_price' => 210],
                    ],
                ],
            ]],
        ];

        $this->actingAs($inventory)
            ->postJson(route('offline-sync.purchase-orders.updates'), $updatePayload)
            ->assertOk()
            ->assertJsonPath('synced.0.local_id', 'po-up-1');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'note' => 'Updated offline PO',
            'total_amount' => 630,
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'product_id' => $p2->id,
            'quantity' => 3,
        ]);

        $deletePayload = [
            'purchase_order_deletes' => [[
                'local_id' => 'po-del-1',
                'payload' => ['purchase_order_id' => $po->id],
            ]],
        ];

        $this->actingAs($inventory)
            ->postJson(route('offline-sync.purchase-orders.deletes'), $deletePayload)
            ->assertOk()
            ->assertJsonPath('synced.0.local_id', 'po-del-1');

        $this->assertDatabaseMissing('purchase_orders', ['id' => $po->id]);
    }
}
