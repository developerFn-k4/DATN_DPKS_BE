<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    
    public function index()
    {
        $roomTypes = RoomType::with('hotel')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $roomTypes
        ]);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'in:active,inactive',
        ]);

        $roomType = RoomType::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Room type created successfully',
            'data' => $roomType
        ], 201);
    }

    // GET /api/room-types/{roomType}
    public function show(RoomType $roomType)
    {
        $roomType->load('hotel');

        return response()->json([
            'success' => true,
            'data' => $roomType
        ]);
    }

    
    public function update(Request $request, RoomType $roomType)
    {
        $data = $request->validate([
            'hotel_id' => 'sometimes|required|exists:hotels,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|required|integer|min:1',
            'bed_type' => 'sometimes|required|string|max:255',
            'base_price' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'status' => 'in:active,inactive',
        ]);

        $roomType->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Room type updated successfully',
            'data' => $roomType
        ]);
    }

    
    public function destroy(RoomType $roomType)
    {
        $roomType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room type deleted successfully'
        ]);
    }
}
