<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\Api\CutoffInbounController;
use App\Http\Controllers\Api\SocialiteController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\EstimasiArrivalController;
use App\Http\Controllers\ImportBatchController;
use App\Http\Controllers\InboundController;
use App\Http\Controllers\ProjectionController;
use App\Http\Controllers\StdSomedayController;
use App\Http\Controllers\TypeSlotController;
use App\Http\Controllers\VehicleTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Import batch status & progress
Route::get('import-batches', [ImportBatchController::class, 'index']);
Route::get('import-batches/{uuid}', [ImportBatchController::class, 'show']);

Route::apiResource('cutoff-inbounds', CutoffInbounController::class);
Route::post('projections/upload', [ProjectionController::class, 'upload']);
Route::apiResource('projections', ProjectionController::class);
Route::apiResource('type-slots', TypeSlotController::class);
Route::apiResource('estimasi-arrivals', EstimasiArrivalController::class);
Route::post('inbounds/upload', [InboundController::class, 'upload']);
Route::get('inbounds/analysis/daily', [InboundController::class, 'dailyAnalysis']);
Route::get('inbounds/{inbound}/cycle', [InboundController::class, 'cycleContext']);
Route::apiResource('inbounds', InboundController::class);
Route::apiResource('vehicle-types', VehicleTypeController::class);
Route::apiResource('agencies', AgencyController::class);
Route::apiResource('drivers', DriverController::class);
Route::post('drivers/upload', [DriverController::class, 'importData']);
Route::post('std-somedays/upload', [StdSomedayController::class, 'upload']);
Route::apiResource('std-somedays', StdSomedayController::class);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);
