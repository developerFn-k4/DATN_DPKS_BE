<?php

use App\Http\Controllers\Admin\AdminRoomImageController;
use App\Http\Controllers\Admin\AdminRoomTypeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomTypeController;
Route::get('/test-image', function () {
    return response()->file(storage_path('app/public/avatars/xxUPPFbN8rkoyjyW68qYl3FcmtUuev3kKDbEcKtF.png'));
});
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
