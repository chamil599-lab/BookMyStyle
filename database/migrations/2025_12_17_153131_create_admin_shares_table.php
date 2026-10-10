<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('admin_shares');
        
        Schema::create('admin_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')
                ->constrained('salons')
                ->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0); // calculated 5% share
            $table->decimal('paid', 10, 2)->default(0);   // amount already paid by salon
            $table->string('note')->nullable();
            $table->date('paid_date')->nullable();
            $table->timestamps();
            
            // One record per salon
            $table->unique('salon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_shares');
    }
};