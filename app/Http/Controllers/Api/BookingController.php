<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = Booking::with(['room', 'user'])->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'total_price' => 'required|numeric'
        ]);

        $booking = Booking::create($validated);

        return response()->json([
            'success' => true,
            'data' => $booking
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $booking = Booking::with(['room', 'user'])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $validated = $request->validate([
            'check_in' => 'date',
            'check_out' => 'date|after:check_in',
            'total_price' => 'numeric'
        ]);

        $booking->update($validated);

        return response()->json([
            'success' => true,
            'data' => $booking
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ], 200);
    }
}
