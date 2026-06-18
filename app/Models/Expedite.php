<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['date_time', 'awb', 'id_driver', 'status', 'current_station'])]
class Expedite extends Model
{
    use HasFactory;

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'id_driver', 'id_driver');
    }
}
