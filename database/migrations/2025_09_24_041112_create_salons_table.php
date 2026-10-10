<?php

use App\Models\User;
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
        Schema::create('salons', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('owner_name');
    $table->string('location')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->longText('description')->nullable();
    $table->string('cover_image')->nullable();
    
    // Track who created/updated the record
    $table->foreignIdFor(User::class, 'created_by');
    $table->foreignIdFor(User::class, 'updated_by');

    $table->timestamps();
    $table->softDeletes();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salons');
    }
};
