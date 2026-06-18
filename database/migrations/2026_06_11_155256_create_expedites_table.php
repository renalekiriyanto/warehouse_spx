<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expedites', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('awb');
            $table->string('id_driver');
            $table->string('status')->nullable();
            $table->string('current_station')->default('Inuman Hub');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedites');
    }
};
