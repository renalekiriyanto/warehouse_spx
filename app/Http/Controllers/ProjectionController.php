<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectionRequest;
use App\Http\Requests\UpdateProjectionRequest;
use App\Models\Projection;

class ProjectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Projection::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectionRequest $request)
    {
        $projection = Projection::create($request->validated());
        return response()->json($projection, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Projection $projection)
    {
        return response()->json($projection);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectionRequest $request, Projection $projection)
    {
        $projection->update($request->validated());
        return response()->json($projection);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Projection $projection)
    {
        $projection->delete();
        return response()->json(null, 204);
    }
}
