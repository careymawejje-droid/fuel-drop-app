<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('collected_by_driver_id')->nullable()->after('order_id')->constrained('drivers')->nullOnDelete();
            $table->timestamp('collected_at')->nullable()->after('status');
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'status']);
            $table->dropForeign(['collected_by_driver_id']);
            $table->dropColumn(['collected_by_driver_id', 'collected_at']);
        });
    }
};
