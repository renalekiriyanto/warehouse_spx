<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportDispatcherService;

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
     * Show the form for creating a new resource — not used in API context.
     */
    public function create(): void {}

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
     * Show the form for editing — not used in API context.
     */
    public function edit(string $id): void {}

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
        $request->validate([
            'file' => 'required|mimes:csv|max:51200',
        ]);

        try {
            $dispatcher = app(ImportDispatcherService::class);
            $batch = $dispatcher->dispatch($request->file('file'), 'driver');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses file import driver.', [
                'error' => $e->getMessage(),
            ], 500);
        }

        return $this->successResponse('File driver berhasil diunggah dan sedang diproses.', [
            'uuid'         => $batch->uuid,
            'status'       => $batch->status,
            'status_label' => $batch->status_label,
            'total_rows'   => $batch->total_rows,
        ], 202);
    }
}
