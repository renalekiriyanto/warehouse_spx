<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProjectionUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_projections_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'projections.csv',
            "inbound_date,projected_lm_inbound\n"
        );

        $response = $this->postJson('/api/projections/upload', [
            'file' => $file,
        ]);

        // Upload kini async → 202 Accepted
        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed'); // file kosong langsung completed
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('projections.pdf', 10, 'application/pdf');

        $response = $this->postJson('/api/projections/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_upload_requires_file(): void
    {
        $response = $this->postJson('/api/projections/upload', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
