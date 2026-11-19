<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('service_radius_metres')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['provider_id', 'name']);
            $table->index(['provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
