<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Build a success response
     *
     * @param  string|null  $message
     * @param  mixed  $data
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($message = 'Success', $data = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ], $code);
    }

    /**
     * Build an error response
     *
     * @param  string  $message
     * @param  mixed  $data
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($message = 'Terjadi kesalahan.', $data = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ], $code);
    }
}
