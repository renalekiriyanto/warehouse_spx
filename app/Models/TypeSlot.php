<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'is_additional'])]
class TypeSlot extends Model
{
    /** @use HasFactory<\Database\Factories\TypeSlotFactory> */
    use HasFactory;

    public function estimasiArrivals()
    {
        return $this->hasMany(EstimasiArrival::class);
    }
}
