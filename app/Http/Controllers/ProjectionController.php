<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectionRequest;
use App\Http\Requests\UpdateProjectionRequest;
use App\Models\Projection;
use App\Services\ImportDispatcherService;
use Illuminate\Http\Request;

class ProjectionController extends Controller
{
    public function __construct(
        private readonly ImportDispatcherService $importDispatcher,
    ) {}
    public function index()
    {
        return $this->successResponse('Berhasil mengambil data projection', Projection::all());
    }

    public function store(StoreProjectionRequest $request)
    {
        $projection = Projection::create($request->validated());
        return $this->successResponse('Data projection berhasil ditambahkan', $projection, 201);
    }

    public function show(Projection $projection)
    {
        return $this->successResponse('Berhasil mengambil detail projection', $projection);
    }

    public function update(UpdateProjectionRequest $request, Projection $projection)
    {
        $projection->update($request->validated());
        return $this->successResponse('Data projection berhasil diupdate', $projection);
    }

    public function destroy(Projection $projection)
    {
        $projection->delete();
        return $this->successResponse('Data projection berhasil dihapus');
    }

    /**
     * Upload and import Projection data from CSV/Excel (async, queue-based).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:51200',
        ]);

        try {
            $batch = $this->importDispatcher->dispatch($request->file('file'), 'projection');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses file upload projection.', [
                'error' => $e->getMessage(),
            ], 500);
        }

        return $this->successResponse('File projection berhasil diunggah dan sedang diproses.', [
            'uuid'         => $batch->uuid,
            'status'       => $batch->status,
            'status_label' => $batch->status_label,
            'total_rows'   => $batch->total_rows,
        ], 202);
    }
}
