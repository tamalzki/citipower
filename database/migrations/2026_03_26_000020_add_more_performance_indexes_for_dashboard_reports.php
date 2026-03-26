<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('expected_arrival_date', 'po_expected_arrival_idx');
            $table->index(['supplier_id', 'expected_arrival_date'], 'po_supplier_due_idx');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->index(['to_branch_id', 'product_id'], 'st_to_branch_product_idx');
            $table->index(['from_branch_id', 'product_id'], 'st_from_branch_product_idx');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('po_expected_arrival_idx');
            $table->dropIndex('po_supplier_due_idx');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex('st_to_branch_product_idx');
            $table->dropIndex('st_from_branch_product_idx');
        });
    }
};

