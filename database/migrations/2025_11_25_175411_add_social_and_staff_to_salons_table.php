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
        Schema::table('salons', function (Blueprint $table) {
            $table->integer('number_of_staff')->nullable()->after('description');
            $table->string('facebook_page')->nullable()->after('number_of_staff');
            $table->string('instagram_page')->nullable()->after('facebook_page');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn(['number_of_staff', 'facebook_page', 'instagram_page']);
        });
    }
};
