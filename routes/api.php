<?php

use App\Http\Controllers\Api\CutoffInbounController;
use App\Http\Controllers\Api\SocialiteController;
use App\Http\Controllers\EstimasiArrivalController;
use App\Http\Controllers\ProjectionController;
use App\Http\Controllers\TypeSlotController;
use App\Http\Controllers\InboundController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('cutoff-inbounds', CutoffInbounController::class);
Route::post('projections/upload', [ProjectionController::class, 'upload']);
Route::apiResource('projections', ProjectionController::class);
Route::apiResource('type-slots', TypeSlotController::class);
Route::apiResource('estimasi-arrivals', EstimasiArrivalController::class);
Route::post('inbounds/upload', [InboundController::class, 'upload']);
Route::get('inbounds/analysis/daily', [InboundController::class, 'dailyAnalysis']);
Route::get('inbounds/{inbound}/cycle', [InboundController::class, 'cycleContext']);
Route::apiResource('inbounds', InboundController::class);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);
