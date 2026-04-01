<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminRoomController extends Controller
{

    /**
     * =========================================================
     * GET LIST ROOMS
     * =========================================================
     */
    public function index()
    {
        $rooms = Room::with(['roomType:id,name'])
            ->select('id', 'room_number', 'room_type_id', 'floor', 'status', 'note', 'created_at', 'updated_at')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }


    /**
     * =========================================================
     * CREATE ROOM
     * =========================================================
     */
    public function store(Request $request)
    {

        $data = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',

            'room_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_number')->whereNull('deleted_at')
            ],

            'floor' => 'required|integer|max:50',

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'booked',
                    'occupied',
                    'maintenance',
                    'reserved',
                    'unavailable'
                ])
            ],

            'note' => 'nullable|string',
        ]);

        $room = Room::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tạo phòng thành công',
            'data' => $room
        ], 201);
    }


    /**
     * =========================================================
     * SHOW ROOM
     * =========================================================
     */
    public function show(Room $room)
    {

        $room->load(['roomType:id,name']);

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }


    /**
     * =========================================================
     * UPDATE ROOM
     * =========================================================
     */
    public function update(Request $request, Room $room)
    {

        $data = $request->validate([
            'room_type_id' => 'sometimes|exists:room_types,id',

            'room_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_number')
                    ->ignore($room->id)
                    ->whereNull('deleted_at')
            ],

            'floor' => 'sometimes|integer|max:50',

            'status' => [
                'sometimes',
                Rule::in([
                    'available',
                    'booked',
                    'occupied',
                    'maintenance',
                    'reserved',
                    'unavailable'
                ])
            ],

            'note' => 'sometimes|nullable|string',
        ]);

        $room->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phòng thành công',
            'data' => $room
        ]);
    }


    /**
     * =========================================================
     * DELETE ROOM
     * =========================================================
     */
    public function destroy(Room $room)
    {

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa phòng thành công'
        ]);
    }


    /**
     * =========================================================
     * RESTORE ROOM
     * =========================================================
     */
    public function restore($id)
    {

        $room = Room::withTrashed()->findOrFail($id);

        if (!$room->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng chưa bị xóa'
            ], 400);
        }

        $room->restore();

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục phòng thành công',
            'data' => $room
        ]);
    }
}
