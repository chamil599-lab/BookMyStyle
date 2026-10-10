<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dealspages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->string('category')->nullable();
            $table->string('salon')->nullable();
            $table->integer('original_price')->nullable();
            $table->integer('discounted_price')->nullable();
            $table->integer('discount')->nullable();
            $table->string('image')->nullable();
            $table->string('badge')->nullable();
            $table->string('badge_color')->nullable();
            $table->text('description')->nullable();
            $table->date('valid_until')->nullable();
            $table->json('features')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=published,0=draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dealspages');
    }
};
