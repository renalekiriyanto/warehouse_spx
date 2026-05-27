<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbound extends Model
{
    /** @use HasFactory<\Database\Factories\InboundFactory> */
    use HasFactory;

    protected $fillable = [
        'actual_arrival',
        'bulky',
        'total_order',
    ];
}
