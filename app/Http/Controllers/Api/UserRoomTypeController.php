<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class UserRoomTypeController extends Controller
{
    // Danh sách phòng
    public function index()
    {
        $roomTypes = RoomType::where('status', 'active')
            ->withCount('rooms') // tổng số phòng
            ->get();

        $result = $roomTypes->map(function ($type) {

            // phòng đang trống
            $availableRooms = Room::where('room_type_id', $type->id)
                ->whereDoesntHave('bookings', function ($query) {

                    $query->where(function ($q) {
                        $q->whereDate('check_in', '<=', now())
                            ->whereDate('check_out', '>=', now());
                    });
                })->count();

            return [
                'room_type_id' => $type->id,
                'name' => $type->name,
                'capacity' => $type->capacity,
                'bed_type' => $type->bed_type,

                'area' => $type->area,
                'amenities' => $type->amenities ? json_decode($type->amenities) : [],

                'base_price' => $type->base_price,
                'currency' => $type->currency,

                'total_rooms' => $type->rooms_count,
                'available_rooms' => $availableRooms
            ];
        });

        return response()->json([
            'room_types' => $result
        ]);
    }
    // Tìm kiếm phòng
    public function search(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children_ages' => 'nullable|array'
        ]);

        $checkIn = $request->check_in;
        $checkOut = $request->check_out;

        $adults = (int) $request->adults;
        $childrenAges = $request->children_ages ?? [];

        $children = 0;

        // phân loại trẻ em
        foreach ($childrenAges as $age) {

            if ($age >= 11) {
                $adults++; // tính như người lớn
            } else {
                $children++; // miễn phí
            }
        }

        $totalGuests = $adults;

        $nights = Carbon::parse($checkIn)->diffInDays($checkOut);

        // lấy loại phòng phù hợp
        $roomTypes = RoomType::where('status', 'active')
            ->where('capacity', '>=', $totalGuests)
            ->get();

        $result = [];

        foreach ($roomTypes as $type) {

            $availableRooms = Room::where('room_type_id', $type->id)
                ->where('status', 'available')
                ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {

                    $query->where(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn);
                    });
                })->count();


            if ($availableRooms > 0) {

                $pricePerNight = $type->base_price;

                $totalPrice = $pricePerNight * $nights;

                $finalPrice = $totalPrice * 1.05;

                $result[] = [
                    'room_type_id' => $type->id,
                    'name' => $type->name,
                    'capacity' => $type->capacity,
                    'bed_type' => $type->bed_type,
                    'area' => $type->area,
                    'amenities' => $type->amenities ? json_decode($type->amenities) : [],
                    'price_per_night' => $pricePerNight,
                    'nights' => $nights,
                    'total_price' => round($finalPrice),
                    'price_note' => 'Đã bao gồm thuế và phí',
                    'currency' => $type->currency,
                    'available_rooms' => $availableRooms
                ];
            }
        }

        $collection = collect($result)
            ->sortBy([
                ['capacity', 'asc'],
                ['total_price', 'asc']
            ])
            ->values();

        $bestRoom = $collection->first();

        $recommendation = null;

        if ($bestRoom) {

            $recommendation = [
                'message' => "Lựa chọn rẻ nhất tại chỗ nghỉ này cho {$adults} người lớn, {$children} trẻ em",
                'room_type_id' => $bestRoom['room_type_id'],
                'room_type' => $bestRoom['name'],
                'capacity' => $bestRoom['capacity'],
                'bed_type' => $bestRoom['bed_type'],
                'area' => $bestRoom['area'],
                'total_price' => $bestRoom['total_price'],
                'price_note' => 'Đã bao gồm thuế và phí',
                'currency' => $bestRoom['currency'],
                'available_rooms' => $bestRoom['available_rooms']
            ];
        }

        return response()->json([
            'search_info' => [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'adults' => $adults,
                'children' => $children,
                'total_guests' => $totalGuests
            ],
            'recommendation' => $recommendation,
            'room_types' => $collection
        ]);
    }

    // show
    public function show($id)
    {
        $roomType = RoomType::with('images')
            ->where('status', 'active')
            ->findOrFail($id);

        $images = $roomType->images->map(function ($img) {
            return $img->image_url;
        });

        return response()->json([
            'room_type' => [
                'id' => $roomType->id,
                'hotel_id' => $roomType->hotel_id,
                'name' => $roomType->name,
                'capacity' => $roomType->capacity,
                'bed_type' => $roomType->bed_type,

                // trường mới
                'area' => $roomType->area,
                'amenities' => $roomType->amenities ? json_decode($roomType->amenities) : [],

                'base_price' => $roomType->base_price,
                'currency' => $roomType->currency,
                'status' => $roomType->status,
            ],

            'images' => $images
        ]);
    }
}
