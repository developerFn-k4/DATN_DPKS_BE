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


/*
|--------------------------------------------------------------------------
| ADMIN API
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */
        Route::apiResource('users', AdminUserController::class);

        // chuyển trạng thái user
        Route::patch('users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);
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
        Route::get('/search', [AdminBookingController::class, 'search']);

        Route::post('/calculate-price', [AdminBookingController::class, 'calculatePrice']);

        Route::post('/booking', [AdminBookingController::class, 'store']);

        Route::post('/payment/momo/{booking}', [AdminBookingController::class, 'momoPayment']);

        Route::post('/payment/webhook', [AdminBookingController::class, 'momoWebhook']);

        Route::post('/booking/{id}/cancel', [AdminBookingController::class, 'cancel']);


        /*
        |--------------------------------------------------------------------------
        | Review
        |--------------------------------------------------------------------------
        */

        Route::get('/reviews', [AdminReviewController::class, 'index']);

        Route::get('/reviews/{id}', [AdminReviewController::class, 'show']);

        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);
    });
