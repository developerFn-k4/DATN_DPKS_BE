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
                $amenitiesData = $roomType->amenities;
                if (is_string($amenitiesData)) {
                    $amenitiesData = json_decode($amenitiesData, true);
                }

                return [
                    'id' => $roomType->id,
                    'hotel_id' => $roomType->hotel_id,
                    'name' => $roomType->name,
                    'capacity' => $roomType->capacity,
                    'bed_type' => $roomType->bed_type,
                    'area' => $roomType->area,
                    'amenities' => is_array($amenitiesData) ? $amenitiesData : [],
                    'base_price' => $roomType->base_price,
                    'currency' => $roomType->currency,
                    'status' => $roomType->status,
                    'created_at' => $roomType->created_at,
                    'updated_at' => $roomType->updated_at,
                    'images' => $roomType->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_url' => $image->image_url,
                            'url' => asset('storage/' . $image->image_url)
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
        
        $amenitiesData = $roomType->amenities;
        if (is_string($amenitiesData)) {
            $amenitiesData = json_decode($amenitiesData, true);
        }

        return response()->json([
            'data' => [
                'id'         => $roomType->id,
                'hotel_id'   => $roomType->hotel_id,
                'name'       => $roomType->name,
                'capacity'   => $roomType->capacity,
                'bed_type'   => $roomType->bed_type,
                'area'       => $roomType->area,
                'amenities'  => is_array($amenitiesData) ? $amenitiesData : [],
                'base_price' => $roomType->base_price,
                'currency'   => $roomType->currency,
                'status'     => $roomType->status,
                'created_at' => $roomType->created_at,
                'updated_at' => $roomType->updated_at,
                'images'     => $roomType->images->map(fn($img) => [
                    'id'        => $img->id,
                    'image_url' => $img->image_url,
                    'url'       => asset('storage/' . $img->image_url)
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
            'hotel_id' => 'required|exists:hotels,id',
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
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096' // Tăng max size lên 4MB cho thoải mái
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
                'status' => $request->status ?? 'active'
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
     * UPDATE ROOM TYPE - Đã sửa lỗi Method PUT FormData, lỗi JSON và lỗi xóa file trên Linux
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

        $roomType = RoomType::with('images')->findOrFail($id);

        DB::beginTransaction();

        try {
            // Chuẩn hóa và mã hóa trường amenities sang JSON để lưu vào MySQL
            $amenities = $request->amenities;
            if (is_array($amenities)) {
                $amenities = json_encode($amenities);
            }

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

            // XỬ LÝ XÓA ẢNH CŨ (Dựa trên danh sách giữ lại từ client gửi lên)
            $keepImages = $request->input('keep_images', []);
            if (is_string($keepImages)) {
                $keepImages = explode(',', $keepImages);
            }
            // Lọc bỏ các phần tử rỗng hoặc không là số
            $keepImages = array_filter($keepImages, function($value) {
                return is_numeric($value) && $value > 0;
            });

            // Tìm những ảnh thuộc phòng này nhưng KHÔNG nằm trong mảng giữ lại
            $imagesToDelete = $roomType->images->whereNotIn('id', $keepImages);
            foreach ($imagesToDelete as $img) {
                if (Storage::disk('public')->exists($img->image_url)) {
                    Storage::disk('public')->delete($img->image_url);
                }
                $img->delete();
            }

            // UPLOAD THÊM ẢNH MỚI (Nếu có)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('room_types', 'public');
                    RoomImage::create([
                        'room_type_id' => $roomType->id,
                        'image_url'    => $path
                    ]);
                }
            }

            // Đồng bộ lại quan hệ sau khi thêm/xóa ảnh
            $roomType->load('images');
            DB::commit();

            $finalAmenities = $roomType->amenities;
            if (is_string($finalAmenities)) {
                $finalAmenities = json_decode($finalAmenities, true);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật loại phòng thành công',
                'data' => [
                    'room_type_id' => $roomType->id,
                    'hotel_id'     => $roomType->hotel_id,
                    'name'         => $roomType->name,
                    'capacity'     => (int)$roomType->capacity,
                    'bed_type'     => $roomType->bed_type,
                    'area'         => (int)$roomType->area,
                    'amenities'    => is_array($finalAmenities) ? $finalAmenities : [],
                    'base_price'   => $roomType->base_price,
                    'currency'     => $roomType->currency,
                    'status'       => $roomType->status,
                    'images'       => $roomType->images->map(fn($img) => [
                        'id' => $img->id,
                        'image_url' => $img->image_url,
                        'url' => asset('storage/' . $img->image_url)
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
     * XOÁ ROOM TYPE
     */
    public function destroy($id)
    {
        $roomType = RoomType::with('images')->findOrFail($id);

        DB::beginTransaction();

        try {
            $roomType->images->each(function ($image) {
                if (Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
                $image->delete();
            });

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