<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Imports\ProjectionImport;

class ProjectionUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_projections_csv()
    {
        $user = User::factory()->create();

        Excel::fake();

        // Create a fake CSV file
        $file = UploadedFile::fake()->create('projections.csv', 10, 'text/csv');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/projections/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'File successfully uploaded and processed.']);

        Excel::assertImported('projections.csv', function (ProjectionImport $import) {
            return true;
        });
    }

    public function test_user_cannot_upload_invalid_file_type()
    {
        $user = User::factory()->create();

        // Create a fake PDF file
        $file = UploadedFile::fake()->create('projections.pdf', 10, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/projections/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }
}
