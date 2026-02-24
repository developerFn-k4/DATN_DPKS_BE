<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    
    public function index()
    {
        $hotels = Hotel::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $hotels
        ]);
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'description' => 'nullable|string',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'status' => 'in:active,inactive',
        ]);

        $hotel = Hotel::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Hotel created successfully',
            'data' => $hotel
        ], 201);
    }

    
    public function show(Hotel $hotel)
    {
        return response()->json([
            'success' => true,
            'data' => $hotel
        ]);
    }

   
    public function update(Request $request, Hotel $hotel)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email',
            'description' => 'nullable|string',
            'check_in_time' => 'sometimes|required',
            'check_out_time' => 'sometimes|required',
            'status' => 'in:active,inactive',
        ]);

        $hotel->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Hotel updated successfully',
            'data' => $hotel
        ]);
    }

   
    public function destroy(Hotel $hotel)
    {
        $hotel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hotel deleted successfully'
        ]);
    }
}
