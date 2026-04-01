<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;

class UserRoomController extends Controller
{
    /**
     * Danh sách phòng
     */
    public function index()
    {
        $rooms = Room::with(['roomType', 'images'])
            ->where('status', 'available')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }


    /**
     * Chi tiết phòng
     */
    public function show(Room $room)
    {
        $room->load([
            'roomType',
            'images',
            'reviews.user'
        ])->loadAvg('reviews', 'overall_score')
            ->loadCount('reviews');

        $relatedRooms = Room::with(['roomType', 'images'])
            ->where('room_type_id', $room->room_type_id)
            ->where('id', '!=', $room->id)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return response()->json([
            'success' => true,
            'room' => $room,
            'related_rooms' => $relatedRooms
        ]);
    }
}
