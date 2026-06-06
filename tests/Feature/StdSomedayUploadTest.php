<?php

namespace Tests\Feature;

use App\Imports\StdSomedayImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StdSomedayUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_std_someday_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'std_someday.csv',
            "date,time,awb,id_driver,driver_name,status\n"
        );

        $response = $this->postJson('/api/std-somedays/upload', [
            'file' => $file,
        ]);

        // Upload kini async → 202 Accepted
        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed'); // file kosong langsung completed
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('std_someday.pdf', 10, 'application/pdf');

        $response = $this->postJson('/api/std-somedays/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_upload_requires_file(): void
    {
        $response = $this->postJson('/api/std-somedays/upload', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_import_normalizes_invalid_time_suffix(): void
    {
        $import = new StdSomedayImport();

        $data = [
            'date' => '2026-05-07',
            'time' => '14:00:00 AM',
            'awb'  => 'SPXID123',
            'status' => 'Delivering',
        ];

        $normalized = $import->prepareRowForValidation($data, 1);

        $this->assertEquals('2026-05-07 14:00:00', $normalized['date_time']);
    }
}
