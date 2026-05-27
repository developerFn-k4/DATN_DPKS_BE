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
     * DANH SÁCH ROOM TYPE (Đã sửa lỗi ẩn phòng do SoftDeletes và bọc an toàn chống lỗi 500)
     */
    public function index()
    {
        try {
            // Sử dụng withTrashed() để kéo lại toàn bộ phòng cũ/mới bị ẩn do dính cột deleted_at
            $roomTypes = RoomType::withTrashed()
                ->with('images')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($roomType) {
                    // Xử lý amenities đồng bộ: Nếu Model đã cast tự động thành mảng thì lấy luôn, nếu là chuỗi thì decode
                    $amenitiesData = $roomType->amenities;
                    if (is_string($amenitiesData)) {
                        $amenitiesData = json_decode($amenitiesData, true);
                    }

                    return [
                        'id' => $roomType->id,
                        'hotel_id' => $roomType->hotel_id,
                        'name' => $roomType->name,
                        'capacity' => (int)$roomType->capacity,
                        'bed_type' => $roomType->bed_type,
                        'area' => (int)$roomType->area,
                        'amenities' => is_array($amenitiesData) ? $amenitiesData : [],
                        'base_price' => $roomType->base_price,
                        'currency' => $roomType->currency,
                        'status' => $roomType->status,
                        'is_deleted' => $roomType->trashed(), // Báo về cho FE biết phòng này đang bị xóa mềm hay không
                        'created_at' => $roomType->created_at,
                        'updated_at' => $roomType->updated_at,
                        'images' => $roomType->images ? $roomType->images->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'image_url' => $image->image_url,
                                'url' => asset('storage/' . $image->image_url)
                            ];
                        }) : []
                    ];
                });

            return response()->json([
                'data' => $roomTypes
            ], 200);

        } catch (\Exception $e) {
            // Trả lỗi chi tiết dạng JSON nếu code gặp sự cố, tránh chết đứng sinh lỗi 500 trắng
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi lấy danh sách loại phòng tại Backend',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * CHI TIẾT ROOM TYPE
     */
    public function show($id)
    {
        try {
            $roomType = RoomType::withTrashed()->with('images')->findOrFail($id);
            
            $amenitiesData = $roomType->amenities;
            if (is_string($amenitiesData)) {
                $amenitiesData = json_decode($amenitiesData, true);
            }

            return response()->json([
                'data' => [
                    'id'         => $roomType->id,
                    'hotel_id'   => $roomType->hotel_id,
                    'name'       => $roomType->name,
                    'capacity'   => (int)$roomType->capacity,
                    'bed_type'   => $roomType->bed_type,
                    'area'       => (int)$roomType->area,
                    'amenities'  => is_array($amenitiesData) ? $amenitiesData : [],
                    'base_price' => $roomType->base_price,
                    'currency'   => $roomType->currency,
                    'status'     => $roomType->status,
                    'is_deleted' => $roomType->trashed(),
                    'created_at' => $roomType->created_at,
                    'updated_at' => $roomType->updated_at,
                    'images'     => $roomType->images->map(fn($img) => [
                        'id'        => $img->id,
                        'image_url' => $img->image_url,
                        'url'       => asset('storage/' . $img->image_url)
                    ])
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Không tìm thấy loại phòng hoặc lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
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
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096'
        ]);

        DB::beginTransaction();

        try {
            // Nếu Model có $casts array cho amenities, truyền thẳng mảng, ngược lại encode string
            $amenitiesInput = $request->amenities;

            $roomType = RoomType::create([
                'hotel_id' => $request->hotel_id,
                'name' => $request->name,
                'capacity' => $request->capacity,
                'bed_type' => $request->bed_type,
                'area' => $request->area,
                'amenities' => $amenitiesInput, 
                'base_price' => $request->base_price,
                'currency' => $request->currency,
                'status' => $request->status ?? 'active',
                'max_adults' => $request->max_adults ?? $request->capacity ?? 2,
                'max_children' => $request->max_children ?? 0,
            ]);

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
     * UPDATE ROOM TYPE
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'hotel_id' => 'sometimes|required|exists:hotels,id',
            'name' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer',
            'bed_type' => 'sometimes|required|string|max:255',
            'area' => 'sometimes|required|integer',
            'amenities' => 'nullable|array',
            'base_price' => 'sometimes|required|numeric',
            'currency' => 'sometimes|required|string|max:3',
            'status' => 'sometimes|nullable|in:active,inactive',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'keep_images' => 'sometimes|nullable'
        ]);

        $roomType = RoomType::withTrashed()->findOrFail($id);

        DB::beginTransaction();

        try {
            $amenities = $request->amenities;

            $roomType->update([
                'hotel_id'   => $request->hotel_id ?? $roomType->hotel_id,
                'name'       => $request->name ?? $roomType->name,
                'capacity'   => $request->has('capacity') ? (int)$request->capacity : $roomType->capacity,
                'bed_type'   => $request->bed_type ?? $roomType->bed_type,
                'area'       => $request->has('area') ? (int)$request->area : $roomType->area,
                'amenities'  => $request->has('amenities') ? $amenities : $roomType->amenities,
                'base_price' => $request->base_price ?? $roomType->base_price,
                'currency'   => $request->currency ?? $roomType->currency,
                'status'     => $request->status ?? $roomType->status,
            ]);

            // Xử lý ảnh cũ cần giữ lại
            $keepImages = $request->input('keep_images', []);
            if (is_string($keepImages)) {
                $keepImages = explode(',', $keepImages);
            }
            $keepImages = array_filter($keepImages, function($value) {
                return is_numeric($value) && $value > 0;
            });

            // Tìm và xóa ảnh không được giữ lại
            $imagesToDelete = $roomType->images->whereNotIn('id', $keepImages);
            foreach ($imagesToDelete as $img) {
                if (Storage::disk('public')->exists($img->image_url)) {
                    Storage::disk('public')->delete($img->image_url);
                }
                $img->delete();
            }

            // Lưu ảnh mới
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
                'message' => 'Cập nhật thất bại tại Server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * XÓA 1 ẢNH TRỰC TIẾP
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
     * XOÁ ROOM TYPE (Soft Delete)
     */
    public function destroy($id)
    {
        $roomType = RoomType::findOrFail($id);

        DB::beginTransaction();

        try {
            // Không xóa ảnh thật trong ổ cứng khi xóa mềm để có thể dùng hàm restore khôi phục lại nguyên vẹn
            $roomType->delete();
            DB::commit();

            return response()->json([
                'message' => 'Xóa loại phòng thành công (Xóa mềm)'
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
        $roomType = RoomType::withTrashed()->findOrFail($id);

        if (!$roomType->trashed()) {
            return response()->json([
                'message' => 'Phòng này chưa bị xóa, không cần khôi phục.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $roomType->restore();
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