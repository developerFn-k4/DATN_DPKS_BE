<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_code',
        'user_id',
        'name',
        'email',
        'phone',
        'check_in',
        'check_out',
        'nights',
        'status',
        'guests',
        'expired_at',
        'total_price'
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'expired_at' => 'datetime',
        'guests' => 'integer',
        'total_price' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->booking_code = 'BK-' . strtoupper(Str::random(6));
        });
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->hasMany(BookingService::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }
}
