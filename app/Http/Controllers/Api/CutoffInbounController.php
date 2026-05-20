<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCutoffInbounRequest;
use App\Http\Requests\UpdateCutoffInbounRequest;
use App\Models\CutoffInboun;

class CutoffInbounController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(CutoffInboun::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCutoffInbounRequest $request)
    {
        $cutoffInboun = CutoffInboun::create($request->validated());
        return response()->json($cutoffInboun, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CutoffInboun $cutoffInboun)
    {
        return response()->json($cutoffInboun);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCutoffInbounRequest $request, CutoffInboun $cutoffInboun)
    {
        $cutoffInboun->update($request->validated());
        return response()->json($cutoffInboun);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CutoffInboun $cutoffInboun)
    {
        $cutoffInboun->delete();
        return response()->json(null, 204);
    }
}
