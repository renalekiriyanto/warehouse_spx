<?php

use App\Http\Controllers\Api\CutoffInbounController;
use App\Http\Controllers\Api\SocialiteController;
use App\Http\Controllers\ProjectionController;
use App\Http\Controllers\TypeSlotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('cutoff-inbouns', CutoffInbounController::class);
Route::apiResource('projections', ProjectionController::class);
Route::apiResource('type-slots', TypeSlotController::class);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);
