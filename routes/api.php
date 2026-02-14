<?php

use App\Http\Controllers\Admin\AdminRoomImageController;
use App\Http\Controllers\Admin\AdminRoomTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('admin')->group(function () {
    //route CRUD loại phòng
    Route::get('room-types', [AdminRoomTypeController::class, 'index']);
    Route::get('room-types/{id}', [AdminRoomTypeController::class, 'show']);
    Route::post('room-types', [AdminRoomTypeController::class, 'store']);
    Route::put('room-types/{id}', [AdminRoomTypeController::class, 'update']);
    Route::delete('room-types/{id}', [AdminRoomTypeController::class, 'destroy']);
    //route crud ảnh
    Route::post(
        'room-types/{id}/images',
        [AdminRoomImageController::class, 'store']
    );

    Route::delete(
        'room-images/{id}',
        [AdminRoomImageController::class, 'destroy']
    );

    Route::post(
        'room-images/{id}',
        [AdminRoomImageController::class, 'update']
    );
});
