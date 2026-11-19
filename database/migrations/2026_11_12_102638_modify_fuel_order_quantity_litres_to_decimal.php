<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fuel_order', function (Blueprint $table) {
            $table->decimal('quantity_litres', 12, 3)->change();
        });
    }

    public function down(): void
    {
        // This is forward-only because converting fractional quantities back to integers loses data.
    }
};
