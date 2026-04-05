<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSalesSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_sales_sync_creates_sale_and_deducts_stock(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);

        $product = Product::create([
            'name' => 'Test Speaker',
            'sku' => 'TST-SPK-001',
            'brand' => null,
            'category' => null,
            'model' => null,
            'description' => null,
            'purchase_price' => 500,
            'selling_price' => 1000,
            'stock_quantity' => 10,
            'minimum_stock' => 2,
        ]);

        $payload = [
            'sales' => [
                [
                    'local_id' => 'local-sale-1',
                    'client_timestamp' => now()->toISOString(),
                    'payload' => [
                        'items' => [
                            ['product_id' => $product->id, 'quantity' => 2],
                        ],
                        'discount_type' => 'fixed',
                        'discount_value' => 100,
                        'note' => 'Offline test sale',
                        'issued_receipt' => '1',
                        'poc' => 'Cashier A',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('offline-sync.sales'), $payload);
        $response->assertOk();
        $response->assertJsonPath('synced.0.local_id', 'local-sale-1');

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('sales', [
            'note' => 'Offline test sale',
            'poc' => 'Cashier A',
        ]);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8,
        ]);
    }
}
