<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('provider_id')->constrained()->nullOnDelete();
            $table->index(['provider_id', 'available_status']);
            $table->index(['branch_id', 'available_status']);
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['provider_id', 'available_status']);
            $table->dropIndex(['branch_id', 'available_status']);
            $table->dropForeign(['provider_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['provider_id', 'branch_id']);
        });
    }
};
