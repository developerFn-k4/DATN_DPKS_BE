<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'order_id',
        'request_id',
        'amount',
        'method',
        'transaction_id',
        'status'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
