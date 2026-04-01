<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use Illuminate\Http\Request;

class UserRoomTypeController extends Controller
{
    // Danh sách phòng (index)
    public function index()
    {
        $today = Carbon::today();

        $roomTypes = RoomType::where('status', 'active')
            ->with('images')
            ->withCount('rooms')
            ->whereHas('rooms', function ($query) use ($today) {
                $query->whereDoesntHave('bookingRooms.booking', function ($q) use ($today) {
                    $q->whereDate('check_in', '<=', $today)
                        ->whereDate('check_out', '>=', $today);
                });
            })
            ->get();

        $result = $roomTypes->map(function ($type) use ($today) {

            $availableRooms = $type->rooms()
                ->whereDoesntHave('bookingRooms.booking', function ($q) use ($today) {
                    $q->whereDate('check_in', '<=', $today)
                        ->whereDate('check_out', '>=', $today);
                })
                ->count();

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
                'available_rooms' => $availableRooms,
                'images' => $type->images->map(fn($img) => asset('storage/' . $img->image_url)),
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
            'name' => 'nullable|string',
            'adults' => 'nullable|integer|min:1',
            'children_ages' => 'nullable|array'
        ]);

        $checkIn = $request->check_in;
        $checkOut = $request->check_out;

        $adults = (int) $request->adults;
        $childrenAges = $request->children_ages ?? [];

        $children = 0;
        $childrenWeight = 0;

        foreach ($childrenAges as $age) {

            if ($age < 10) {
                $childrenWeight += 0.5;
            } else {
                $childrenWeight += 1;
            }

            $children++;
        }

        $totalGuests = $adults + $childrenWeight;
        $requiredCapacity = ceil($totalGuests);

        $nights = Carbon::parse($checkIn)->diffInDays($checkOut);

        /*
    |--------------------------------------------------------------------------
    | Query room types
    |--------------------------------------------------------------------------
    */

        $roomTypesQuery = RoomType::where('status', 'active');

        // tìm theo tên
        if ($request->filled('name')) {
            $roomTypesQuery->where('name', 'LIKE', '%' . $request->name . '%');
        }

        // tìm theo số người
        if ($request->filled('adults')) {
            $roomTypesQuery->where('capacity', '>=', $requiredCapacity);
        }

        $roomTypes = $roomTypesQuery->with('images')->get();

        $services = Service::all();

        $result = [];

        foreach ($roomTypes as $type) {

            $availableRooms = Room::where('room_type_id', $type->id)
                ->where('status', 'available')
                ->whereDoesntHave('bookingRooms.booking', function ($q) use ($checkIn, $checkOut) {
                    $q->where(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn);
                    });
                })
                ->count();

            if ($availableRooms > 0) {

                $pricePerNight = $type->base_price;
                $totalPrice = $pricePerNight * $nights;

                $finalPrice = round($totalPrice * 1.05);

                $result[] = [
                    'room_type_id' => $type->id,
                    'name' => $type->name,
                    'capacity' => $type->capacity,
                    'bed_type' => $type->bed_type,
                    'area' => $type->area,

                    'amenities' => $type->amenities
                        ? json_decode($type->amenities)
                        : [],

                    'price_per_night' => $pricePerNight,
                    'nights' => $nights,
                    'total_price' => $finalPrice,
                    'currency' => $type->currency,

                    'available_rooms' => $availableRooms,

                    'images' => $type->images->map(function ($img) {
                        return asset('storage/' . $img->image_url);
                    }),

                    'services' => $services->map(function ($s) {
                        return [
                            'service_id' => $s->id,
                            'name' => $s->name,
                            'price' => $s->price,
                            'currency' => $s->currency
                        ];
                    })
                ];
            }
        }

        if (count($result) == 0) {
            return response()->json([
                'message' => 'Không có phòng phù hợp'
            ], 404);
        }

        $collection = collect($result)
            ->sortBy([
                ['capacity', 'asc'],
                ['total_price', 'asc']
            ])
            ->values();

        return response()->json([
            'search_info' => [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'adults' => $adults,
                'children' => $children,
                'total_guests' => $totalGuests
            ],
            'room_types' => $collection
        ]);
    }

    // Show chi tiết 1 phòng
    public function show($id)
    {
        $roomType = RoomType::with('images')->where('status', 'active')->findOrFail($id);
        $images = $roomType->images->map(fn($img) => asset('storage/' . $img->image_url));

        return response()->json([
            'room_type' => [
                'id' => $roomType->id,
                'hotel_id' => $roomType->hotel_id,
                'name' => $roomType->name,
                'capacity' => $roomType->capacity,
                'bed_type' => $roomType->bed_type,
                'area' => $roomType->area,
                'amenities' => $roomType->amenities ? json_decode($roomType->amenities) : [],
                'base_price' => $roomType->base_price,
                'currency' => $roomType->currency,
                'status' => $roomType->status
            ],
            'images' => $images
        ]);
    }
}
