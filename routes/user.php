<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserRoomTypeController;
use App\Http\Controllers\Api\UserRoomImageController;
use App\Http\Controllers\Api\UserRoomController;
use App\Http\Controllers\Admin\AdminRImageController;

/*
|--------------------------------------------------------------------------
| PUBLIC API
|--------------------------------------------------------------------------
*/

Route::prefix('rooms')->group(function () {

    // Danh sách phòng
    Route::get('/', [UserRoomController::class, 'index']);

    // Chi tiết phòng
    Route::get('/{room}', [UserRoomController::class, 'show']);

    Route::get('/{room}/images', [AdminRImageController::class, 'index']);
});
Route::prefix('rooms')->group(function () {

    // Danh sách loại phòng
    Route::get('/room-types', [UserRoomTypeController::class, 'index']);

    // Chi tiết loại phòng
    Route::get('/room-types/{id}', [UserRoomTypeController::class, 'show']);

    // Ảnh của loại phòng
    Route::get('/room-types/{roomType}/images', [UserRoomImageController::class, 'index']);
});


/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);

    Route::post('/login', [AuthController::class, 'login']);
});


/*
|--------------------------------------------------------------------------
| USER LOGIN
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {

    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    });

    Route::get('/profile', [AuthController::class, 'profile']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
