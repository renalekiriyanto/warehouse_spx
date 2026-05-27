<?php

namespace App\Http\Controllers;

use App\Http\Requests\InboundDailyAnalysisRequest;
use App\Http\Requests\StoreInboundRequest;
use App\Http\Requests\UpdateInboundRequest;
use App\Models\Inbound;
use App\Services\InboundAnalysisService;

class InboundController extends Controller
{
    public function __construct(
        private readonly InboundAnalysisService $inboundAnalysisService,
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
     * Upload and import Inbound data from CSV/Excel.
     */
    public function upload(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:10240',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\InboundImport,
                $request->file('file')
            );
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errors = [];
            foreach ($e->failures() as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            }

            return $this->errorResponse('Validasi file gagal. Periksa format data pada baris yang dilaporkan.', $errors, 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses file upload inbound.', [
                'error' => $e->getMessage(),
            ], 500);
        }

        return $this->successResponse('File inbound berhasil diunggah dan diproses.', null, 201);
    }
}
