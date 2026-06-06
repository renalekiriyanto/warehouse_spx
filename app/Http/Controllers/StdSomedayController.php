<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStdSomedayRequest;
use App\Http\Requests\UpdateStdSomedayRequest;
use App\Models\StdSomeday;
use App\Services\ImportDispatcherService;
use Illuminate\Http\Request;

class StdSomedayController extends Controller
{
    public function __construct(
        private readonly ImportDispatcherService $importDispatcher,
    ) {}
    public function index()
    {
        return $this->successResponse(
            'Berhasil mengambil data STD Someday',
            StdSomeday::with('driver')->get()
        );
    }

    public function store(StoreStdSomedayRequest $request)
    {
        $stdSomeday = StdSomeday::create($request->validated());

        return $this->successResponse(
            'Data STD Someday berhasil ditambahkan',
            $stdSomeday->load('driver'),
            201
        );
    }

    public function show(StdSomeday $stdSomeday)
    {
        return $this->successResponse(
            'Berhasil mengambil detail STD Someday',
            $stdSomeday->load('driver')
        );
    }

    public function update(UpdateStdSomedayRequest $request, StdSomeday $stdSomeday)
    {
        $stdSomeday->update($request->validated());

        return $this->successResponse(
            'Data STD Someday berhasil diupdate',
            $stdSomeday->load('driver')
        );
    }

    public function destroy(StdSomeday $stdSomeday)
    {
        $stdSomeday->delete();

        return $this->successResponse('Data STD Someday berhasil dihapus');
    }

    /**
     * Upload and import STD Someday data from CSV/Excel (async, queue-based).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:51200',
        ]);

        try {
            $batch = $this->importDispatcher->dispatch($request->file('file'), 'std_someday');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses file upload STD Someday.', [
                'error' => $e->getMessage(),
            ], 500);
        }

        return $this->successResponse('File STD Someday berhasil diunggah dan sedang diproses.', [
            'uuid'         => $batch->uuid,
            'status'       => $batch->status,
            'status_label' => $batch->status_label,
            'total_rows'   => $batch->total_rows,
        ], 202);
    }
}
