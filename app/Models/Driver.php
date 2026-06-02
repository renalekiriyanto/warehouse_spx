<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['id_driver', 'name', 'id_vehicle_type', 'id_agency', 'contract_type', 'status'])]
class Driver extends Model
{
    use HasFactory;

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'id_vehicle_type');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'id_agency');
    }
}
