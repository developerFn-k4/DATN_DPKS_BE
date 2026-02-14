<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
=======
>>>>>>> main

class RoomType extends Model
{
    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'capacity',
        'bed_type',
        'base_price',
        'currency',
        'status',
    ];

<<<<<<< HEAD
    // loại phòng thuộc về 1 khách sạn
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    // loại phòng có nhiều phòng cụ thể
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    // loại phòng có nhiều ảnh
    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class);
=======

    public function hotel()
    {
       return $this->belongsTo(Hotel::class);
>>>>>>> main
    }
}
