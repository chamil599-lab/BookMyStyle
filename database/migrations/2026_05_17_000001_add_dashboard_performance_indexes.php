<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['salon_id', 'status', 'date'], 'bookings_salon_status_date_idx');
            $table->index(['status', 'created_at'], 'bookings_status_created_idx');
            $table->index(['status', 'date'], 'bookings_status_date_idx');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->index(['status', 'ends_at'], 'user_subs_status_ends_idx');
        });

        if (Schema::hasTable('site_visits') && ! Schema::hasIndex('site_visits', ['occurred_at'])) {
            Schema::table('site_visits', function (Blueprint $table) {
                $table->index('occurred_at', 'site_visits_occurred_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_salon_status_date_idx');
            $table->dropIndex('bookings_status_created_idx');
            $table->dropIndex('bookings_status_date_idx');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex('user_subs_status_ends_idx');
        });

        if (Schema::hasTable('site_visits') && Schema::hasIndex('site_visits', 'site_visits_occurred_at_idx')) {
            Schema::table('site_visits', function (Blueprint $table) {
                $table->dropIndex('site_visits_occurred_at_idx');
            });
        }
    }
};
