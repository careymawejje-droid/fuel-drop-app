<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_fuel_id')->constrained('branch_fuel')->restrictOnDelete();
            $table->decimal('quantity_litres', 12, 3);
            $table->string('status')->default('reserved');
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_fuel_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
