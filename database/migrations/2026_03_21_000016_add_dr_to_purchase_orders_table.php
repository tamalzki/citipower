<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('dr_number', 100)->nullable()->after('note');
            $table->date('arrival_date')->nullable()->after('dr_number');
            $table->text('arrival_notes')->nullable()->after('arrival_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['dr_number', 'arrival_date', 'arrival_notes']);
        });
    }
};
