<?php

namespace App\Services;

use App\Models\StdSomeday;
use Carbon\Carbon;

class StdSomedayService
{
    public function fetchAll($startDate = null, $endDate = null)
    {
        $query = StdSomeday::with('driver');

        if ($startDate) {
            $query->whereDate('date_time', '>=', Carbon::parse($startDate)->format('Y-m-d'));
        }

        if ($endDate) {
            $query->whereDate('date_time', '<=', Carbon::parse($endDate)->format('Y-m-d'));
        }

        $data = $query->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'awb'         => $item->awb,
                    'status'      => $item->status,
                    'date'        => \Carbon\Carbon::parse($item->date_time)->format('d/m/Y'),
                    'time'        => \Carbon\Carbon::parse($item->date_time)->format('H:i:s'),
                    'driver_id'   => $item->id_driver,
                    'driver_name' => $item->driver?->name ?? 'Unknown',
                ];
            });

        return $data;
    }
}
