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
        'total_price'
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests' => 'integer',
        'total_price' => 'decimal:2'
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
}
