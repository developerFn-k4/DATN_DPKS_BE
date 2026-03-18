<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RImage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AdminRoomController extends Controller
{

    /**
     * =========================================================
     * GET LIST ROOMS
     * =========================================================
     */
    public function index()
    {
        $rooms = Room::with(['roomType', 'images'])->latest()->get();

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

            'price' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        DB::beginTransaction();

        try {

            $room = Room::create($data);

            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $file) {

                    $path = $file->store('rooms', 'public');

                    RImage::create([
                        'room_id' => $room->id,
                        'image_url' => $path
                    ]);
                }
            }

            DB::commit();

            $room->load(['roomType', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully',
                'data' => $room
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Create room failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * =========================================================
     * SHOW ROOM
     * =========================================================
     */
    public function show(Room $room)
    {
        $room->load(['roomType', 'images']);

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

            'price' => 'sometimes|numeric|min:0',
            'note' => 'sometimes|nullable|string',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        DB::beginTransaction();

        try {

            $room->update(collect($data)->except('images')->toArray());

            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $file) {

                    $path = $file->store('rooms', 'public');

                    RImage::create([
                        'room_id' => $room->id,
                        'image_url' => $path
                    ]);
                }
            }

            DB::commit();

            $room->load(['roomType', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data' => $room
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * =========================================================
     * DELETE IMAGE
     * =========================================================
     */
    public function deleteImage($imageId)
    {

        $image = RImage::findOrFail($imageId);

        Storage::disk('public')->delete($image->image_url);

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa ảnh thành công'
        ]);
    }


    /**
     * =========================================================
     * DELETE ROOM
     * =========================================================
     */
    public function destroy(Room $room)
    {

        foreach ($room->images as $image) {

            Storage::disk('public')->delete($image->image_url);

            $image->delete();
        }

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
                'message' => 'Phòng này chưa bị xóa'
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
