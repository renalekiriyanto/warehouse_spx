<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimasiArrival extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_slot_id',
        'estimasi_arrival',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function typeSlot()
    {
        return $this->belongsTo(TypeSlot::class);
    }
}
