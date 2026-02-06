<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index()
    {
        return RoomType::withCount('rooms')->get();
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:100',
            'description' => 'nullable',
            'base_price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1'
        ]);


        return RoomType::create($data);
    }
    public function show(RoomType $roomType)
    {
        return $roomType->load('rooms');
    }


    public function update(Request $request, RoomType $roomType)
    {
        $roomType->update($request->only([
            'name',
            'description',
            'base_price',
            'max_guests'
        ]));


        return $roomType;
    }
    public function destroy(RoomType $roomType)
    {
        if ($roomType->rooms()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa loại phòng đang có phòng'
            ], 422);
        }


        $roomType->delete();
        return response()->noContent();
    }
}
