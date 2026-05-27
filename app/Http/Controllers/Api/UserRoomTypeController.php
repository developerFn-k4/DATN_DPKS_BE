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
            ->with(['images', 'rooms', 'reviews'])
            ->get();

        $result = $roomTypes->map(function ($type) use ($today) {

            // tổng phòng
            $totalRooms = $type->rooms->count();

            // phòng available
            $availableRooms = $type->rooms()
                ->where('status', 'available')
                ->whereDoesntHave('bookingRooms.booking', function ($q) use ($today) {
                    $q->whereDate('check_in', '<=', $today)
                        ->whereDate('check_out', '>', $today);
                })
                ->count();

            // --- Reviews ---
            $reviews = $type->reviews;

            $totalReviews = $reviews->count();

            $averageRate = $totalReviews > 0
                ? $reviews->avg('overall_score')
                : 0;
                 $ratingSummary = [
                'overall' => round((float) $averageRate, 1),
                'cleanliness' => round((float) ($reviews->avg('cleanliness') ?? 0), 1),
                'comfort' => round((float) ($reviews->avg('comfort') ?? 0), 1),
                'location' => round((float) ($reviews->avg('location') ?? 0), 1),
                'service' => round((float) ($reviews->avg('service') ?? 0), 1),
                'value' => round((float) ($reviews->avg('value') ?? 0), 1),
                'wifi' => round((float) ($reviews->avg('wifi') ?? 0), 1),
                'total_reviews' => $totalReviews,
            ];

            return [
                'room_type_id' => $type->id,
                'name' => $type->name,

                'capacity' => $type->capacity,
                'max_adults' => $type->max_adults,
                'max_children' => $type->max_children,

                'bed_type' => $type->bed_type,
                'area' => $type->area,

                'amenities' => is_array($type->amenities) ? $type->amenities : (json_decode($type->amenities) ?? []),

                'base_price' => $type->base_price,
                'currency' => $type->currency,

                'total_rooms' => $totalRooms,
                'available_rooms' => $availableRooms,

                'total_reviews' => $totalReviews,
                'average_rate' => round($averageRate, 1),
                 'rating_summary' => $ratingSummary,

                'images' => $type->images->map(function ($img) {
                    return asset('storage/' . $img->image_url);
                }),
            ];
        });

        return response()->json([
            'room_types' => $result
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REALTIME PRICE
    |--------------------------------------------------------------------------
    */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array'
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights = $checkIn->diffInDays($checkOut);

        $total = 0;
        $details = [];

        foreach ($request->rooms as $room) {
            $roomType = RoomType::findOrFail($room['room_type_id']);
            $price = $roomType->base_price * ($room['quantity'] ?? 1) * $nights;

            $details[] = [
                'room_type' => $roomType->name,
                'quantity' => $room['quantity'] ?? 1,
                'price' => $price
            ];

            $total += $price;
        }

        return response()->json([
            'nights' => $nights,
            'rooms' => $details,
            'total' => $total
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'name' => 'nullable|string',
            'room_type_id' => 'nullable|exists:room_types,id',
            'adults' => 'required|integer|min:1',
            'children_ages' => 'nullable|array',
            'quantity_rooms' => 'nullable|integer|min:1'
        ]);

        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $adults = (int)$request->adults;
        $childrenAges = $request->children_ages ?? [];
        $quantityRooms = $request->quantity_rooms ?? null;

        $children = count($childrenAges);

        $childrenUnder10 = 0;
        $childrenOver10 = 0;

        foreach ($childrenAges as $age) {
            if ($age < 10) {
                $childrenUnder10++;
            } else {
                $childrenOver10++;
            }
        }

        $totalGuests = $adults + $childrenOver10 + ($childrenUnder10 * 0.5);
        $requiredCapacity = ceil($totalGuests);

        $nights = \Carbon\Carbon::parse($checkIn)->diffInDays($checkOut);

        $roomTypesQuery = RoomType::where('status', 'active');

        if ($request->filled('name')) {
            $roomTypesQuery->where('name', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('room_type_id')) {
            $roomTypesQuery->where('id', $request->room_type_id);
        }

        $roomTypes = $roomTypesQuery->with('images')->get();

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

            if ($availableRooms == 0) continue;

            $capacityPerRoom = $type->capacity;

            if ($quantityRooms) {
                $roomsNeeded = $quantityRooms;
            } else {
                $roomsNeeded = ceil($requiredCapacity / $capacityPerRoom);
            }

            if ($availableRooms < $roomsNeeded) continue;

            $maxCapacity = $capacityPerRoom * $roomsNeeded;

            if ($maxCapacity < $requiredCapacity) continue;

            $pricePerNight = (float)$type->base_price;

            $basePrice = $pricePerNight * $roomsNeeded * $nights;

            $totalPrice = round($basePrice);

            $result[] = [
                'room_type_id' => $type->id,
                'name' => $type->name,
                'capacity_per_room' => $capacityPerRoom,
                'rooms_needed' => $roomsNeeded,
                'available_rooms' => $availableRooms,
                'bed_type' => $type->bed_type,
                'area' => $type->area,
                'amenities' => $type->amenities ? json_decode($type->amenities) : [],
                'price_per_room_per_night' => $pricePerNight,
                'nights' => $nights,
                'total_price' => $totalPrice,
                'currency' => $type->currency,
                'images' => $type->images->map(fn($img) => asset('storage/' . $img->image_url))
            ];
        }

        if (count($result) == 0) {
            return response()->json([
                'message' => 'Không có phòng phù hợp với yêu cầu của bạn'
            ], 404);
        }

        $collection = collect($result)->sortBy('total_price')->values();

        /*
    |--------------------------------------------------------------------------
    | BEST MATCH (CHEAPEST OPTION)
    |--------------------------------------------------------------------------
    */

        $bestMatch = $collection->first();

        return response()->json([
            'search_info' => [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'adults' => $adults,
                'children' => $children,
                'total_guests' => $totalGuests,
                'required_capacity' => $requiredCapacity,
                'quantity_rooms' => $quantityRooms
            ],
            'best_match' => $bestMatch,
            'room_types' => $collection
        ]);
    }
    public function show($id)
    {
        $today = Carbon::today();

        // Lấy loại phòng + ảnh
        $roomType = RoomType::with('images')
            ->where('status', 'active')
            ->findOrFail($id);

        // Tổng số phòng
        $totalRooms = Room::where('room_type_id', $roomType->id)->count();

        // Phòng còn trống hôm nay
        $availableRooms = Room::where('room_type_id', $roomType->id)
            ->where('status', 'available')
            ->whereDoesntHave('bookingRooms.booking', function ($query) use ($today) {
                $query->whereDate('check_in', '<=', $today)
                    ->whereDate('check_out', '>', $today);
            })
            ->count();

        $roomSlots = $availableRooms > 0 ? range(1, $availableRooms) : [];

        // Dịch vụ
        $services = Service::all()->map(function ($service) {
            return [
                'service_id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'currency' => $service->currency,
            ];
        });

        // --- Tính tổng lượt và điểm trung bình ---
        // Lấy tất cả review của loại phòng
        $reviews = $roomType->reviews()->with('user:id,name,avatar')->latest()->get();// nhớ tạo relation reviews() trong RoomType

        $totalReviews = $reviews->count();

        // Tính điểm trung bình từ các cột thực tế (ví dụ: cleanliness, comfort, service)
        $averageRate = $reviews->count() > 0
             ? $reviews->avg('overall_score')
            : 0;
 $ratingSummary = [
            'overall' => round((float) $averageRate, 1),
            'cleanliness' => round((float) ($reviews->avg('cleanliness') ?? 0), 1),
            'comfort' => round((float) ($reviews->avg('comfort') ?? 0), 1),
            'location' => round((float) ($reviews->avg('location') ?? 0), 1),
            'service' => round((float) ($reviews->avg('service') ?? 0), 1),
            'value' => round((float) ($reviews->avg('value') ?? 0), 1),
            'wifi' => round((float) ($reviews->avg('wifi') ?? 0), 1),
            'total_reviews' => $totalReviews,
        ];

        $comments = $reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'user_name' => $review->user->name ?? 'Khach hang',
                'user_avatar' => !empty($review->user->avatar) ? asset('storage/' . $review->user->avatar) : null,
                'overall_score' => round((float) $review->overall_score, 1),
                'cleanliness' => $review->cleanliness,
                'comfort' => $review->comfort,
                'location' => $review->location,
                'service' => $review->service,
                'value' => $review->value,
                'wifi' => $review->wifi,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
            ];
        });
        // Response
        return response()->json([
            'room_type' => [
                'id' => $roomType->id,
                'name' => $roomType->name,
                'capacity' => $roomType->capacity,
                'max_adults' => $roomType->max_adults,
                'max_children' => $roomType->max_children,
                'max_babies' => $roomType->max_babies,
                'max_occupancy' => $roomType->max_occupancy,
                'extra_adult_price' => $roomType->extra_adult_price,
                'extra_child_price' => $roomType->extra_child_price,
                'bed_type' => $roomType->bed_type,
                'area' => $roomType->area,
                'amenities' => $roomType->amenities ? json_decode($roomType->amenities) : [],
                'base_price' => $roomType->base_price,
                'currency' => $roomType->currency,
                'total_rooms' => $totalRooms,
                'available_rooms' => $availableRooms,
                'images' => $roomType->images->map(fn($img) => asset('storage/' . $img->image_url)),
                'room_slots' => $roomSlots,
                'services' => $services,
                'total_reviews' => $totalReviews,
                'average_rate' => round($averageRate, 1), // làm tròn 1 chữ số
                'rating_summary' => $ratingSummary,
                'comments' => $comments,
            ]
        ]);
    }
}
