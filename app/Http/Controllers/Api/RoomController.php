<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
{
$query = Room::with('roomType');


if ($request->filled('status')) {
$query->where('status', $request->status);
}


if ($request->filled('room_type_id')) {
$query->where('room_type_id', $request->room_type_id);
}


return $query->paginate(10);
}


public function store(Request $request)
{
$data = $request->validate([
'room_number' => 'required|unique:rooms',
'room_type_id' => 'required|exists:room_types,id',
'floor' => 'required|integer',
'status' => 'in:available,booked,occupied,maintenance'
]);


return Room::create($data);
}


public function show(Room $room)
{
return $room->load('roomType');
}


public function update(Request $request, Room $room)
{
$room->update($request->only([
'room_number','room_type_id','floor','status'
]));


return $room;
}


public function destroy(Room $room)
{
if ($room->status !== 'available') {
return response()->json([
'message' => 'Chỉ được xóa phòng đang trống'
], 422);
}


$room->delete();
return response()->noContent();
}
}
