<?php

namespace App\Services;

use App\Models\CutoffInboun;
use App\Models\EstimasiArrival;
use App\Models\Inbound;
use App\Models\Projection;
use App\Models\TypeSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InboundAnalysisService
{
    /** @var Collection<int, CutoffInboun>|null */
    private ?Collection $activeCutoffs = null;

    /** @var Collection<int, EstimasiArrival>|null */
    private ?Collection $slotSchedules = null;

    /**
     * @return array<string, mixed>
     */
    public function classifyInbound(Inbound $inbound): array
    {
        $date = $this->resolveInboundDate($inbound);
        $time = $this->normalizeTime($inbound->actual_arrival);

        if ($time === null) {
            return [
                'inbound_id' => $inbound->id,
                'date_inbound' => $date,
                'actual_arrival' => null,
                'in_cycle' => false,
                'inbound_group' => null,
                'cutoff_inbound' => null,
                'type_slot' => null,
                'type_slot_id' => null,
            ];
        }

        $cutoff = $this->findCutoffForTime($time);
        $slotSchedule = $this->findSlotScheduleForTime($time);

        return [
            'inbound_id' => $inbound->id,
            'date_inbound' => $date,
            'actual_arrival' => $time,
            'in_cycle' => $cutoff !== null,
            'inbound_group' => $cutoff ? $this->getInboundGroupNumber($cutoff) : null,
            'cutoff_inbound' => $cutoff ? [
                'id' => $cutoff->id,
                'name' => $cutoff->name,
                'slug' => $cutoff->slug,
                'time_start' => $this->formatTime($cutoff->time_start),
                'time_end' => $this->formatTime($cutoff->time_end),
            ] : null,
            'type_slot' => $slotSchedule?->typeSlot ? [
                'id' => $slotSchedule->typeSlot->id,
                'name' => $slotSchedule->typeSlot->name,
                'slug' => $slotSchedule->typeSlot->slug,
            ] : null,
            'type_slot_id' => $slotSchedule?->type_slot_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dailyReport(string $date): array
    {
        $projection = Projection::query()
            ->whereDate('date_inbound', $date)
            ->first();

        $inbounds = Inbound::query()
            ->where(function ($query) use ($date) {
                $query->whereDate('date_inbound', $date)
                    ->orWhere(function ($fallback) use ($date) {
                        $fallback->whereNull('date_inbound')
                            ->whereDate('created_at', $date);
                    });
            })
            ->orderBy('actual_arrival')
            ->get();

        $classified = $inbounds->map(fn (Inbound $inbound) => array_merge(
            ['inbound' => $inbound],
            $this->classifyInbound($inbound)
        ));

        $slotTotals = $this->buildSlotTotals($classified);
        $cutoffTotals = $this->buildCutoffTotals($classified);
        $totalSlotOrders = collect($slotTotals)->sum('total_order');
        $target = $projection?->projected_inbound ?? 0;

        return [
            'date' => $date,
            'projection' => $projection,
            'projection_check' => [
                'projected_inbound' => $target,
                'actual_total_order_slot_1_3' => $totalSlotOrders,
                'meets_projection' => $projection !== null && $totalSlotOrders >= $target,
                'variance' => $totalSlotOrders - $target,
                'fulfillment_percentage' => $target > 0
                    ? round(($totalSlotOrders / $target) * 100, 2)
                    : null,
            ],
            'slots' => $slotTotals,
            'cutoff_groups' => $cutoffTotals,
            'inbounds' => $classified->values()->all(),
        ];
    }

    public function findCutoffForTime(string $time): ?CutoffInboun
    {
        return $this->getActiveCutoffs()
            ->first(fn (CutoffInboun $cutoff) => $this->timeInRange(
                $time,
                $this->formatTime($cutoff->time_start),
                $this->formatTime($cutoff->time_end),
            ));
    }

    public function getInboundGroupNumber(CutoffInboun $cutoff): int
    {
        $index = $this->getActiveCutoffs()->search(
            fn (CutoffInboun $item) => $item->id === $cutoff->id
        );

        return $index === false ? 1 : $index + 1;
    }

    public function findSlotScheduleForTime(string $time): ?EstimasiArrival
    {
        $schedules = $this->getSlotSchedules();

        if ($schedules->isEmpty()) {
            return null;
        }

        $matched = $schedules
            ->filter(fn (EstimasiArrival $schedule) => $this->formatTime($schedule->estimasi_arrival) <= $time)
            ->last();

        return $matched ?? $schedules->first();
    }

    /**
     * @return Collection<int, CutoffInboun>
     */
    private function getActiveCutoffs(): Collection
    {
        if ($this->activeCutoffs === null) {
            $this->activeCutoffs = CutoffInboun::query()
                ->where('is_active', true)
                ->orderBy('time_start')
                ->get();
        }

        return $this->activeCutoffs;
    }

    /**
     * @return Collection<int, EstimasiArrival>
     */
    private function getSlotSchedules(): Collection
    {
        if ($this->slotSchedules === null) {
            $this->slotSchedules = EstimasiArrival::query()
                ->with('typeSlot')
                ->where('status', true)
                ->whereHas('typeSlot', function ($query) {
                    $query->where('is_additional', false)
                        ->whereIn('slug', ['slot-1', 'slot-2', 'slot-3']);
                })
                ->orderBy('estimasi_arrival')
                ->get();
        }

        return $this->slotSchedules;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $classified
     * @return list<array<string, mixed>>
     */
    private function buildSlotTotals(Collection $classified): array
    {
        $slots = TypeSlot::query()
            ->whereIn('slug', ['slot-1', 'slot-2', 'slot-3'])
            ->orderBy('slug')
            ->get();

        return $slots->map(function (TypeSlot $slot) use ($classified) {
            $items = $classified->filter(
                fn (array $row) => ($row['type_slot']['slug'] ?? null) === $slot->slug
            );

            return [
                'type_slot_id' => $slot->id,
                'name' => $slot->name,
                'slug' => $slot->slug,
                'inbound_count' => $items->count(),
                'total_order' => (int) $items->sum(fn (array $row) => $row['inbound']->total_order),
                'total_bulky' => (int) $items->sum(fn (array $row) => $row['inbound']->bulky),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $classified
     * @return list<array<string, mixed>>
     */
    private function buildCutoffTotals(Collection $classified): array
    {
        return $this->getActiveCutoffs()
            ->map(function (CutoffInboun $cutoff) use ($classified) {
                $group = $this->getInboundGroupNumber($cutoff);
                $items = $classified->filter(
                    fn (array $row) => ($row['inbound_group'] ?? null) === $group
                );

                return [
                    'inbound_group' => $group,
                    'cutoff_inbound' => [
                        'id' => $cutoff->id,
                        'name' => $cutoff->name,
                        'slug' => $cutoff->slug,
                        'time_start' => $this->formatTime($cutoff->time_start),
                        'time_end' => $this->formatTime($cutoff->time_end),
                    ],
                    'inbound_count' => $items->count(),
                    'total_order' => (int) $items->sum(fn (array $row) => $row['inbound']->total_order),
                    'total_bulky' => (int) $items->sum(fn (array $row) => $row['inbound']->bulky),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveInboundDate(Inbound $inbound): string
    {
        if ($inbound->date_inbound) {
            return Carbon::parse($inbound->date_inbound)->toDateString();
        }

        return $inbound->created_at->toDateString();
    }

    private function normalizeTime(mixed $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return $this->formatTime($time);
    }

    private function formatTime(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }

        $value = (string) $time;

        if (strlen($value) === 5) {
            return $value.':00';
        }

        return Carbon::parse($value)->format('H:i:s');
    }

    private function timeInRange(string $time, string $start, string $end): bool
    {
        $t = strtotime($time);
        $s = strtotime($start);
        $e = strtotime($end);

        if ($s <= $e) {
            return $t >= $s && $t <= $e;
        }

        return $t >= $s || $t <= $e;
    }
}
