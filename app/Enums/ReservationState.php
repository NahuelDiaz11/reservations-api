<?php

namespace App\Enums;

enum ReservationState: string
{
    case RESERVED = 'RESERVED';
    case SCHEDULED = 'SCHEDULED';
    case INSTALLED = 'INSTALLED';
    case UNINSTALLED = 'UNINSTALLED';
    case CANCELED = 'CANCELED';

    public function canTransitionTo(self $newState): bool
    {
        return match ($this) {
            self::RESERVED => in_array($newState, [self::SCHEDULED, self::CANCELED]),
            self::SCHEDULED => in_array($newState, [self::INSTALLED, self::CANCELED]),
            self::INSTALLED => $newState === self::UNINSTALLED,
            self::CANCELED, self::UNINSTALLED => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELED, self::UNINSTALLED]);
    }
}