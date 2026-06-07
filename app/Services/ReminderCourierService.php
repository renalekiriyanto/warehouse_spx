<?php

namespace App\Services;

use App\Models\StdSomeday;

class ReminderCourierService
{
    public function getLatestStdSomeday()
    {
        $today = now()->toDateString();

        // 1 query untuk semua count per driver sekaligus
        $counts = StdSomeday::whereDate('date_time', $today)
            ->selectRaw('
            id_driver,
            MAX(date_time) as date_time,
            COUNT(*) as awb_count,
            SUM(CASE WHEN status = "Delivering" THEN 1 ELSE 0 END) as delivering,
            SUM(CASE WHEN status = "OnHold"     THEN 1 ELSE 0 END) as onhold,
            SUM(CASE WHEN status = "Delivered"  THEN 1 ELSE 0 END) as delivered
        ')
            ->groupBy('id_driver')
            ->with('driver')
            ->get();

        return $counts->map(function ($item) {
            $awb_count = $item->awb_count;
            $onhold    = $item->onhold;
            $delivered = $item->delivered;

            return [
                'driver_id'           => $item->id_driver,
                'driver_name'         => $item->driver?->name ?? 'Unknown',
                'date'                => \Carbon\Carbon::parse($item->date_time)
                    ->timezone('Asia/Jakarta')
                    ->format('d/m/Y'),
                'time'                => \Carbon\Carbon::parse($item->date_time)
                    ->timezone('Asia/Jakarta')
                    ->format('H:i'),
                'awb_count'           => $awb_count,
                'delivering'          => $item->delivering,
                'onhold'              => $onhold,
                'delivered'           => $delivered,
                'pct_delivered'       => $awb_count > 0
                    ? round($delivered / $awb_count * 100, 2) : 0,
                'pct_done_delivering' => $awb_count > 0
                    ? round(($onhold + $delivered) / $awb_count * 100, 2) : 0,
            ];
        })
            ->sortBy('pct_done_delivering')
            ->values();
    }
}
