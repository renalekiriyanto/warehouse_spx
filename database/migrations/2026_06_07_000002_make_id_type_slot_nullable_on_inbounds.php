<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * id_type_slot pada tabel inbounds tidak punya FK constraint dan bisa NULL
 * (inbound bisa tiba di luar jam slot manapun).
 * Jadikan nullable agar konsisten dengan model dan test.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbounds', function (Blueprint $table) {
            $table->integer('id_type_slot')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inbounds', function (Blueprint $table) {
            $table->integer('id_type_slot')->nullable(false)->change();
        });
    }
};
