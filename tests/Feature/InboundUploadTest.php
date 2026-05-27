<?php

namespace Tests\Feature;

use App\Imports\InboundImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class InboundUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_inbounds_csv(): void
    {
        Excel::fake();

        $file = UploadedFile::fake()->create('inbounds.csv', 10, 'text/csv');

        $response = $this->postJson('/api/inbounds/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'File inbound berhasil diunggah dan diproses.');

        Excel::assertImported('inbounds.csv', function (InboundImport $import) {
            return true;
        });
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('inbounds.pdf', 10, 'application/pdf');

        $response = $this->postJson('/api/inbounds/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_upload_requires_file(): void
    {
        $response = $this->postJson('/api/inbounds/upload', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
