<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTypeSlotRequest;
use App\Http\Requests\UpdateTypeSlotRequest;
use App\Models\TypeSlot;

class TypeSlotController extends Controller
{
    public function index()
    {
        return $this->successResponse('Berhasil mengambil data type slot', TypeSlot::with('estimasiArrivals')->get());
    }

    public function store(StoreTypeSlotRequest $request)
    {
        $typeSlot = TypeSlot::create($request->validated());
        return $this->successResponse('Data type slot berhasil ditambahkan', $typeSlot, 201);
    }

    public function show(TypeSlot $typeSlot)
    {
        return $this->successResponse('Berhasil mengambil detail type slot', $typeSlot->load('estimasiArrivals'));
    }

    public function update(UpdateTypeSlotRequest $request, TypeSlot $typeSlot)
    {
        $typeSlot->update($request->validated());
        return $this->successResponse('Data type slot berhasil diupdate', $typeSlot);
    }

    public function destroy(TypeSlot $typeSlot)
    {
        $typeSlot->delete();
        return $this->successResponse('Data type slot berhasil dihapus');
    }
}
