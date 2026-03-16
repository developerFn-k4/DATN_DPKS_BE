<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;

class UserRoomController extends Controller
{
    /**
     * =====================================================
     * Danh sách phòng (kèm loại phòng + ảnh)
     * =====================================================
     */
    public function index()
    {
        $rooms = Room::with([
            'roomType',
            'images'
        ])
            ->where('status', 'available')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }


    /**
     * =====================================================
     * Chi tiết phòng
     * =====================================================
     */
    public function show(Room $room)
    {
        $room->load([
            'roomType',
            'images',
            'reviews.user'
        ]);

        $reviews = $room->reviews;

        /**
         * Rating summary
         */
        $ratingSummary = [
            'cleanliness' => round($reviews->avg('cleanliness'), 1),
            'comfort' => round($reviews->avg('comfort'), 1),
            'location' => round($reviews->avg('location'), 1),
            'service' => round($reviews->avg('service'), 1),
            'value' => round($reviews->avg('value'), 1),
            'wifi' => round($reviews->avg('wifi'), 1),
            'overall' => round($reviews->avg('overall_score'), 1),
            'total_reviews' => $reviews->count()
        ];

        /**
         * Lấy 4 phòng cùng loại
         */
        $relatedRooms = Room::with([
            'roomType',
            'images'
        ])
            ->where('room_type_id', $room->room_type_id)
            ->where('id', '!=', $room->id)
            ->where('status', 'available')
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return response()->json([
            'success' => true,
            'room' => $room,
            'rating_summary' => $ratingSummary,
            'reviews' => $reviews,
            'related_rooms' => $relatedRooms
        ]);
    }
}
