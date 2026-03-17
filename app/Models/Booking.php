<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_id',
        'user_id',
        'check_in',
        'check_out',
        'guests',
        'status',
        'payment_method',
        'payment_status',
        'total_price',
        'payment_txn_ref',
        'payment_transaction_no',
        'paid_at',
        'payment_response',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests' => 'integer',
        'total_price' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_response' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Booking thuộc về một phòng
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Booking thuộc về một user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
