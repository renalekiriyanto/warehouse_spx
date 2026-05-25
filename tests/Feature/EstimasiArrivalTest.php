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
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_index_returns_empty_array_when_no_records()
    {
        $response = $this->getJson('/api/estimasi-arrivals');
        $response->assertStatus(200)->assertJsonCount(0);
    }

    public function test_index_includes_type_slot_relation()
    {
        $typeSlot = TypeSlot::factory()->create();
        EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot->id]);

        $response = $this->getJson('/api/estimasi-arrivals');
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'type_slot_id', 'time_start', 'time_end', 'is_active', 'type_slot'],
            ]);
    }

    // ── STORE ─────────────────────────────────────────────

    public function test_can_create_estimasi_arrival()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'time_start' => '08:00:00',
            'time_end' => '12:00:00',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(201)
            ->assertJsonFragment([
                'type_slot_id' => $typeSlot->id,
                'time_start' => '08:00:00',
                'time_end' => '12:00:00',
            ]);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'type_slot_id' => $typeSlot->id,
            'time_start' => '08:00:00',
            'time_end' => '12:00:00',
        ]);
    }

    public function test_store_returns_type_slot_relation()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'time_start' => '09:00:00',
            'time_end' => '11:00:00',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure(['type_slot']);
    }

    public function test_store_defaults_is_active_to_true()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'time_start' => '10:00:00',
            'time_end' => '14:00:00',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'type_slot_id' => $typeSlot->id,
            'is_active' => 1,
        ]);
    }

    public function test_store_fails_without_required_fields()
    {
        $response = $this->postJson('/api/estimasi-arrivals', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_slot_id', 'time_start', 'time_end']);
    }

    public function test_store_fails_with_invalid_type_slot_id()
    {
        $payload = [
            'type_slot_id' => 9999,
            'time_start' => '08:00:00',
            'time_end' => '12:00:00',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_slot_id']);
    }

    public function test_store_fails_with_invalid_time_format()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = [
            'type_slot_id' => $typeSlot->id,
            'time_start' => '8am',
            'time_end' => 'noon',
        ];

        $response = $this->postJson('/api/estimasi-arrivals', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['time_start', 'time_end']);
    }

    // ── SHOW ──────────────────────────────────────────────

    public function test_can_show_estimasi_arrival()
    {
        $arrival = EstimasiArrival::factory()->create();
        $response = $this->getJson("/api/estimasi-arrivals/{$arrival->id}");
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $arrival->id]);
    }

    public function test_show_includes_type_slot_relation()
    {
        $arrival = EstimasiArrival::factory()->create();
        $response = $this->getJson("/api/estimasi-arrivals/{$arrival->id}");
        $response->assertStatus(200)
            ->assertJsonStructure(['type_slot']);
    }

    public function test_show_returns_404_for_nonexistent()
    {
        $response = $this->getJson('/api/estimasi-arrivals/9999');
        $response->assertStatus(404);
    }

    // ── UPDATE ────────────────────────────────────────────

    public function test_can_update_estimasi_arrival()
    {
        $arrival = EstimasiArrival::factory()->create([
            'time_start' => '08:00:00',
            'time_end' => '12:00:00',
        ]);

        $payload = [
            'time_start' => '14:00:00',
            'time_end' => '18:00:00',
        ];

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", $payload);
        $response->assertStatus(200)
            ->assertJsonFragment(['time_start' => '14:00:00', 'time_end' => '18:00:00']);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'id' => $arrival->id,
            'time_start' => '14:00:00',
            'time_end' => '18:00:00',
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
        $response->assertStatus(200);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'id' => $arrival->id,
            'type_slot_id' => $typeSlot2->id,
        ]);
    }

    public function test_can_toggle_is_active()
    {
        $arrival = EstimasiArrival::factory()->create(['is_active' => true]);

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", [
            'is_active' => false,
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['is_active' => false]);
        $this->assertDatabaseHas('estimasi_arrivals', [
            'id' => $arrival->id,
            'is_active' => 0,
        ]);
    }

    public function test_update_fails_with_invalid_type_slot_id()
    {
        $arrival = EstimasiArrival::factory()->create();

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", [
            'type_slot_id' => 9999,
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_slot_id']);
    }

    public function test_update_fails_with_invalid_time_format()
    {
        $arrival = EstimasiArrival::factory()->create();

        $response = $this->putJson("/api/estimasi-arrivals/{$arrival->id}", [
            'time_start' => 'invalid',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['time_start']);
    }

    public function test_update_returns_404_for_nonexistent()
    {
        $response = $this->putJson('/api/estimasi-arrivals/9999', ['is_active' => false]);
        $response->assertStatus(404);
    }

    // ── DELETE ─────────────────────────────────────────────

    public function test_can_delete_estimasi_arrival()
    {
        $arrival = EstimasiArrival::factory()->create();
        $response = $this->deleteJson("/api/estimasi-arrivals/{$arrival->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('estimasi_arrivals', ['id' => $arrival->id]);
    }

    public function test_delete_does_not_affect_parent_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $arrival = EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot->id]);

        $this->deleteJson("/api/estimasi-arrivals/{$arrival->id}")->assertStatus(204);
        $this->assertDatabaseMissing('estimasi_arrivals', ['id' => $arrival->id]);
        $this->assertDatabaseHas('type_slots', ['id' => $typeSlot->id]);
    }

    public function test_delete_returns_404_for_nonexistent()
    {
        $response = $this->deleteJson('/api/estimasi-arrivals/9999');
        $response->assertStatus(404);
    }
}
