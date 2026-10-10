<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('service_pages', 'src_url')) {
                $table->string('src_url')->nullable()->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            if (Schema::hasColumn('service_pages', 'src_url')) {
                $table->dropColumn('src_url');
            }
        });
    }
};
