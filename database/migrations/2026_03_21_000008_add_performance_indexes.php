<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('created_at', 'sales_created_at_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_date', 'expenses_expense_date_idx');
            $table->index('expense_category_id', 'expenses_category_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status', 'po_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_created_at_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_expense_date_idx');
            $table->dropIndex('expenses_category_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('po_status_idx');
        });
    }
};
