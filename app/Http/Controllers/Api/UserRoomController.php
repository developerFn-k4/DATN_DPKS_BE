<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;

class UserRoomController extends Controller
{
    // Danh sách phòng
    public function index()
    {
        $rooms = Room::with('roomType')->where('status', 'available')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    // Chi tiết phòng
    public function show(Room $room)
    {
        $room->load('roomType');

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }
}
