<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpediteRequest;
use App\Services\ExpediteService;
use Illuminate\Http\Request;

class ExpediteController extends Controller
{
    public $expediteService;
    public function __construct()
    {
        // declare services
        $this->expediteService = new ExpediteService();
    }
    public function index(Request $request)
    {
        // Logic to retrieve and return expedite data
        $data = $this->expediteService->fetchExpediteData($request);
        return $this->successResponse('Data expedite berhasil diambil', $data, 200);
    }

    public function storeData(StoreExpediteRequest $request)
    {
        // Logic to store expedite data
        $data = $this->expediteService->storeExpediteData($request->validated());
        return $this->successResponse('Data expedite berhasil disimpan', $data, 200);
    }

    public function destroy($id)
    {
        // Logic to delete expedite data
        $data = $this->expediteService->deleteExpediteData($id);
        return $data;
    }

    // Upload / import data
    public function uploadData(Request $request)
    {
        // Logic to handle file upload and import data
        $data = $this->expediteService->uploadExpediteData($request);
        return [
            'data' => $data
        ];
        // This is a placeholder, you can implement the actual logic as needed
        return $this->successResponse('Data expedite berhasil diupload', $data, 200);
    }
}
