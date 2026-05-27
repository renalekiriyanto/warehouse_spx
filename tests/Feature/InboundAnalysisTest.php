<?php

namespace Tests\Feature;

use App\Models\CutoffInboun;
use App\Models\EstimasiArrival;
use App\Models\Inbound;
use App\Models\Projection;
use App\Models\TypeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private string $date = '2026-05-27';

    protected function setUp(): void
    {
        parent::setUp();

        $slot1 = TypeSlot::create(['name' => 'Slot 1', 'slug' => 'slot-1', 'is_additional' => false]);
        $slot2 = TypeSlot::create(['name' => 'Slot 2', 'slug' => 'slot-2', 'is_additional' => false]);
        $slot3 = TypeSlot::create(['name' => 'Slot 3', 'slug' => 'slot-3', 'is_additional' => false]);

        EstimasiArrival::create(['type_slot_id' => $slot1->id, 'estimasi_arrival' => '08:00:00', 'status' => true]);
        EstimasiArrival::create(['type_slot_id' => $slot2->id, 'estimasi_arrival' => '12:00:00', 'status' => true]);
        EstimasiArrival::create(['type_slot_id' => $slot3->id, 'estimasi_arrival' => '16:00:00', 'status' => true]);

        CutoffInboun::create([
            'name' => 'Cycle Pagi',
            'slug' => 'cycle-pagi',
            'is_active' => true,
            'time_start' => '06:00:00',
            'time_end' => '11:59:59',
        ]);
        CutoffInboun::create([
            'name' => 'Cycle Sore',
            'slug' => 'cycle-sore',
            'is_active' => true,
            'time_start' => '12:00:00',
            'time_end' => '17:59:59',
        ]);
    }

    public function test_cycle_context_marks_inbound_inside_cutoff_and_group(): void
    {
        $inbound = Inbound::create([
            'date_inbound' => $this->date,
            'actual_arrival' => '09:30:00',
            'bulky' => 2,
            'total_order' => 50,
        ]);

        $response = $this->getJson("/api/inbounds/{$inbound->id}/cycle");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.in_cycle', true)
            ->assertJsonPath('data.inbound_group', 1)
            ->assertJsonPath('data.cutoff_inbound.slug', 'cycle-pagi')
            ->assertJsonPath('data.type_slot.slug', 'slot-1');
    }

    public function test_cycle_context_marks_outside_cutoff_when_time_not_in_range(): void
    {
        $inbound = Inbound::create([
            'date_inbound' => $this->date,
            'actual_arrival' => '23:00:00',
            'bulky' => 1,
            'total_order' => 10,
        ]);

        $response = $this->getJson("/api/inbounds/{$inbound->id}/cycle");

        $response->assertStatus(200)
            ->assertJsonPath('data.in_cycle', false)
            ->assertJsonPath('data.inbound_group', null);
    }

    public function test_daily_analysis_checks_projection_fulfillment_for_slot_1_to_3(): void
    {
        Projection::create([
            'projected_inbound' => 300,
            'date_inbound' => $this->date,
        ]);

        Inbound::create([
            'date_inbound' => $this->date,
            'actual_arrival' => '08:30:00',
            'bulky' => 1,
            'total_order' => 100,
        ]);
        Inbound::create([
            'date_inbound' => $this->date,
            'actual_arrival' => '12:30:00',
            'bulky' => 1,
            'total_order' => 120,
        ]);
        Inbound::create([
            'date_inbound' => $this->date,
            'actual_arrival' => '16:30:00',
            'bulky' => 1,
            'total_order' => 90,
        ]);

        $response = $this->getJson('/api/inbounds/analysis/daily?date='.$this->date);

        $response->assertStatus(200)
            ->assertJsonPath('data.projection_check.projected_inbound', 300)
            ->assertJsonPath('data.projection_check.actual_total_order_slot_1_3', 310)
            ->assertJsonPath('data.projection_check.meets_projection', true)
            ->assertJsonPath('data.projection_check.variance', 10)
            ->assertJsonCount(3, 'data.slots');
    }

    public function test_daily_analysis_requires_date_parameter(): void
    {
        $response = $this->getJson('/api/inbounds/analysis/daily');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
