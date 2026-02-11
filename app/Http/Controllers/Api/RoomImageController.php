<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomImage;
use Illuminate\Http\Request;

class RoomImageController extends Controller
{
    
    public function index()
    {
        $images = RoomImage::with('roomType')->latest('id')->get();

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'image_url' => 'required|string|max:255',
        ]);

        $data['created_at'] = now();

        $image = RoomImage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Room image created successfully',
            'data' => $image
        ], 201);
    }

    public function show(RoomImage $roomImage)
    {
        $roomImage->load('roomType');

        return response()->json([
            'success' => true,
            'data' => $roomImage
        ]);
    }

    
    public function update(Request $request, RoomImage $roomImage)
    {
        $data = $request->validate([
            'room_type_id' => 'sometimes|required|exists:room_types,id',
            'image_url' => 'sometimes|required|string|max:255',
        ]);

        $roomImage->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Room image updated successfully',
            'data' => $roomImage
        ]);
    }

    
    public function destroy(RoomImage $roomImage)
    {
        $roomImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room image deleted successfully'
        ]);
    }
}
