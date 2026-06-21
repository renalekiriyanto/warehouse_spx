<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['date', 'arrival', 'departed', 'awb', 'lt_number', 'to_number', 'driver_lh', 'vehicle_number', 'sku_name'])]
class Damage extends Model
{
    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByAwb($query, $awb)
    {
        return $query->when(
            filled($awb),
            fn ($q) => $q->where('awb', trim($awb))
        );
    }
}
