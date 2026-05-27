<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectionRequest;
use App\Http\Requests\UpdateProjectionRequest;
use App\Models\Projection;

class ProjectionController extends Controller
{
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
     * Upload and import Projection data from CSV/Excel.
     */
    public function upload(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\ProjectionImport, 
                $request->file('file')
            );
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            }
            return $this->errorResponse('Validasi file gagal. Periksa format data pada baris yang dilaporkan.', $errors, 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses file upload projection.', [
                'error' => $e->getMessage(),
            ], 500);
        }

        return $this->successResponse('File projection berhasil diunggah dan diproses.', null, 201);
    }
}
