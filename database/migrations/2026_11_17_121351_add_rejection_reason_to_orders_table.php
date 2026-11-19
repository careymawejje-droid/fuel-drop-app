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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    /**
     * This migration is intentionally irreversible because dropping the column would
     * permanently delete recorded rejection reasons. Use a forward migration to correct
     * the schema rather than rolling this migration back.
     */
    public function down(): void
    {
        throw new LogicException('This migration is irreversible to preserve rejection reasons.');
    }
};
