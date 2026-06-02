<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = \App\Models\Agency::all();
        return $this->successResponse('Berhasil mengambil data agen', $data);
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
            'slug' => 'required|string|unique:agencies,slug',
        ]);

        $agency = \App\Models\Agency::create($validated);
        return $this->successResponse('Data agen berhasil ditambahkan', $agency, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $agency = \App\Models\Agency::findOrFail($id);
        return $this->successResponse('Berhasil mengambil data agen', $agency);
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
            'slug' => 'required|string|unique:agencies,slug,' . $id,
        ]);

        $agency = \App\Models\Agency::findOrFail($id);
        $agency->update($validated);
        return $this->successResponse('Data agen berhasil diperbarui', $agency);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $agency = \App\Models\Agency::findOrFail($id);
        $agency->delete();
        return $this->successResponse('Data agen berhasil dihapus', null);
    }
}
