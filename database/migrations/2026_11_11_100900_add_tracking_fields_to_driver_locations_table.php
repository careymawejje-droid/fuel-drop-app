<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_locations', function (Blueprint $table) {
            $table->decimal('accuracy_metres', 8, 2)->nullable()->after('longitude');
            $table->index(['driver_id', 'recorded_at']);
            $table->index(['order_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('driver_locations', function (Blueprint $table) {
            $table->dropIndex(['driver_id', 'recorded_at']);
            $table->dropIndex(['order_id', 'recorded_at']);
            $table->dropColumn('accuracy_metres');
        });
    }
};
