<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->timestamps();
        });

        // Seed default branches
        DB::table('branches')->insert([
            ['name' => 'Main Branch', 'code' => 'MAIN', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse',   'code' => 'WH',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
