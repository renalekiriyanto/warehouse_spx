<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['date_time', 'awb', 'id_driver', 'status'])]
class StdSomeday extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_time' => 'datetime',
        ];
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'id_driver', 'id');
    }
}
