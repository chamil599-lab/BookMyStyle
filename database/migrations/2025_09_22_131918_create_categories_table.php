<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Department relation
            $table->foreignId('department_id')
                ->constrained()
                ->cascadeOnDelete()
                ->index();

            // Parent_id (just the column here)
            $table->unsignedBigInteger('parent_id')->nullable()->index();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add the self-reference foreign key after table creation
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['parent_id']);
        });

        Schema::dropIfExists('categories');
    }
};
