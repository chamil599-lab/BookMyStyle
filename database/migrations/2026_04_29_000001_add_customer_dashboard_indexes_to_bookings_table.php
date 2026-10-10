<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['user_id', 'date', 'status'], 'bookings_user_date_status_index');
            $table->index(['email', 'date', 'status'], 'bookings_email_date_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_user_date_status_index');
            $table->dropIndex('bookings_email_date_status_index');
        });
    }
};
