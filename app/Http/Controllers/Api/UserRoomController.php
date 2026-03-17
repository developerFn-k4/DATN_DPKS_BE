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
        $room->load(['roomType', 'images']);

        return response()->json([
            'success' => true,
            'room' => $room,
            'rating_summary' => [
                'cleanliness' => 0,
                'comfort' => 0,
                'location' => 0,
                'service' => 0,
                'value' => 0,
                'wifi' => 0,
                'overall' => 0,
                'total_reviews' => 0,
            ],
            'reviews' => [],
            'related_rooms' => [],
        ]);
    }
}
