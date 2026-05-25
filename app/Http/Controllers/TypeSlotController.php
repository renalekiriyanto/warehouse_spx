<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTypeSlotRequest;
use App\Http\Requests\UpdateTypeSlotRequest;
use App\Models\TypeSlot;

class TypeSlotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(TypeSlot::with('estimasiArrivals')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTypeSlotRequest $request)
    {
        $typeSlot = TypeSlot::create($request->validated());
        return response()->json($typeSlot, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TypeSlot $typeSlot)
    {
        return response()->json($typeSlot->load('estimasiArrivals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTypeSlotRequest $request, TypeSlot $typeSlot)
    {
        $typeSlot->update($request->validated());
        return response()->json($typeSlot);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TypeSlot $typeSlot)
    {
        $typeSlot->delete();
        return response()->json(null, 204);
    }
}
