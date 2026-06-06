<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InboundUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_inbounds_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'inbounds.csv',
            "type_slot,date_inbound,actual_arrival,total_order\n"
        );

        $response = $this->postJson('/api/inbounds/upload', [
            'file' => $file,
        ]);

        // Upload kini async → 202 Accepted, bukan 201
        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed'); // file kosong langsung completed
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
