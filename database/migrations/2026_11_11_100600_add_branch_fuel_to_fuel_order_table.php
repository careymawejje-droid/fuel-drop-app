<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_order', function (Blueprint $table) {
            $table->foreignId('branch_fuel_id')->nullable()->after('fuel_id')->constrained('branch_fuel')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fuel_order', function (Blueprint $table) {
            $table->dropForeign(['branch_fuel_id']);
            $table->dropColumn('branch_fuel_id');
        });
    }
};
