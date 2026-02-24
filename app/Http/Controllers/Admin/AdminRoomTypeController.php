<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomType;

class AdminRoomTypeController extends Controller
{
    /**
     * GET /api/admin/room-types
     * Lấy danh sách loại phòng
     */
    public function index()
    {
        $roomTypes = RoomType::with('hotel', 'images')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roomTypes
        ]);
    }

    /**
     * GET /api/admin/room-types/{id}
     * Xem chi tiết 1 loại phòng
     */
    public function show($id)
    {
        $roomType = RoomType::with('hotel', 'images', 'rooms')
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

    /**
     * POST /api/admin/room-types
     * Tạo loại phòng mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'required|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'status' => 'required|in:active,inactive',
        ]);

        $roomType = RoomType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo loại phòng thành công',
            'data' => $roomType
        ], 201);
    }

    /**
     * PUT /api/admin/room-types/{id}
     * Cập nhật loại phòng
     */
    public function update(Request $request, $id)
    {
        $roomType = RoomType::find($id);

        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy loại phòng'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'bed_type' => 'sometimes|string|max:100',
            'base_price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|max:10',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $roomType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $roomType
        ]);
    }

    /**
     * DELETE /api/admin/room-types/{id}
     * Xóa loại phòng
     */
    public function destroy($id)
    {
        $roomType = RoomType::find($id);

        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy loại phòng'
            ], 404);
        }

        $roomType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa loại phòng thành công'
        ]);
    }
}
