<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineMutationsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_mutations_endpoint_applies_product_create_and_sale_create_in_order(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $payload = [
            'mutations' => [
                [
                    'local_id' => 'm1',
                    'kind' => 'product_create',
                    'payload' => [
                        'name' => 'Offline Mut Product',
                        'sku' => 'OM-001',
                        'brand' => null,
                        'category' => null,
                        'model' => null,
                        'description' => null,
                        'purchase_price' => 50,
                        'selling_price' => 100,
                        'stock_quantity' => 20,
                        'minimum_stock' => 1,
                        'supplier_ids' => [],
                        'supplier_costs' => [],
                    ],
                ],
                [
                    'local_id' => 'm2',
                    'kind' => 'sale_create',
                    'payload' => [
                        'items' => [
                            ['product_id' => 1, 'quantity' => 2],
                        ],
                        'discount_type' => null,
                        'discount_value' => 0,
                        'note' => null,
                        'issued_receipt' => 1,
                        'poc' => null,
                    ],
                ],
            ],
        ];

        $res = $this->actingAs($user)->postJson(route('offline-sync.mutations'), $payload);
        $res->assertOk();
        $this->assertDatabaseHas('products', ['sku' => 'OM-001', 'stock_quantity' => 18]);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_mutations_reject_wrong_role(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $payload = [
            'mutations' => [[
                'local_id' => 'x1',
                'kind' => 'product_create',
                'payload' => [
                    'name' => 'X',
                    'sku' => null,
                    'brand' => null,
                    'category' => null,
                    'model' => null,
                    'description' => null,
                    'purchase_price' => 1,
                    'selling_price' => 2,
                    'stock_quantity' => 0,
                    'minimum_stock' => 0,
                    'supplier_ids' => [],
                    'supplier_costs' => [],
                ],
            ]],
        ];
        $res = $this->actingAs($cashier)->postJson(route('offline-sync.mutations'), $payload);
        $res->assertOk();
        $res->assertJsonCount(1, 'failed');
        $this->assertStringContainsString('not authorized', (string) ($res->json('failed.0.message') ?? ''));
    }
}
