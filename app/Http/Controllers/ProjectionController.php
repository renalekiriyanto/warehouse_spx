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
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'code' => 422,
                'data' => $errors,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage(),
                'code' => 500,
                'data' => null,
            ], 500);
        }

        return $this->successResponse('File successfully uploaded and processed.', null, 201);
    }
}
