<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserRoomTypeController;
use App\Http\Controllers\Api\UserRoomImageController;
use App\Http\Controllers\Api\UserRoomController;
use App\Http\Controllers\Admin\AdminRImageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserBookingController;
use App\Http\Controllers\Api\UserReviewController;
use App\Http\Controllers\Api\UserProfileController;

/*
|--------------------------------------------------------------------------
| PUBLIC API
|--------------------------------------------------------------------------
*/

Route::prefix('rooms')->group(function () {
    Route::get('/room-types', [UserRoomTypeController::class, 'index'])->name('api.rooms.room-types.index');
    Route::get('/room-types/{id}', [UserRoomTypeController::class, 'show'])->name('api.rooms.room-types.show');
    Route::post('/room-types/search', [UserRoomTypeController::class, 'search'])->name('api.rooms.room-types.search');
});

/*
|--------------------------------------------------------------------------
| AUTH & USER PROFILE
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Public Auth
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');

    // Protected Auth & Profile
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => $request->user()
            ]);
        })->name('api.auth.me');

        Route::prefix('profile')->group(function () {
            Route::get('/', [UserProfileController::class, 'show'])->name('api.auth.profile.show');
            Route::put('/', [UserProfileController::class, 'update'])->name('api.auth.profile.update');
            Route::post('/avatar', [UserProfileController::class, 'updateAvatar'])->name('api.auth.profile.avatar');
        });

        Route::put('/change-password', [UserProfileController::class, 'changePassword'])->name('api.auth.change-password');

        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    });
});

/*
|--------------------------------------------------------------------------
| BOOKING & REVIEWS
|--------------------------------------------------------------------------
*/
Route::get('/available-rooms', [UserBookingController::class, 'availableRooms'])->name('api.available-rooms');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/calculate-price', [UserBookingController::class, 'calculatePrice'])->name('api.calculate-price');
    Route::get('/services', [UserBookingController::class, 'services'])->name('api.services');
    Route::post('/bookings', [UserBookingController::class, 'store'])->name('api.bookings.store');
    Route::get('/my-bookings', [UserBookingController::class, 'myBookings'])->name('api.bookings.index');
    Route::put('/bookings/{id}/cancel', [UserBookingController::class, 'cancel'])->name('api.bookings.cancel');

    Route::post('/reviews', [UserReviewController::class, 'store'])->name('api.reviews.store');
    Route::put('/reviews/{review}', [UserReviewController::class, 'update'])->name('api.reviews.update');
    Route::delete('/reviews/{review}', [UserReviewController::class, 'destroy'])->name('api.reviews.destroy');

    Route::prefix('payment')->group(function () {
        Route::post('/vnpay/{bookingId}', [PaymentController::class, 'createVnpay'])->name('api.payment.vnpay');
        Route::get('/vnpay-return', [PaymentController::class, 'vnpayReturn'])->name('api.payment.vnpay-return');
        Route::get('/fake-success/{orderId}', [PaymentController::class, 'fakeVnpaySuccess'])->name('api.payment.fake-success');
    });
});
