<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RImage;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminRImageController extends Controller
{
    // Lấy danh sách ảnh của phòng
    public function index(Room $room)
    {
        $images = $room->images;

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    // Upload ảnh
    public function store(Request $request, Room $room)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {

            $path = $image->store('rooms', 'public');

            $uploadedImages[] = RImage::create([
                'room_id' => $room->id,
                'image_url' => $path
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Upload ảnh thành công',
            'data' => $uploadedImages
        ]);
    }

    // Cập nhật ảnh
    public function update(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $image = RImage::findOrFail($id);

        // xóa ảnh cũ
        Storage::disk('public')->delete($image->image_url);

        // upload ảnh mới
        $path = $request->file('image')->store('rooms', 'public');

        $image->update([
            'image_url' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ảnh thành công',
            'data' => $image
        ]);
    }

    // Xóa ảnh
    public function destroy($id)
    {
        $image = RImage::findOrFail($id);

        Storage::disk('public')->delete($image->image_url);

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa ảnh thành công'
        ]);
    }
}
