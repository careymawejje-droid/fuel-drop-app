<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_fuel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_id')->constrained()->restrictOnDelete();
            $table->decimal('price_per_litre', 10, 2);
            $table->decimal('available_stock_litres', 12, 3)->default(0);
            $table->decimal('reserved_stock_litres', 12, 3)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['branch_id', 'fuel_id']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_fuel');
    }
};
