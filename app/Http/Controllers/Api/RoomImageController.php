<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomImage;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomImageController extends Controller
{
    // 1. Lấy danh sách ảnh theo loại phòng (PUBLIC)
    public function index(RoomType $roomType)
    {
        $images = $roomType->images()
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    // 2. Upload ảnh (ADMIN)
    public function store(Request $request, RoomType $roomType)
    {
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $file) {

            $path = $file->store('room-types', 'public');

            $uploadedImages[] = RoomImage::create([
                'room_type_id' => $roomType->id,
                'image_url'    => Storage::url($path),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data'    => $uploadedImages
        ], 201);
    }

    // 3. Update ảnh (thay file mới)
    public function update(Request $request, RoomImage $roomImage)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Xoá ảnh cũ
        $oldPath = str_replace('/storage/', '', $roomImage->image_url);
        Storage::disk('public')->delete($oldPath);

        // Upload ảnh mới
        $path = $request->file('image')
            ->store('room-types', 'public');

        $roomImage->update([
            'image_url' => Storage::url($path),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image updated successfully',
            'data' => $roomImage
        ]);
    }

    // 4. Delete ảnh
    public function destroy(RoomImage $roomImage)
    {
        $path = str_replace('/storage/', '', $roomImage->image_url);
        Storage::disk('public')->delete($path);

        $roomImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }
}
