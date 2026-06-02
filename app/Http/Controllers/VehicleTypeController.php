<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = \App\Models\VehicleType::all();
        return $this->successResponse('Berhasil mengambil data jenis kendaraan', $data);
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
            'name' => 'required|string',
            'slug' => 'required|string|unique:vehicle_types,slug',
        ]);

        $vehicleType = \App\Models\VehicleType::create($validated);
        return $this->successResponse('Data jenis kendaraan berhasil ditambahkan', $vehicleType, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $vehicleType = \App\Models\VehicleType::findOrFail($id);
        return $this->successResponse('Berhasil mengambil data jenis kendaraan', $vehicleType);
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
            'name' => 'required|string',
            'slug' => 'required|string|unique:vehicle_types,slug,' . $id,
        ]);

        $vehicleType = \App\Models\VehicleType::findOrFail($id);
        $vehicleType->update($validated);
        return $this->successResponse('Data jenis kendaraan berhasil diperbarui', $vehicleType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vehicleType = \App\Models\VehicleType::findOrFail($id);
        $vehicleType->delete();
        return $this->successResponse('Data jenis kendaraan berhasil dihapus', null);
    }
}
