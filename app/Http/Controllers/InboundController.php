<?php

namespace App\Http\Controllers;

use App\Http\Requests\InboundDailyAnalysisRequest;
use App\Http\Requests\StoreInboundRequest;
use App\Http\Requests\UpdateInboundRequest;
use App\Models\Inbound;
use App\Services\ImportDispatcherService;
use App\Services\InboundAnalysisService;

class InboundController extends Controller
{
    public function __construct(
        private readonly InboundAnalysisService $inboundAnalysisService,
        private readonly ImportDispatcherService $importDispatcher,
    ) {}

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

    /**
     * Cek cycle / inbound group / slot untuk satu data inbound.
     */
    public function cycleContext(Inbound $inbound)
    {
        return $this->successResponse(
            'Berhasil menganalisis cycle dan group inbound.',
            $this->inboundAnalysisService->classifyInbound($inbound),
        );
    }

    /**
     * Ringkasan harian: slot 1-3 vs projection + grouping cutoff.
     */
    public function dailyAnalysis(InboundDailyAnalysisRequest $request)
    {
        $report = $this->inboundAnalysisService->dailyReport($request->validated('date'));

        return $this->successResponse(
            'Berhasil menganalisis inbound harian dan pengecekan projection.',
            $report,
        );
    }

    /**
     * Upload and import Inbound data from CSV/Excel (async, queue-based).
     */
    public function upload(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:51200', // 50 MB
        ]);

        try {
            $batch = $this->importDispatcher->dispatch($request->file('file'), 'inbound');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses file upload inbound.', [
                'error' => $e->getMessage(),
            ], 500);
        }

        return $this->successResponse('File inbound berhasil diunggah dan sedang diproses.', [
            'uuid'         => $batch->uuid,
            'status'       => $batch->status,
            'status_label' => $batch->status_label,
            'total_rows'   => $batch->total_rows,
        ], 202);
    }
}
