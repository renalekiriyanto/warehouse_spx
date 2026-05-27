<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\EstimasiArrival;
use App\Models\TypeSlot;

class EstimasiArrivalTest extends TestCase
{
    use RefreshDatabase;

    // ── INDEX ──────────────────────────────────────────────

    public function test_can_list_estimasi_arrivals()
    {
        $typeSlot = TypeSlot::factory()->create();
        EstimasiArrival::factory(3)->create(['type_slot_id' => $typeSlot->id]);

        $response = $this->getJson('/api/estimasi-arrivals');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_returns_empty_array_when_no_records()
    {
        $response = $this->getJson('/api/estimasi-arrivals');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_index_includes_type_slot_relation()
    {
        $typeSlot = TypeSlot::factory()->create();
        EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot->id]);

        $response = $this->getJson('/api/estimasi-arrivals');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type_slot_id', 'estimasi_arrival', 'status', 'type_slot'],
                ],
            ]);
    }

    // ── STORE ─────────────────────────────────────────────

    public function test_can_create_estimasi_arrival()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'estimasi_arrival' => '08:00:00',
            'status' => true,
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 201)
            ->assertJsonFragment([
                'type_slot_id' => $typeSlot->id,
                'estimasi_arrival' => '08:00:00',
            ]);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'type_slot_id' => $typeSlot->id,
            'estimasi_arrival' => '08:00:00',
        ]);
    }

    public function test_store_returns_type_slot_relation()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'estimasi_arrival' => '09:00:00',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['type_slot']]);
    }

    public function test_store_defaults_status_to_true()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'estimasi_arrival' => '10:00:00',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'type_slot_id' => $typeSlot->id,
            'status' => 1,
        ]);
    }

    public function test_store_fails_without_required_fields()
    {
        $response = $this->postJson('/api/estimasi-arrivals', []);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_store_fails_with_invalid_type_slot_id()
    {
        $payload = [
            'type_slot_id' => 9999,
            'estimasi_arrival' => '08:00:00',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_store_fails_with_invalid_time_format()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'estimasi_arrival' => '8am',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    // ── SHOW ──────────────────────────────────────────────

    public function test_can_show_estimasi_arrival()
    {
        $arrival = EstimasiArrival::factory()->create();
        $response = $this->getJson("/api/estimasi-arrivals/{$arrival->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonFragment(['id' => $arrival->id]);
    }

    public function test_show_includes_type_slot_relation()
    {
        $arrival = EstimasiArrival::factory()->create();
        $response = $this->getJson("/api/estimasi-arrivals/{$arrival->id}");
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['type_slot']]);
    }

    public function test_show_returns_404_for_nonexistent()
    {
        $response = $this->getJson('/api/estimasi-arrivals/9999');
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }

    // ── UPDATE ────────────────────────────────────────────

    public function test_can_update_estimasi_arrival()
    {
        $arrival = EstimasiArrival::factory()->create([
            'estimasi_arrival' => '08:00:00',
        ]);

        $payload = [
            'estimasi_arrival' => '14:00:00',
        ];

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", $payload);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['estimasi_arrival' => '14:00:00']);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'id' => $arrival->id,
            'estimasi_arrival' => '14:00:00',
        ]);
    }

    public function test_can_update_type_slot_id()
    {
        $typeSlot1 = TypeSlot::factory()->create();
        $typeSlot2 = TypeSlot::factory()->create();
        $arrival = EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot1->id]);

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", [
            'type_slot_id' => $typeSlot2->id,
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'id' => $arrival->id,
            'type_slot_id' => $typeSlot2->id,
        ]);
    }

    public function test_can_toggle_status()
    {
        $arrival = EstimasiArrival::factory()->create(['status' => true]);

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", [
            'status' => false,
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['status' => false]);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'id' => $arrival->id,
            'status' => 0,
        ]);
    }

    public function test_update_fails_with_invalid_type_slot_id()
    {
        $arrival = EstimasiArrival::factory()->create();

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", [
            'type_slot_id' => 9999,
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_update_fails_with_invalid_time_format()
    {
        $arrival = EstimasiArrival::factory()->create();

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", [
            'estimasi_arrival' => 'invalid',
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_update_returns_404_for_nonexistent()
    {
        $response = $this->putJson('/api/estimasi-arrivals/9999', ['status' => false]);
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }

    // ── DELETE ─────────────────────────────────────────────

    public function test_can_delete_estimasi_arrival()
    {
        $arrival = EstimasiArrival::factory()->create();
        $response = $this->deleteJson("/api/estimasi-arrivals/{$arrival->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Data estimasi arrival berhasil dihapus');
        $this->assertDatabaseMissing('estimasi_arrivals', ['id' => $arrival->id]);
    }

    public function test_delete_does_not_affect_parent_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $arrival = EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot->id]);

        $this->deleteJson("/api/estimasi-arrivals/{$arrival->id}")->assertStatus(200);
        $this->assertDatabaseMissing('estimasi_arrivals', ['id' => $arrival->id]);
        $this->assertDatabaseHas('type_slots', ['id' => $typeSlot->id]);
    }

    public function test_delete_returns_404_for_nonexistent()
    {
        $response = $this->deleteJson('/api/estimasi-arrivals/9999');
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }
}
