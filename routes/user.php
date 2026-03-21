<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserRoomTypeController;
use App\Http\Controllers\Api\UserRoomImageController;
use App\Http\Controllers\Api\UserRoomController;
use App\Http\Controllers\Admin\AdminRImageController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\Api\UserBookingController;
use App\Http\Controllers\Api\UserReviewController;

/*
|--------------------------------------------------------------------------
| PUBLIC API
|--------------------------------------------------------------------------
*/

Route::prefix('rooms')->group(function () {

    //Tìm kiếm phòng
    Route::get('/search', [UserRoomTypeController::class, 'search']);
    // Danh sách loại phòng
    Route::get('/room-types', [UserRoomTypeController::class, 'index']);
    // Chi tiết loại phòng
    Route::get('/room-types/{id}', [UserRoomTypeController::class, 'show']);
    // Ảnh của loại phòng
    Route::get('/room-types/{roomType}/images', [UserRoomImageController::class, 'index']);
    
    // Danh sách phòng
    Route::get('/', [UserRoomController::class, 'index']);
    // Chi tiết phòng
    Route::get('/{room}', [UserRoomController::class, 'show']);

    Route::get('/{room}/images', [AdminRImageController::class, 'index']);
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

/*
    |--------------------------------------------------------------------------
    | USER Đặt Phòng + review
    |--------------------------------------------------------------------------
    */
Route::get('/available-rooms', [UserBookingController::class, 'availableRooms']);

Route::middleware('auth:sanctum')->group(function () {

    // đặt phòng
    Route::post('/bookings', [UserBookingController::class, 'store']);

    // danh sách booking của user
    Route::get('/my-bookings', [UserBookingController::class, 'myBookings']);

    // hủy booking
    Route::put('/bookings/{id}/cancel', [UserBookingController::class, 'cancel']);

    // review
    Route::post('/reviews', [UserReviewController::class, 'store']);
    Route::put('/reviews/{review}', [UserReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [UserReviewController::class, 'destroy']);


    Route::post('/payment/vnpay/{bookingId}', [PaymentController::class, 'createVnpay']);
    Route::get('/payment/vnpay-return', [PaymentController::class, 'vnpayReturn']);
    Route::get('/payment/fake-success/{orderId}', [PaymentController::class, 'fakeVnpaySuccess']);
});
