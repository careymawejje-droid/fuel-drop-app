<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('provider_id')->constrained()->nullOnDelete();
            $table->foreignId('delivery_address_id')->nullable()->after('vehicle_id')->constrained('addresses')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->after('driver_id')->constrained()->nullOnDelete();
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->index(['provider_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['provider_id', 'status']);
            $table->dropIndex(['branch_id', 'status']);
            $table->dropIndex(['driver_id', 'status']);
            $table->dropForeign(['provider_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['delivery_address_id']);
            $table->dropForeign(['truck_id']);
            $table->dropColumn([
                'provider_id',
                'branch_id',
                'delivery_address_id',
                'truck_id',
                'delivery_latitude',
                'delivery_longitude',
            ]);
        });
    }
};
