<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = \App\Models\Driver::with(['vehicleType', 'agency'])->get();
        return $this->successResponse('Berhasil mengambil data driver', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_driver' => 'required|string|unique:drivers,id_driver',
            'name' => 'required|string',
            'id_vehicle_type' => 'required|integer|exists:vehicle_types,id',
            'id_agency' => 'required|integer|exists:agencies,id',
        ]);

        $driver = \App\Models\Driver::create($validated);
        return $this->successResponse('Data driver berhasil ditambahkan', $driver, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $driver = \App\Models\Driver::with(['vehicleType', 'agency'])->findOrFail($id);
        return $this->successResponse('Berhasil mengambil data driver', $driver);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'id_driver' => 'required|string|unique:drivers,id_driver,' . $id,
            'name' => 'required|string',
            'id_vehicle_type' => 'required|integer|exists:vehicle_types,id',
            'id_agency' => 'required|integer|exists:agencies,id',
        ]);

        $driver = \App\Models\Driver::findOrFail($id);
        $driver->update($validated);
        return $this->successResponse('Data driver berhasil diperbarui', $driver);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $driver = \App\Models\Driver::findOrFail($id);
        $driver->delete();
        return $this->successResponse('Data driver berhasil dihapus', null);
    }

    public function importData(Request $request)
    {
        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        foreach ($data as $index => $value) {
            if($index === 0) continue; // Skip header row
            // Finding id vehicle type by slug
            $vehicleType = \App\Models\VehicleType::where('slug', $value[2])->first();
            $value[2] = $vehicleType ? $vehicleType->id : null;
            // Finding id agency by slug
            $agency = \App\Models\Agency::where('name', $value[4])->first();
            if(!$agency) {
                $agency = \App\Models\Agency::create([
                    'name' => $value[4],
                    'slug' => Str::slug($value[4]),
                ]);
            }

            $status = strtolower($value[5]) ?? 'active';
            $driverData = [
                'id_driver' => $value[0],
                'name' => $value[1],
                'id_vehicle_type' => $value[2],
                'id_agency' => $agency ? $agency->id : null,
                'contract_type' => $value[3] ?? null,
                'status' => $status,
            ];
            \App\Models\Driver::updateOrCreate(
                $driverData
            );
        }

        return $this->successResponse('Data driver berhasil diimpor', null);
    }
}
