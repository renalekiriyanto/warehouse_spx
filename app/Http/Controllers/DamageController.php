<?php

namespace App\Http\Controllers;

use App\Http\Requests\DamageRequest;
use App\Models\Damage;
use App\Services\DamageService;
use Illuminate\Http\Request;

class DamageController extends Controller
{
    private $damageService;

    public function __construct()
    {
        $this->damageService = new DamageService();
    }

    public function index(Request $request)
    {
        $data = $this->damageService->fetchAll($request);
        return $this->successResponse($data['message'], $data['data'], $data['code']);
    }

    public function fetchOne(Damage $damage)
    {
        $data = $this->damageService->fetchOne($damage);
        return $data;
        return $this->successResponse($data['message'], $data['data'], $data['code']);
    }

    public function store(DamageRequest $request)
    {
        $data = $this->damageService->storeData($request);

        return $this->successResponse($data['message'], $data['data'], $data['code']);
    }

    public function update(DamageRequest $request, Damage $damage)
    {

        $data = $this->damageService->updateData($damage, $request);
        return $this->successResponse($data['message'], $data['data'], $data['code']);
    }

    public function delete(Damage $damage)
    {
        $data = $this->damageService->deleteData($damage);
        return $this->successResponse($data['message'], $data['data'], $data['code']);
    }
}
