<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstimasiArrivalRequest;
use App\Http\Requests\UpdateEstimasiArrivalRequest;
use App\Models\EstimasiArrival;

class EstimasiArrivalController extends Controller
{
    public function index()
    {
        return $this->successResponse('Berhasil mengambil data estimasi arrival', EstimasiArrival::with('typeSlot')->get());
    }

    public function store(StoreEstimasiArrivalRequest $request)
    {
        $estimasiArrival = EstimasiArrival::create($request->validated());
        return $this->successResponse('Data estimasi arrival berhasil ditambahkan', $estimasiArrival->load('typeSlot'), 201);
    }

    public function show(EstimasiArrival $estimasiArrival)
    {
        return $this->successResponse('Berhasil mengambil detail estimasi arrival', $estimasiArrival->load('typeSlot'));
    }

    public function update(UpdateEstimasiArrivalRequest $request, EstimasiArrival $estimasiArrival)
    {
        $estimasiArrival->update($request->validated());
        return $this->successResponse('Data estimasi arrival berhasil diupdate', $estimasiArrival->load('typeSlot'));
    }

    public function destroy(EstimasiArrival $estimasiArrival)
    {
        $estimasiArrival->delete();
        return $this->successResponse('Data estimasi arrival berhasil dihapus');
    }
}
