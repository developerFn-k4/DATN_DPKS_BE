<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'room_type_id',
        'user_id',
        'booking_id',
        'cleanliness',
        'comfort',
        'location',
        'service',
        'value',
        'wifi',
        'overall_score',
        'comment'
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
