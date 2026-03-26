<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedInteger('payment_terms_count')->nullable()->after('expected_arrival_date');
            $table->unsignedInteger('payment_terms_days')->nullable()->after('payment_terms_count');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_terms_count', 'payment_terms_days']);
        });
    }
};

