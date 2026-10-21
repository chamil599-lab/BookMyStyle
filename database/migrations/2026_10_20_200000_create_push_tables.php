<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personal_access_token_id')->constrained('personal_access_tokens')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->text('token');
            $table->timestamps();
        });
        Schema::create('booking_reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->unique(['booking_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reminder_deliveries');
        Schema::dropIfExists('push_devices');
    }
};
