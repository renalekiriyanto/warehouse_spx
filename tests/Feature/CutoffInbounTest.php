<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\CutoffInboun;
use App\Models\User;

class CutoffInbounTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_cutoff_inbouns()
    {
        CutoffInboun::factory(3)->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/cutoff-inbounds');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_cutoff_inboun()
    {
        $payload = [
            'name' => 'Morning Cutoff',
            'slug' => 'morning-cutoff',
            'is_active' => true,
            'time_start' => '08:00:00',
            'time_end' => '12:00:00',
        ];
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/cutoff-inbounds', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 201)
            ->assertJsonFragment(['name' => 'Morning Cutoff']);
        $this->assertDatabaseHas('cutoff_inbouns', ['slug' => 'morning-cutoff']);
    }

    public function test_can_show_cutoff_inboun()
    {
        $cutoff = CutoffInboun::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/cutoff-inbounds/{$cutoff->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonFragment(['id' => $cutoff->id]);
    }

    public function test_can_update_cutoff_inboun()
    {
        $cutoff = CutoffInboun::factory()->create();
        $payload = ['name' => 'Updated Name'];
        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/cutoff-inbounds/{$cutoff->id}", $payload);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonFragment(['name' => 'Updated Name']);
        $this->assertDatabaseHas('cutoff_inbouns', ['id' => $cutoff->id, 'name' => 'Updated Name']);
    }

    public function test_can_delete_cutoff_inboun()
    {
        $cutoff = CutoffInboun::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/cutoff-inbounds/{$cutoff->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Data cutoff inbound berhasil dihapus');
        $this->assertDatabaseMissing('cutoff_inbouns', ['id' => $cutoff->id]);
    }
}
