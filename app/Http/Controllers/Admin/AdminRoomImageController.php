<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomImage;
use App\Models\RoomType;
use Illuminate\Support\Facades\Storage;

class AdminRoomImageController extends Controller
{
    public function store(Request $request, $roomTypeId)
    {
        $roomType = RoomType::findOrFail($roomTypeId);

        $request->validate([
            'image' => 'required|image|max:2048'
        ]);

        $path = $request->file('image')->store('room-images', 'public');

        $image = RoomImage::create([
            'room_type_id' => $roomType->id,
            'image_url' => Storage::url($path)
        ]);

        return response()->json([
            'success' => true,
            'data' => $image
        ]);
    }
}
