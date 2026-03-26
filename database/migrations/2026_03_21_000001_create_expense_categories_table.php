<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('expense_categories')->insert([
            ['name' => 'Utilities', 'description' => 'Electricity, water, internet, and related bills', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Food', 'description' => 'Meals, snacks, and pantry supplies', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transportation', 'description' => 'Fuel, fares, delivery, and travel costs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rent', 'description' => 'Office, storage, and facility rent payments', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Salaries', 'description' => 'Wages, salaries, and staff allowances', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maintenance', 'description' => 'Repairs and upkeep of tools/equipment', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Supplies', 'description' => 'Office and operational consumables', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Marketing', 'description' => 'Ads, promotions, and campaign costs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other', 'description' => 'Miscellaneous business expenses', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
