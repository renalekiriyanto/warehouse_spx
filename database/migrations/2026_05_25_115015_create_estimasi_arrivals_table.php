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
        Schema::create('estimasi_arrivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_slot_id')->constrained('type_slots')->cascadeOnDelete();
            $table->time('time_start');
            $table->time('time_end');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimasi_arrivals');
    }
};
