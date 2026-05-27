<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCutoffInbounRequest;
use App\Http\Requests\UpdateCutoffInbounRequest;
use App\Models\CutoffInboun;

class CutoffInbounController extends Controller
{
    public function index()
    {
        return $this->successResponse('Berhasil mengambil data cutoff inbound', CutoffInboun::all());
    }

    public function store(StoreCutoffInbounRequest $request)
    {
        $cutoffInboun = CutoffInboun::create($request->validated());
        return $this->successResponse('Data cutoff inbound berhasil ditambahkan', $cutoffInboun, 201);
    }

    public function show(CutoffInboun $cutoffInbound)
    {
        return $this->successResponse('Berhasil mengambil detail cutoff inbound', $cutoffInbound);
    }

    public function update(UpdateCutoffInbounRequest $request, CutoffInboun $cutoffInbound)
    {
        $cutoffInbound->update($request->validated());
        return $this->successResponse('Data cutoff inbound berhasil diupdate', $cutoffInbound);
    }

    public function destroy(CutoffInboun $cutoffInbound)
    {
        $cutoffInbound->delete();
        return $this->successResponse('Data cutoff inbound berhasil dihapus');
    }
}
