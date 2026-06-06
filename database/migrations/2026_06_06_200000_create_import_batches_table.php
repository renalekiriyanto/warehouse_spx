<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->index();
            $table->string('type');                          // inbound | projection | std_someday | driver
            $table->string('original_filename');
            $table->string('stored_path');                  // path relatif di storage/app/imports
            $table->enum('status', ['uploading', 'queued', 'processing', 'completed', 'failed'])
                  ->default('uploading');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();             // array error per baris (max ~200 entri)
            $table->string('job_batch_id')->nullable();     // Laravel Bus batch ID
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
