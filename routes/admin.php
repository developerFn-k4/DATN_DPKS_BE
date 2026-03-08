<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminRoomTypeController;
use App\Http\Controllers\Admin\AdminRoomController;
use App\Http\Controllers\Admin\AdminRoomTypeImageController;
use App\Http\Controllers\Admin\AdminRImageController;

/*
|--------------------------------------------------------------------------
| ADMIN API
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

        /*
        | ROOM TYPES
        */

        Route::get('/room-types', [AdminRoomTypeController::class, 'index']);
        Route::get('/room-types/{id}', [AdminRoomTypeController::class, 'show']);
        Route::post('/room-types', [AdminRoomTypeController::class, 'store']);
        Route::put('/room-types/{id}', [AdminRoomTypeController::class, 'update']);
        Route::delete('/room-types/{id}', [AdminRoomTypeController::class, 'destroy']);

        /*
        | ROOM type IMAGES
        */

        Route::post('/room-types/{roomType}/images', [AdminRoomTypeImageController::class, 'store']);

        Route::post('/room-images/{roomImage}', [AdminRoomTypeImageController::class, 'update']);

        Route::delete('/room-images/{roomImage}', [AdminRoomTypeImageController::class, 'destroy']);

        /*
        | ROOM IMAGES
        */

        Route::post('rooms/{room}/images', [AdminRImageController::class, 'store']);

        Route::post('images/{id}', [AdminRImageController::class, 'update']);

        Route::delete('images/{id}', [AdminRImageController::class, 'destroy']);

        /*
        | ROOMS
        */

        Route::apiResource('rooms', AdminRoomController::class);
    });
