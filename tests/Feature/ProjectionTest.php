<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Projection;

class ProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_projections()
    {
        Projection::factory(3)->create();
        $response = $this->getJson('/api/projections');
        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_can_create_projection()
    {
        $payload = [
            'projected_inbound' => 500,
            'date_inbound' => '2026-06-01',
        ];
        $response = $this->postJson('/api/projections', $payload);
        $response->assertStatus(201)->assertJsonFragment(['projected_inbound' => 500]);
        $this->assertDatabaseHas('projections', ['projected_inbound' => 500, 'date_inbound' => '2026-06-01']);
    }

    public function test_can_show_projection()
    {
        $projection = Projection::factory()->create();
        $response = $this->getJson("/api/projections/{$projection->id}");
        $response->assertStatus(200)->assertJsonFragment(['id' => $projection->id]);
    }

    public function test_can_update_projection()
    {
        $projection = Projection::factory()->create();
        $payload = ['projected_inbound' => 999];
        $response = $this->putJson("/api/projections/{$projection->id}", $payload);
        $response->assertStatus(200)->assertJsonFragment(['projected_inbound' => 999]);
        $this->assertDatabaseHas('projections', ['id' => $projection->id, 'projected_inbound' => 999]);
    }

    public function test_can_delete_projection()
    {
        $projection = Projection::factory()->create();
        $response = $this->deleteJson("/api/projections/{$projection->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('projections', ['id' => $projection->id]);
    }
}
