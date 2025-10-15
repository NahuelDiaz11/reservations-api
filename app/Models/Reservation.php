<?php

namespace App\Models;

use App\Enums\ReservationState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'address',
        'lat',
        'lng',
        'state',
    ];

    protected $casts = [
        'state' => ReservationState::class,
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'state' => ReservationState::RESERVED,
    ];
}