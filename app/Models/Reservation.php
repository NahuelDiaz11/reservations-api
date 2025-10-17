<?php

namespace App\Models;

use App\Enums\ReservationState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Devuelve true si la reserva está en un estado final
     */
    public function isFinalState(): bool
    {
        return in_array($this->state, [
            ReservationState::INSTALLED,
            ReservationState::UNINSTALLED,
            ReservationState::CANCELED,
        ]);
    }

    /**
     * Verifica si puede cambiar de estado actual a otro
     */
    public function canTransitionTo(ReservationState $newState): bool
    {
        return match ($this->state) {
            ReservationState::RESERVED => in_array($newState, [
                ReservationState::SCHEDULED,
                ReservationState::CANCELED,
            ]),
            ReservationState::SCHEDULED => in_array($newState, [
                ReservationState::INSTALLED,
                ReservationState::CANCELED,
            ]),
            ReservationState::INSTALLED => [],
            ReservationState::UNINSTALLED => [],
            ReservationState::CANCELED => [],
            default => false,
        };
    }
}
