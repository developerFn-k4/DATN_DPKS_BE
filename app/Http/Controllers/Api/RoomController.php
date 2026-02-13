<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
   
    public function index()
    {
        $rooms = Room::with('roomType')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:50|unique:rooms,room_number',
            'floor' => 'required|string|max:50',
            'status' => 'required|in:available,maintenance',
        ]);

        $room = Room::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully',
            'data' => $room
        ], 201);
    }

    
    public function show(Room $room)
    {
        $room->load('roomType');

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    
    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'room_type_id' => 'sometimes|required|exists:room_types,id',
            'room_number' => 'sometimes|required|string|max:50|unique:rooms,room_number,' . $room->id,
            'floor' => 'sometimes|required|string|max:50',
            'status' => 'sometimes|required|in:available,maintenance',
        ]);

        $room->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    
    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    }
}
