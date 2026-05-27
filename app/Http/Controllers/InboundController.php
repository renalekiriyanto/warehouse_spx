<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInboundRequest;
use App\Http\Requests\UpdateInboundRequest;
use App\Models\Inbound;

class InboundController extends Controller
{
    public function index()
    {
        return $this->successResponse('Berhasil mengambil data inbound', Inbound::all());
    }

    public function store(StoreInboundRequest $request)
    {
        $inbound = Inbound::create($request->validated());
        return $this->successResponse('Data inbound berhasil ditambahkan', $inbound, 201);
    }

    public function show(Inbound $inbound)
    {
        return $this->successResponse('Berhasil mengambil detail inbound', $inbound);
    }

    public function update(UpdateInboundRequest $request, Inbound $inbound)
    {
        $inbound->update($request->validated());
        return $this->successResponse('Data inbound berhasil diupdate', $inbound);
    }

    public function destroy(Inbound $inbound)
    {
        $inbound->delete();
        return $this->successResponse('Data inbound berhasil dihapus');
    }
}
