<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['projected_inbound', 'date_inbound'])]
class Projection extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectionFactory> */
    use HasFactory;
}
