<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'is_active', 'time_start', 'time_end'])]
class CutoffInboun extends Model
{
    use HasFactory;
}
