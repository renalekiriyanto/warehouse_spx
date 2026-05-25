<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstimasiArrivalRequest;
use App\Http\Requests\UpdateEstimasiArrivalRequest;
use App\Models\EstimasiArrival;

class EstimasiArrivalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(EstimasiArrival::with('typeSlot')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstimasiArrivalRequest $request)
    {
        $estimasiArrival = EstimasiArrival::create($request->validated());
        return response()->json($estimasiArrival->load('typeSlot'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(EstimasiArrival $estimasiArrival)
    {
        return response()->json($estimasiArrival->load('typeSlot'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEstimasiArrivalRequest $request, EstimasiArrival $estimasiArrival)
    {
        $estimasiArrival->update($request->validated());
        return response()->json($estimasiArrival->load('typeSlot'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EstimasiArrival $estimasiArrival)
    {
        $estimasiArrival->delete();
        return response()->json(null, 204);
    }
}
