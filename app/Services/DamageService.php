<?php

namespace App\Services;

use App\Http\Requests\DamageRequest;
use App\Models\Damage;

class DamageService
{
    public function fetchAll($request)
    {
        $message = 'Success';
        $data = null;
        $codeError = 200;

        $data = Damage::byDate($request->date)
            ->byAwb($request->awb)
            ->get();

        if (!$data) {
            $message = 'warning';
            $codeError = 404;
        }

        return [
            'message' => $message,
            'data' => $data,
            'code' => $codeError
        ];
    }

    public function fetchOne($damage)
    {
        $message = 'Success';
        $data = null;
        $codeError = 200;

        if (!$damage) {
            $message = 'Error';
            $codeError = 404;
        }

        return [
            'message' => $message,
            'data' => $damage,
            'code' => $codeError
        ];
    }

    public function storeData($request)
    {
        $message = 'Success';
        $data = null;
        $codeError = 200;

        // validasi data input
        $data_validated = $request->validated();

        $data = Damage::create($data_validated);

        return [
            'message' => $message,
            'data' => $data,
            'code' => $codeError
        ];
    }

    public function updateData(Damage $damage, DamageRequest $request)
    {
        $data_validated = $request->validated();

        $damage->update($data_validated);

        return [
            'message' => 'Success',
            'data' => $damage->refresh(),
            'code' => 200,
        ];
    }

    public function deleteData($damage)
    {
        $message = 'Success';
        $data = null;
        $codeError = 200;

        // validasi data input
        $damage->delete();

        return [
            'message' => $message,
            'data' => $data,
            'code' => $codeError
        ];
    }
}
