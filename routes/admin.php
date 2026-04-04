<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminRoomTypeController;
use App\Http\Controllers\Admin\AdminRoomController;
use App\Http\Controllers\Admin\AdminRoomTypeImageController;
use App\Http\Controllers\Admin\AdminRImageController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminServiceController;


/*
|--------------------------------------------------------------------------
| ADMIN API
|--------------------------------------------------------------------------
*/


Route::get('vnpay/pay/{orderId}', [AdminBookingController::class, 'vnpayPay'])->name('vnpay.pay');
Route::post('vnpay/webhook', [AdminBookingController::class, 'vnpayWebhook'])->name('vnpay.webhook');
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        
        // chuyển trạng thái user
        Route::patch('users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);
        // thống kê user
        Route::get('users/stats', [AdminUserController::class, 'stats']);
        Route::get('users/chart', [AdminUserController::class, 'chart']);
        Route::get('users/top', [AdminUserController::class, 'topUsers']); // nếu có booking

        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */
        Route::apiResource('users', AdminUserController::class);

        // chuyển trạng thái user
        Route::patch('users/{id}/status', [AdminUserController::class, 'changeStatus']);
        /*
        |--------------------------------------------------------------------------
        | ROOM TYPES
        |--------------------------------------------------------------------------
        */

        Route::apiResource('room-types', AdminRoomTypeController::class);

        Route::put('room-types/{id}/restore', [AdminRoomTypeController::class, 'restore']);
        /*
        |--------------------------------------------------------------------------
        | ROOM TYPE IMAGES
        |--------------------------------------------------------------------------
        */
        // thêm ảnh cho loại phòng
        Route::post(
            '/room-types/{roomType}/images',
            [AdminRoomTypeImageController::class, 'store']
        );
        // cập nhật ảnh loại phòng
        Route::put(
            '/room-type-images/{roomImage}',
            [AdminRoomTypeImageController::class, 'update']
        );
        // xoá ảnh loại phòng
        Route::delete(
            '/room-type-images/{roomImage}',
            [AdminRoomTypeImageController::class, 'destroy']
        );
        /*
        |--------------------------------------------------------------------------
        | ROOMS
        |--------------------------------------------------------------------------
        */
        Route::apiResource('rooms', AdminRoomController::class);

        Route::put('rooms/{id}/restore', [AdminRoomController::class, 'restore']);
        /*
        |--------------------------------------------------------------------------
        | BOOKINGS
        |--------------------------------------------------------------------------
        */
        Route::post('search', [AdminBookingController::class, 'search']);
        Route::post('calculate-price', [AdminBookingController::class, 'calculatePrice']);
        Route::post('/booking', [AdminBookingController::class, 'store']);
        Route::post('{id}/cancel', [AdminBookingController::class, 'cancel']);

        Route::post('auto-cancel', [AdminBookingController::class, 'autoCancel']);



        /*
        |--------------------------------------------------------------------------
        | Review
        |--------------------------------------------------------------------------
        */

        Route::get('/reviews', [AdminReviewController::class, 'index']);

        Route::get('/reviews/{id}', [AdminReviewController::class, 'show']);

        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | SERVICES
        |--------------------------------------------------------------------------
        */
        Route::apiResource('services', AdminServiceController::class);
    });
