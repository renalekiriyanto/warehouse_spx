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
        Schema::create('std_somedays', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time');
            $table->string('awb');
            $table->unsignedBigInteger('id_driver')->nullable();
            $table->foreign('id_driver')->references('id')->on('drivers')->onDelete('set null');
            $table->enum('status', ['LMHub_Received', 'LMHub_Assigned', 'LMHub_Assigning', 'Return_LMHub_Packed', 'Delivering', 'OnHold', 'Delivered'])->default('LMHub_Received');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('std_somedays');
    }
};
