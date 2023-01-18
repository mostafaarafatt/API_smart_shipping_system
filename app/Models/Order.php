<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_name',
        'length',
        'height',
        'width',
        'weight',
        'start_place',
        'end_place',
        'price',
        'client_phone',
        'client_name',
        'user_id',
        'driver_id',
        'state',
        'reset',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
