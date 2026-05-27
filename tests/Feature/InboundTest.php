<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Inbound;

class InboundTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_inbounds()
    {
        Inbound::factory(3)->create();
        $response = $this->getJson('/api/inbounds');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_inbound()
    {
        $payload = [
            'actual_arrival' => '10:00:00',
            'bulky' => 5,
            'total_order' => 100,
        ];
        $response = $this->postJson('/api/inbounds', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 201)
            ->assertJsonPath('message', 'Data inbound berhasil ditambahkan')
            ->assertJsonFragment($payload);
        $this->assertDatabaseHas('inbounds', $payload);
    }

    public function test_can_show_inbound()
    {
        $inbound = Inbound::factory()->create();
        $response = $this->getJson("/api/inbounds/{$inbound->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonFragment(['id' => $inbound->id]);
    }

    public function test_can_update_inbound()
    {
        $inbound = Inbound::factory()->create();
        $payload = [
            'actual_arrival' => '12:00:00',
            'bulky' => 10,
            'total_order' => 200,
        ];
        $response = $this->putJson("/api/inbounds/{$inbound->id}", $payload);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Data inbound berhasil diupdate')
            ->assertJsonFragment($payload);
        $this->assertDatabaseHas('inbounds', $payload);
    }

    public function test_can_delete_inbound()
    {
        $inbound = Inbound::factory()->create();
        $response = $this->deleteJson("/api/inbounds/{$inbound->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Data inbound berhasil dihapus');
        $this->assertDatabaseMissing('inbounds', ['id' => $inbound->id]);
    }

    // ── ERROR HANDLING ────────────────────────────────────

    public function test_store_fails_validation()
    {
        $response = $this->postJson('/api/inbounds', []);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_show_returns_404_for_nonexistent()
    {
        $response = $this->getJson('/api/inbounds/9999');
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }

    public function test_delete_returns_404_for_nonexistent()
    {
        $response = $this->deleteJson('/api/inbounds/9999');
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }
}
