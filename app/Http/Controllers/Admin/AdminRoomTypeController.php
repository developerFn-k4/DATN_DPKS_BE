<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomType;
use App\Models\RoomImage;

class AdminRoomTypeController extends Controller
{
    /**
     * DANH SÁCH ROOM TYPE
     */
    public function index()
    {
        $roomTypes = RoomType::with('images')->latest()->get();

        return response()->json($roomTypes);
    }


    /**
     * THÊM ROOM TYPE
     */
    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'max_people' => 'required|integer',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp'
        ]);

        $roomType = RoomType::create([
            'name' => $request->name,
            'price' => $request->price,
            'max_people' => $request->max_people,
            'description' => $request->description,
            'status' => $request->status ?? 'active'
        ]);


        /**
         * Upload ảnh
         */
        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store('room_types', 'public');

                RoomImage::create([
                    'room_type_id' => $roomType->id,
                    'image_url' => $path
                ]);
            }
        }


        return response()->json([
            'message' => 'Tạo loại phòng thành công',
            'data' => $roomType->load('images')
        ]);
    }



    /**
     * UPDATE ROOM TYPE
     */
    public function update(Request $request, $id)
    {

        $roomType = RoomType::findOrFail($id);

        $roomType->update([
            'name' => $request->name,
            'price' => $request->price,
            'max_people' => $request->max_people,
            'description' => $request->description,
            'status' => $request->status
        ]);


        /**
         * Thêm ảnh mới
         */
        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store('room_types', 'public');

                RoomImage::create([
                    'room_type_id' => $roomType->id,
                    'image_url' => $path
                ]);
            }
        }

        return response()->json([
            'message' => 'Cập nhật loại phòng thành công',
            'data' => $roomType->load('images')
        ]);
    }



    /**
     * XOÁ ROOM TYPE
     */
    public function destroy($id)
    {

        $roomType = RoomType::findOrFail($id);

        $roomType->delete();

        return response()->json([
            'message' => 'Xóa loại phòng thành công'
        ]);
    }
}
