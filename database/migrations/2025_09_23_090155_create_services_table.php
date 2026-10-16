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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title', 2000);
            $table->string('slug', 2000);
            $table->longText('description');

            // Correct way: creates department_id column + FK
            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();

            // Correct way: creates category_id column + FK
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->decimal('price', 10, 2);
            $table->string('status')->index();

            $table->foreignIdFor(User::class, 'created_by');
            $table->foreignIdFor(User::class, 'updated_by');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
