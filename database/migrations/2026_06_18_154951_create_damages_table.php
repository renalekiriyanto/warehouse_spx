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
        Schema::create('damages', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->dateTime('arrival');
            $table->dateTime('departed');
            $table->string('awb')->unique();
            $table->char('lt_number', 13);
            $table->char('to_number', 15);
            $table->string('driver_lh');
            $table->char('vehicle_number', 10);
            $table->longText('sku_name');
            $table->timestamps();

            $table->index('date');
            $table->index('awb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damages');
    }
};
