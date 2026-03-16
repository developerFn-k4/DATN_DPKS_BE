<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_number',
        'room_type_id',
        'floor',
        'status',
        'note',
        'price'
    ];

    protected $casts = [
        'floor' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Một phòng thuộc về một loại phòng
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    // Một phòng có nhiều booking
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function images()
    {
        return $this->hasMany(RImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
