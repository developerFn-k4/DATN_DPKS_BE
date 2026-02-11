<?php

use App\Http\Api\HotelController;
use App\Http\Api\RoomImageController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('admin')->group(function () {
    Route::apiResource('room-types', RoomTypeController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('hotels', HotelController::class);
    Route::apiResource('room-images', RoomImageController::class);
});
