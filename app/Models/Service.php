<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'price',
        'type'
    ];

    public function bookings()
    {
        return $this->hasMany(BookingService::class);
    }
}
