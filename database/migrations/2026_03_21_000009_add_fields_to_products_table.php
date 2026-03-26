<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand', 100)->nullable()->after('sku');
            $table->string('category', 100)->nullable()->after('brand');
            $table->string('model', 100)->nullable()->after('category');
            $table->text('description')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['brand', 'category', 'model', 'description']);
        });
    }
};
