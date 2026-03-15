<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminRoomTypeController;
use App\Http\Controllers\Admin\AdminRoomController;
use App\Http\Controllers\Admin\AdminRoomTypeImageController;
use App\Http\Controllers\Admin\AdminRImageController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminDashboardController;

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
        | ROOM TYPES
        |--------------------------------------------------------------------------
        */

        Route::apiResource('room-types', AdminRoomTypeController::class);


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
        | ROOM IMAGES
        |--------------------------------------------------------------------------
        */

        // thêm ảnh cho phòng
        Route::post(
            'rooms/{room}/images',
            [AdminRImageController::class, 'store']
        );

        // cập nhật ảnh phòng
        Route::put(
            'room-images/{id}',
            [AdminRImageController::class, 'update']
        );

        // xoá ảnh phòng
        Route::delete(
            'room-images/{id}',
            [AdminRImageController::class, 'destroy']
        );


        /*
        |--------------------------------------------------------------------------
        | BOOKINGS
        |--------------------------------------------------------------------------
        */

        // danh sách booking
        Route::get(
            '/bookings',
            [AdminBookingController::class, 'index']
        );

        // xác nhận booking
        Route::put(
            '/bookings/{id}/confirm',
            [AdminBookingController::class, 'confirm']
        );

        // hoàn thành booking
        Route::put(
            '/bookings/{id}/complete',
            [AdminBookingController::class, 'complete']
        );
    });
