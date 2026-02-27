<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\AdminRoomTypeController;
use App\Http\Controllers\Admin\AdminRoomImageController;

/*
|--------------------------------------------------------------------------
| PUBLIC API (KHÔNG CẦN ĐĂNG NHẬP)
|--------------------------------------------------------------------------
| - Register
| - Login
| - Quên mật khẩu (sau này có thể thêm)
*/

Route::prefix('auth')->group(function () {

    // Đăng ký
    Route::post('/register', [AuthController::class, 'register']);

    // Đăng nhập
    Route::post('/login', [AuthController::class, 'login']);
});


/*
|--------------------------------------------------------------------------
| USER API (PHẢI LOGIN - auth:sanctum)
|--------------------------------------------------------------------------
| - Profile
| - Logout
| - Lấy thông tin user hiện tại
*/
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {

    // Lấy thông tin user đang đăng nhập (me/profile)
    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    });

    // Profile (nếu bạn dùng controller riêng)
    Route::get('/profile', [AuthController::class, 'profile']);

    // Logout (xoá token)
    Route::post('/logout', [AuthController::class, 'logout']);
});


/*
|--------------------------------------------------------------------------
| ADMIN API (PHẢI LOGIN + ROLE = ADMIN)
|--------------------------------------------------------------------------
| Middleware:
| - auth:sanctum: phải đăng nhập
| - admin: phải là admin mới được vào
*/
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

        /*
        |----------------------------------
        | CRUD ROOM TYPES (LOẠI PHÒNG)
        |----------------------------------
        */
        // Lấy danh sách loại phòng
        Route::get('/room-types', [AdminRoomTypeController::class, 'index']);

        // Chi tiết 1 loại phòng
        Route::get('/room-types/{id}', [AdminRoomTypeController::class, 'show']);

        // Tạo loại phòng
        Route::post('/room-types', [AdminRoomTypeController::class, 'store']);

        // Cập nhật loại phòng
        Route::put('/room-types/{id}', [AdminRoomTypeController::class, 'update']);

        // Xoá loại phòng
        Route::delete('/room-types/{id}', [AdminRoomTypeController::class, 'destroy']);


        /*
        |----------------------------------
        | CRUD ROOM IMAGES (ẢNH PHÒNG)
        |----------------------------------
        */
        // Thêm ảnh cho loại phòng
        Route::post('/room-types/{id}/images', [AdminRoomImageController::class, 'store']);

        // Cập nhật ảnh phòng
        Route::post('/room-images/{id}', [AdminRoomImageController::class, 'update']);

        // Xoá ảnh phòng
        Route::delete('/room-images/{id}', [AdminRoomImageController::class, 'destroy']);
    });
