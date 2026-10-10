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
        // database/migrations/xxxx_create_subscription_packages_table.php
Schema::create('subscription_packages', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Salon Starter, Salon Pro, Salon Elite
    $table->string('slug')->unique();
    $table->decimal('price', 8, 2);
    $table->integer('duration_days'); // 30, 365, etc
    $table->integer('max_salons'); // How many salons owner can create
    $table->integer('max_staff'); // Staff members per salon
    $table->integer('max_services'); // Services per salon
    $table->boolean('analytics')->default(false);
    $table->boolean('priority_support')->default(false);
    $table->text('features')->nullable(); // JSON or text for additional features
    $table->boolean('is_active')->default(true);
    $table->boolean('is_popular')->default(false);
    $table->integer('order_column')->default(0);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_packages');
    }
};
