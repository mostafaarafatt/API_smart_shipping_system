<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Driver extends Authenticatable
{
    use HasFactory;
    protected $fillable = [
        'name',
        'password',
        'phone',
        'national_ID',
        'car_number',
        'address',
        'image',
        'user_rate',
        'count_rate',
    ];

    
}
