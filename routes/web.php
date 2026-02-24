<?php

use App\Http\Controllers\Admin\AdminRoomImageController;
use App\Http\Controllers\Admin\AdminRoomTypeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomTypeController;

Route::resource('room_types', RoomTypeController::class);

Route::get('/', function () {
    return view('welcome');
});


// Route::prefix('admin')->group(function () {
//     //route CRUD loại phòng
//     Route::get('room-types', [AdminRoomTypeController::class, 'index']);
//     Route::get('room-types/{id}', [AdminRoomTypeController::class, 'show']);
//     Route::post('room-types', [AdminRoomTypeController::class, 'store']);
//     Route::put('room-types/{id}', [AdminRoomTypeController::class, 'update']);
//     Route::delete('room-types/{id}', [AdminRoomTypeController::class, 'destroy']);
//     //route crud ảnh
//     Route::post(
//         'room-types/{id}/images',
//         [AdminRoomImageController::class, 'store']
//     );

//     Route::delete(
//         'room-images/{id}',
//         [AdminRoomImageController::class, 'destroy']
//     );

//     Route::post(
//         'room-images/{id}',
//         [AdminRoomImageController::class, 'update']
//     );
// });
















Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
