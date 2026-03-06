<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class UserRoomTypeController extends Controller
{
    /**
     * GET /api/room-types
     * Lấy danh sách loại phòng cho user
     */
    public function index()
    {
        $roomTypes = RoomType::with(['images', 'rooms'])
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roomTypes
        ]);
    }

    /**
     * GET /api/room-types/{id}
     * Xem chi tiết loại phòng
     */
    public function show($id)
    {
        $roomType = RoomType::with([
            'images',
            'rooms' => function ($query) {
                $query->where('status', 'available');
            }
        ])
            ->where('status', 'active')
            ->find($id);

        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy loại phòng'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $roomType
        ]);
    }
}
