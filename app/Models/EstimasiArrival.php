<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimasiArrival extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_slot_id',
        'time_start',
        'time_end',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function typeSlot()
    {
        return $this->belongsTo(TypeSlot::class);
    }
}
