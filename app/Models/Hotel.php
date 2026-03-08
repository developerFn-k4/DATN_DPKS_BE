<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;


class Hotel extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'description',
        'check_in_time',
        'check_out_time',
        'status',
    ];


    // 1 hotel có nhiều loại phòng
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

}
