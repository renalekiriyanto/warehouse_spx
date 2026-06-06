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
        Schema::table('std_somedays', function (Blueprint $table) {
            $table->enum('status', [
                'LMHub_Received',
                'LMHub_Assigned',
                'LMHub_Assigning',
                'Return_LMHub_Packed',
                'Return_LMHub_Received',
                'Delivering',
                'OnHold',
                'Delivered'
            ])->default('LMHub_Received')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('std_somedays', function (Blueprint $table) {
            $table->enum('status', [
                'LMHub_Received',
                'LMHub_Assigned',
                'LMHub_Assigning',
                'Return_LMHub_Packed',
                'Delivering',
                'OnHold',
                'Delivered'
            ])->default('LMHub_Received')->change();
        });
    }
};
