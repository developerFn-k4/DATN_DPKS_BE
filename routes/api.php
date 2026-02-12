<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomTypeApiController;

Route::apiResource('room_types', RoomTypeApiController::class);
