<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\TypeSlot;
use App\Models\EstimasiArrival;

class TypeSlotTest extends TestCase
{
    use RefreshDatabase;

    // ── INDEX ──────────────────────────────────────────────

    public function test_can_list_type_slots()
    {
        TypeSlot::factory(3)->create();
        $response = $this->getJson('/api/type-slots');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_returns_empty_array_when_no_type_slots()
    {
        $response = $this->getJson('/api/type-slots');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_index_includes_estimasi_arrivals_relation()
    {
        $typeSlot = TypeSlot::factory()->create();
        EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot->id]);

        $response = $this->getJson('/api/type-slots');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'is_additional', 'estimasi_arrivals'],
                ],
            ]);
    }

    // ── STORE ─────────────────────────────────────────────

    public function test_can_create_type_slot()
    {
        $payload = [
            'name' => 'Premium Slot',
            'is_additional' => true,
        ];
        $response = $this->postJson('/api/type-slots', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 201)
            ->assertJsonFragment(['name' => 'Premium Slot', 'is_additional' => true]);
        $this->assertDatabaseHas('type_slots', ['slug' => 'premium-slot', 'is_additional' => 1]);
    }

    public function test_store_auto_generates_slug_from_name()
    {
        $payload = ['name' => 'My Special Slot'];
        $response = $this->postJson('/api/type-slots', $payload);
        $response->assertStatus(201)
            ->assertJsonFragment(['slug' => 'my-special-slot']);
    }

    public function test_store_fails_without_name()
    {
        $response = $this->postJson('/api/type-slots', []);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_store_fails_with_duplicate_slug()
    {
        TypeSlot::factory()->create(['name' => 'Duplicate', 'slug' => 'duplicate']);
        $payload = ['name' => 'Duplicate'];
        $response = $this->postJson('/api/type-slots', $payload);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_store_defaults_is_additional_to_false()
    {
        $payload = ['name' => 'Default Additional'];
        $response = $this->postJson('/api/type-slots', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('type_slots', [
            'slug' => 'default-additional',
            'is_additional' => 0,
        ]);
    }

    // ── SHOW ──────────────────────────────────────────────

    public function test_can_show_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $response = $this->getJson("/api/type-slots/{$typeSlot->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonFragment(['id' => $typeSlot->id]);
    }

    public function test_show_returns_404_for_nonexistent_type_slot()
    {
        $response = $this->getJson('/api/type-slots/9999');
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }

    public function test_show_includes_estimasi_arrivals()
    {
        $typeSlot = TypeSlot::factory()->create();
        EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot->id]);

        $response = $this->getJson("/api/type-slots/{$typeSlot->id}");
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['estimasi_arrivals']]);
    }

    // ── UPDATE ────────────────────────────────────────────

    public function test_can_update_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = ['name' => 'Updated Slot Name'];
        $response = $this->putJson("/api/type-slots/{$typeSlot->id}", $payload);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Updated Slot Name']);
        $this->assertDatabaseHas('type_slots', ['id' => $typeSlot->id, 'name' => 'Updated Slot Name']);
    }

    public function test_update_regenerates_slug_when_name_changes()
    {
        $typeSlot = TypeSlot::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);
        $payload = ['name' => 'New Name'];
        $response = $this->putJson("/api/type-slots/{$typeSlot->id}", $payload);
        $response->assertStatus(200)
            ->assertJsonFragment(['slug' => 'new-name']);
        $this->assertDatabaseHas('type_slots', ['id' => $typeSlot->id, 'slug' => 'new-name']);
    }

    public function test_update_fails_with_duplicate_slug()
    {
        TypeSlot::factory()->create(['name' => 'Existing', 'slug' => 'existing']);
        $typeSlot = TypeSlot::factory()->create(['name' => 'Another', 'slug' => 'another']);

        $payload = ['name' => 'Existing'];
        $response = $this->putJson("/api/type-slots/{$typeSlot->id}", $payload);
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);
    }

    public function test_update_returns_404_for_nonexistent()
    {
        $response = $this->putJson('/api/type-slots/9999', ['name' => 'Nope']);
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }

    // ── DELETE ─────────────────────────────────────────────

    public function test_can_delete_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $response = $this->deleteJson("/api/type-slots/{$typeSlot->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Data type slot berhasil dihapus');
        $this->assertDatabaseMissing('type_slots', ['id' => $typeSlot->id]);
    }

    public function test_delete_cascades_to_estimasi_arrivals()
    {
        $typeSlot = TypeSlot::factory()->create();
        $arrival = EstimasiArrival::factory()->create(['type_slot_id' => $typeSlot->id]);

        $this->deleteJson("/api/type-slots/{$typeSlot->id}")->assertStatus(200);
        $this->assertDatabaseMissing('type_slots', ['id' => $typeSlot->id]);
        $this->assertDatabaseMissing('estimasi_arrivals', ['id' => $arrival->id]);
    }

    public function test_delete_returns_404_for_nonexistent()
    {
        $response = $this->deleteJson('/api/type-slots/9999');
        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 404);
    }
}
