<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'capacity',
        'bed_type',
        'base_price'
    ];
}
