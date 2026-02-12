<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomType;

class RoomTypeApiController extends Controller
{
    public function index()
    {
        return response()->json(RoomType::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id'   => 'nullable',
            'name'       => 'required',
            'description'=> 'nullable',
            'capacity'   => 'required|integer',
            'bed_type'   => 'required',
            'base_price' => 'required|numeric'
        ]);

        $roomType = RoomType::create($data);

        return response()->json($roomType, 201);
    }

    public function show($id)
    {
        return response()->json(RoomType::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $roomType = RoomType::findOrFail($id);

        $data = $request->validate([
            'hotel_id'   => 'nullable',
            'name'       => 'required',
            'description'=> 'nullable',
            'capacity'   => 'required|integer',
            'bed_type'   => 'required',
            'base_price' => 'required|numeric'
        ]);

        $roomType->update($data);

        return response()->json($roomType);
    }

    public function destroy($id)
    {
        RoomType::destroy($id);

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
