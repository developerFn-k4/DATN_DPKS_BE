<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;

class UserRoomImageController extends Controller
{
    public function index(RoomType $roomType)
    {
        return response()->json([
            'success' => true,
            'data' => $roomType->images
        ]);
    }
}
