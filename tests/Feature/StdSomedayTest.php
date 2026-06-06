<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\StdSomeday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StdSomedayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_std_somedays(): void
    {
        StdSomeday::factory()->count(3)->create();

        $response = $this->getJson('/api/std-somedays');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Berhasil mengambil data STD Someday')
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_std_someday(): void
    {
        $response = $this->postJson('/api/std-somedays', [
            'date_time' => '2026-06-06 10:00:00',
            'awb' => 'AWB1234567890',
            'status' => 'LMHub_Received',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data STD Someday berhasil ditambahkan')
            ->assertJsonPath('data.awb', 'AWB1234567890')
            ->assertJsonPath('data.status', 'LMHub_Received');

        $this->assertDatabaseHas('std_somedays', [
            'awb' => 'AWB1234567890',
            'status' => 'LMHub_Received',
        ]);

        // Test with the newly added Return_LMHub_Received status
        $response2 = $this->postJson('/api/std-somedays', [
            'date_time' => '2026-06-06 11:00:00',
            'awb' => 'AWB1234567891',
            'status' => 'Return_LMHub_Received',
        ]);

        $response2->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Return_LMHub_Received');

        $this->assertDatabaseHas('std_somedays', [
            'awb' => 'AWB1234567891',
            'status' => 'Return_LMHub_Received',
        ]);
    }

    public function test_can_create_std_someday_with_driver(): void
    {
        $driver = Driver::factory()->create();

        $response = $this->postJson('/api/std-somedays', [
            'date_time' => '2026-06-06 10:00:00',
            'awb' => 'AWB9999999999',
            'id_driver' => $driver->id,
            'status' => 'Delivering',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id_driver', $driver->id)
            ->assertJsonPath('data.status', 'Delivering');
    }

    public function test_can_show_std_someday(): void
    {
        $stdSomeday = StdSomeday::factory()->create();

        $response = $this->getJson("/api/std-somedays/{$stdSomeday->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Berhasil mengambil detail STD Someday')
            ->assertJsonPath('data.id', $stdSomeday->id);
    }

    public function test_can_update_std_someday(): void
    {
        $stdSomeday = StdSomeday::factory()->create([
            'status' => 'LMHub_Received',
        ]);

        $response = $this->putJson("/api/std-somedays/{$stdSomeday->id}", [
            'status' => 'Delivered',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data STD Someday berhasil diupdate')
            ->assertJsonPath('data.status', 'Delivered');

        $this->assertDatabaseHas('std_somedays', [
            'id' => $stdSomeday->id,
            'status' => 'Delivered',
        ]);
    }

    public function test_can_delete_std_someday(): void
    {
        $stdSomeday = StdSomeday::factory()->create();

        $response = $this->deleteJson("/api/std-somedays/{$stdSomeday->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data STD Someday berhasil dihapus');

        $this->assertDatabaseMissing('std_somedays', [
            'id' => $stdSomeday->id,
        ]);
    }

    public function test_store_validation_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/std-somedays', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_validation_fails_with_invalid_status(): void
    {
        $response = $this->postJson('/api/std-somedays', [
            'date_time' => '2026-06-06 10:00:00',
            'awb' => 'AWB1234567890',
            'status' => 'InvalidStatus',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_show_returns_404_for_nonexistent_record(): void
    {
        $response = $this->getJson('/api/std-somedays/99999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
