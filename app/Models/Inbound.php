<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbound extends Model
{
    /** @use HasFactory<\Database\Factories\InboundFactory> */
    use HasFactory;

    protected $fillable = [
        'id_type_slot',
        'date_inbound',
        'actual_arrival',
        'total_order',
    ];

    protected $casts = [
        'date_inbound' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Inbound $inbound) {
            if (! $inbound->date_inbound) {
                $inbound->date_inbound = now()->toDateString();
            }
        });
    }
}
