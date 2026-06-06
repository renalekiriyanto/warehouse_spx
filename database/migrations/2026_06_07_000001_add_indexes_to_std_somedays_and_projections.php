<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah index performa pada tabel yang sering di-query/insert massal.
 *
 * std_somedays:
 *   - awb          → sering dipakai di whereIn() saat dedup import
 *   - status        → sering difilter di frontend
 *   - (awb, date_time) composite → dedup key pada import
 *
 * projections:
 *   - date_inbound unique → diperlukan oleh DB::upsert() agar ON DUPLICATE KEY bekerja
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('std_somedays', function (Blueprint $table) {
            $table->index('awb',    'std_somedays_awb_index');
            $table->index('status', 'std_somedays_status_index');
        });

        Schema::table('projections', function (Blueprint $table) {
            // Unique constraint diperlukan agar upsert() bekerja benar di MySQL/MariaDB
            $table->unique('date_inbound', 'projections_date_inbound_unique');
        });
    }

    public function down(): void
    {
        Schema::table('std_somedays', function (Blueprint $table) {
            $table->dropIndex('std_somedays_awb_index');
            $table->dropIndex('std_somedays_status_index');
        });

        Schema::table('projections', function (Blueprint $table) {
            $table->dropUnique('projections_date_inbound_unique');
        });
    }
};
