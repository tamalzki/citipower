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

class OfflineModulesSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_expense_sync_creates_expense(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $category = ExpenseCategory::firstOrCreate(['name' => 'Utilities']);

        $payload = [
            'expenses' => [
                [
                    'local_id' => 'expense-local-1',
                    'payload' => [
                        'expense_category_id' => $category->id,
                        'expense_date' => now()->toDateString(),
                        'reference_no' => 'REF-001',
                        'amount' => 1234.50,
                        'vendor' => 'Power Co',
                        'description' => 'Offline expense sync test',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('offline-sync.expenses'), $payload);
        $response->assertOk();
        $response->assertJsonPath('synced.0.local_id', 'expense-local-1');

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $category->id,
            'reference_no' => 'REF-001',
            'amount' => 1234.50,
            'vendor' => 'Power Co',
        ]);
    }

    public function test_offline_purchase_order_and_stock_transfer_sync_create_records(): void
    {
        $user = User::factory()->create(['role' => 'inventory']);
        $supplier = Supplier::create([
            'name' => 'Supplier A',
            'contact_person' => null,
            'phone' => null,
            'email' => null,
            'address' => null,
            'note' => null,
        ]);

        $product = Product::create([
            'name' => 'Transfer Product',
            'sku' => 'TR-001',
            'brand' => null,
            'category' => null,
            'model' => null,
            'description' => null,
            'purchase_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 10,
            'minimum_stock' => 1,
        ]);

        $poPayload = [
            'purchase_orders' => [
                [
                    'local_id' => 'po-local-1',
                    'payload' => [
                        'supplier_id' => $supplier->id,
                        'order_date' => now()->toDateString(),
                        'expected_arrival_date' => now()->toDateString(),
                        'payment_terms_count' => 2,
                        'payment_terms_days' => 30,
                        'note' => 'Offline PO test',
                        'items' => [
                            [
                                'product_id' => $product->id,
                                'quantity' => 3,
                                'purchase_price' => 150,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $poResponse = $this->actingAs($user)->postJson(route('offline-sync.purchase-orders'), $poPayload);
        $poResponse->assertOk();
        $poResponse->assertJsonPath('synced.0.local_id', 'po-local-1');
        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertDatabaseCount('purchase_order_items', 1);

        $transferPayload = [
            'transfers' => [
                [
                    'local_id' => 'transfer-local-1',
                    'payload' => [
                        'note' => 'Offline transfer',
                        'items' => [
                            ['product_id' => $product->id, 'quantity' => 2],
                        ],
                    ],
                ],
            ],
        ];

        $stResponse = $this->actingAs($user)->postJson(route('offline-sync.stock-transfers'), $transferPayload);
        $stResponse->assertOk();
        $stResponse->assertJsonPath('synced.0.local_id', 'transfer-local-1');
        $this->assertDatabaseCount('stock_transfers', 1);
    }

    public function test_offline_expense_update_and_delete_sync_apply_changes(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $catA = ExpenseCategory::firstOrCreate(['name' => 'Utilities']);
        $catB = ExpenseCategory::firstOrCreate(['name' => 'Fuel']);

        $expense = Expense::create([
            'expense_category_id' => $catA->id,
            'expense_date' => now()->subDay()->toDateString(),
            'reference_no' => 'OLD-REF',
            'amount' => 100,
            'vendor' => 'Old Vendor',
            'description' => 'Old desc',
        ]);

        $updatePayload = [
            'expense_updates' => [[
                'local_id' => 'exp-upd-1',
                'payload' => [
                    'expense_id' => $expense->id,
                    'expense_category_id' => $catB->id,
                    'expense_date' => now()->toDateString(),
                    'reference_no' => 'NEW-REF',
                    'amount' => 250.75,
                    'vendor' => 'New Vendor',
                    'description' => 'Updated offline',
                ],
            ]],
        ];

        $updRes = $this->actingAs($user)->postJson(route('offline-sync.expenses.updates'), $updatePayload);
        $updRes->assertOk()->assertJsonPath('synced.0.local_id', 'exp-upd-1');
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'expense_category_id' => $catB->id,
            'reference_no' => 'NEW-REF',
            'amount' => 250.75,
            'vendor' => 'New Vendor',
        ]);

        $deletePayload = [
            'expense_deletes' => [[
                'local_id' => 'exp-del-1',
                'payload' => ['expense_id' => $expense->id],
            ]],
        ];
        $delRes = $this->actingAs($user)->postJson(route('offline-sync.expenses.deletes'), $deletePayload);
        $delRes->assertOk()->assertJsonPath('synced.0.local_id', 'exp-del-1');
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_offline_sale_void_sync_restores_stock_and_deletes_sale(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $product = Product::create([
            'name' => 'Void Product',
            'sku' => 'VOID-001',
            'brand' => null,
            'category' => null,
            'model' => null,
            'description' => null,
            'purchase_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 10,
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
        $product->update(['stock_quantity' => 8]); // mimic sale deduction

        $payload = [
            'sale_voids' => [[
                'local_id' => 'sale-void-1',
                'payload' => ['sale_id' => $sale->id],
            ]],
        ];

        $res = $this->actingAs($user)->postJson(route('offline-sync.sales.voids'), $payload);
        $res->assertOk()->assertJsonPath('synced.0.local_id', 'sale-void-1');
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 10]);
    }

    public function test_offline_purchase_order_update_and_delete_sync_apply_changes(): void
    {
        $user = User::factory()->create(['role' => 'inventory']);
        $supplierA = Supplier::create(['name' => 'Supplier A']);
        $supplierB = Supplier::create(['name' => 'Supplier B']);
        $product = Product::create([
            'name' => 'PO Product',
            'sku' => 'PO-001',
            'brand' => null,
            'category' => null,
            'model' => null,
            'description' => null,
            'purchase_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 10,
            'minimum_stock' => 1,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $supplierA->id,
            'order_date' => now()->toDateString(),
            'expected_arrival_date' => now()->addDay()->toDateString(),
            'payment_terms_count' => null,
            'payment_terms_days' => null,
            'status' => 'ordered',
            'total_amount' => 300,
            'note' => 'old',
        ]);
        $po->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'purchase_price' => 100,
            'subtotal' => 300,
        ]);

        $updatePayload = [
            'purchase_order_updates' => [[
                'local_id' => 'po-upd-1',
                'payload' => [
                    'purchase_order_id' => $po->id,
                    'supplier_id' => $supplierB->id,
                    'order_date' => now()->toDateString(),
                    'expected_arrival_date' => now()->addDays(2)->toDateString(),
                    'payment_terms_count' => 2,
                    'payment_terms_days' => 30,
                    'note' => 'updated offline',
                    'items' => [[
                        'product_id' => $product->id,
                        'quantity' => 4,
                        'purchase_price' => 120,
                    ]],
                ],
            ]],
        ];

        $updRes = $this->actingAs($user)->postJson(route('offline-sync.purchase-orders.updates'), $updatePayload);
        $updRes->assertOk()->assertJsonPath('synced.0.local_id', 'po-upd-1');
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'supplier_id' => $supplierB->id,
            'note' => 'updated offline',
            'total_amount' => 480,
        ]);

        $deletePayload = [
            'purchase_order_deletes' => [[
                'local_id' => 'po-del-1',
                'payload' => ['purchase_order_id' => $po->id],
            ]],
        ];
        $delRes = $this->actingAs($user)->postJson(route('offline-sync.purchase-orders.deletes'), $deletePayload);
        $delRes->assertOk()->assertJsonPath('synced.0.local_id', 'po-del-1');
        $this->assertDatabaseMissing('purchase_orders', ['id' => $po->id]);
    }
}
