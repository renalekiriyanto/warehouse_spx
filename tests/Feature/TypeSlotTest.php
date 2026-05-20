<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\TypeSlot;

class TypeSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_type_slots()
    {
        TypeSlot::factory(3)->create();
        $response = $this->getJson('/api/type-slots');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_type_slot()
    {
        $payload = [
            'name' => 'Premium Slot',
            'slug' => 'premium-slot',
            'is_additional' => true,
        ];
        $response = $this->postJson('/api/type-slots', $payload);
        $response->assertStatus(201)->assertJsonFragment(['name' => 'Premium Slot', 'is_additional' => true]);
        $this->assertDatabaseHas('type_slots', ['slug' => 'premium-slot', 'is_additional' => 1]);
    }

    public function test_can_show_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $response = $this->getJson("/api/type-slots/{$typeSlot->id}");
        $response->assertStatus(200)->assertJsonFragment(['id' => $typeSlot->id]);
    }

    public function test_can_update_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $payload = ['name' => 'Updated Slot Name'];
        $response = $this->putJson("/api/type-slots/{$typeSlot->id}", $payload);
        $response->assertStatus(200)->assertJsonFragment(['name' => 'Updated Slot Name']);
        $this->assertDatabaseHas('type_slots', ['id' => $typeSlot->id, 'name' => 'Updated Slot Name']);
    }

    public function test_can_delete_type_slot()
    {
        $typeSlot = TypeSlot::factory()->create();
        $response = $this->deleteJson("/api/type-slots/{$typeSlot->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('type_slots', ['id' => $typeSlot->id]);
    }
}
