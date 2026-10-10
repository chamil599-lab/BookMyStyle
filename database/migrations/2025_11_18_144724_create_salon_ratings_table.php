<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('salon_ratings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('salon_id');
        $table->string('email');
        $table->tinyInteger('rating'); // 1–5 stars
        $table->text('review')->nullable();
        $table->timestamps();

        // Prevent duplicate rating by same email for same salon
        $table->unique(['salon_id', 'email']);

        // Foreign key link
        $table->foreign('salon_id')
              ->references('id')
              ->on('salons')
              ->onDelete('cascade');
    });
}

};
