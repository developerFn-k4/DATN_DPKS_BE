<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomType;
use App\Models\RoomImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminRoomTypeController extends Controller
{

    /**
     * DANH SÁCH ROOM TYPE
     */
    public function index()
    {
        $roomTypes = RoomType::with('images')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($roomType) {

                return [
                    'id' => $roomType->id,
                    'hotel_id' => $roomType->hotel_id,
                    'name' => $roomType->name,
                    'capacity' => $roomType->capacity,
                    'bed_type' => $roomType->bed_type,
                    'area' => $roomType->area,
                    'amenities' => $roomType->amenities ? json_decode($roomType->amenities) : [],
                    'base_price' => $roomType->base_price,
                    'currency' => $roomType->currency,
                    'status' => $roomType->status,
                    'created_at' => $roomType->created_at,
                    'updated_at' => $roomType->updated_at,

                    'images' => $roomType->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_url' => asset('storage/' . $image->image_url)
                        ];
                    })
                ];
            });

        return response()->json([
            'data' => $roomTypes
        ]);
    }


    /**
     * CHI TIẾT ROOM TYPE
     */
    public function show($id)
    {
        $roomType = RoomType::with('images')->findOrFail($id);

        return response()->json([
            'data' => [
                'id'         => $roomType->id,
                'hotel_id'   => $roomType->hotel_id,
                'name'       => $roomType->name,
                'capacity'   => $roomType->capacity,
                'bed_type'   => $roomType->bed_type,
                'area'       => $roomType->area,
                'amenities'  => $roomType->amenities ? json_decode($roomType->amenities) : [],
                'base_price' => $roomType->base_price,
                'currency'   => $roomType->currency,
                'status'     => $roomType->status,
                'created_at' => $roomType->created_at,
                'updated_at' => $roomType->updated_at,
                'images'     => $roomType->images->map(fn($img) => [
                    'id'        => $img->id,
                    'image_url' => asset('storage/' . $img->image_url)
                ])
            ]
        ]);
    }

    /**
     * THÊM ROOM TYPE
     */
    public function store(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'bed_type' => 'required|string|max:255',
            'area' => 'required|integer',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'base_price' => 'required|numeric',
            'currency' => 'required|string|max:3',
            'status' => 'nullable|in:active,inactive',

            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        DB::beginTransaction();

        try {

            $roomType = RoomType::create([
                'hotel_id' => $request->hotel_id,
                'name' => $request->name,
                'capacity' => $request->capacity,
                'bed_type' => $request->bed_type,
                'area' => $request->area,
                'amenities' => $request->amenities ? json_encode($request->amenities) : null,
                'base_price' => $request->base_price,
                'currency' => $request->currency,
                'status' => $request->status ?? 'active',
                'max_adults' => $request->max_adults ?? $request->capacity ?? 2,
                'max_children' => $request->max_children ?? 0,
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

            DB::commit();

            return response()->json([
                'message' => 'Tạo loại phòng thành công',
                'data' => $roomType->load('images')
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Tạo loại phòng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * UPDATE ROOM TYPE - Đã fix lỗi 500 và tối ưu response
     */
    public function update(Request $request, $id)
    {
        // 1. Validate dữ liệu đầu vào
        $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'keep_images' => 'nullable|array'
        ]);

        $roomType = RoomType::with('images')->findOrFail($id);

        DB::beginTransaction();

        try {
            // 2. Cập nhật thông tin cơ bản
            $roomType->update([
                'hotel_id'   => $request->hotel_id ?? $roomType->hotel_id,
                'name'       => $request->name ?? $roomType->name,
                'capacity'   => (int)($request->capacity ?? $roomType->capacity),
                'bed_type'   => $request->bed_type ?? $roomType->bed_type,
                'area'       => $request->area ?? $roomType->area,
                'amenities'  => $request->amenities ?? $roomType->amenities,
                'base_price' => $request->base_price ?? $roomType->base_price,
                'currency'   => $request->currency ?? $roomType->currency,
                'status'     => $request->status ?? $roomType->status,
            ]);

            // 3. XỬ LÝ XÓA ẢNH (Dựa trên keep_images)
            if ($request->has('keep_images')) {
                $keepImages = $request->keep_images;

                // Đảm bảo là mảng để xử lý
                if (!is_array($keepImages)) {
                    $keepImages = explode(',', $keepImages);
                }

                // Lấy danh sách ảnh cũ KHÔNG nằm trong danh sách giữ lại
                $imagesToDelete = $roomType->images->whereNotIn('id', $keepImages);

                foreach ($imagesToDelete as $img) {
                    if (Storage::disk('public')->exists($img->image_url)) {
                        Storage::disk('public')->delete($img->image_url);
                    }
                    $img->delete();
                }
            }

            // 4. UPLOAD ẢNH MỚI
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('room_types', 'public');
                    RoomImage::create([
                        'room_type_id' => $roomType->id,
                        'image_url'    => $path
                    ]);
                }
            }

            $roomType->load('images');
            DB::commit();

            // 5. CHUẨN BỊ RESPONSE (Đã fix lỗi json_decode)
            $amenitiesData = $roomType->amenities;

            // Nếu là chuỗi JSON thì mới decode, nếu đã là mảng rồi thì giữ nguyên
            if (is_string($amenitiesData)) {
                $amenitiesData = json_decode($amenitiesData, true);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật loại phòng thành công',
                'data' => [
                    'room_type_id' => $roomType->id, // Trả về đúng ID để FE nhận diện
                    'hotel_id'     => $roomType->hotel_id,
                    'name'         => $roomType->name,
                    'capacity'     => (int)$roomType->capacity,
                    'bed_type'     => $roomType->bed_type,
                    'area'         => $roomType->area,
                    'amenities'    => is_array($amenitiesData) ? $amenitiesData : [],
                    'base_price'   => $roomType->base_price,
                    'currency'     => $roomType->currency,
                    'status'       => $roomType->status,
                    'images'       => $roomType->images->map(fn($img) => [
                        'id' => $img->id,
                        'image_url' => asset('storage/' . $img->image_url)
                    ])
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * XÓA 1 ẢNH
     */
    public function deleteImage($id)
    {
        $image = RoomImage::findOrFail($id);

        if (Storage::disk('public')->exists($image->image_url)) {
            Storage::disk('public')->delete($image->image_url);
        }

        $image->delete();

        return response()->json([
            'message' => 'Xóa ảnh thành công'
        ]);
    }



    /**
     * XOÁ ROOM TYPE
     */
    public function destroy($id)
    {
        $roomType = RoomType::with('images')->findOrFail($id);

        DB::beginTransaction();

        try {
            // Xóa tất cả ảnh liên quan
            $roomType->images->each(function ($image) {
                if (Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
                $image->delete();
            });

            // Xóa room type
            $roomType->delete();

            DB::commit();

            return response()->json([
                'message' => 'Xóa loại phòng thành công'
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Xóa thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * KHÔI PHỤC ROOM TYPE ĐÃ XÓA
     */
    public function restore($id)
    {
        // Tìm cả những record đã xóa
        $roomType = RoomType::withTrashed()->findOrFail($id);

        if (!$roomType->trashed()) {
            return response()->json([
                'message' => 'Phòng này chưa bị xóa, không cần khôi phục.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $roomType->restore(); // khôi phục soft delete

            DB::commit();

            return response()->json([
                'message' => 'Khôi phục loại phòng thành công',
                'data' => $roomType->load('images')
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Khôi phục thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
