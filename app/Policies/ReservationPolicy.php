<?php

namespace App\Policies;

use App\Enums\ReservationState;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Determina si el usuario puede ver cualquier reserva
     */
    public function viewAny(User $user): bool
    {
        return true; // Todos los usuarios autenticados pueden ver reservas
    }

    /**
     * Determina si el usuario puede ver una reserva especifica
     */
    public function view(User $user, Reservation $reservation): bool
    {
        // admin y coordinators ven todas, otros solo las que crearon
        return $user->canManageAllReservations() || $reservation->created_by === $user->id;
    }

    /**
     * Determina si el usuario puede crear reservas
     */
    public function create(User $user): bool
    {
        // todos los usuarios autenticados pueden crear reservas
        return true;
    }

    /**
     * Determina si el usuario puede actualizar la reserva
     */
    public function update(User $user, Reservation $reservation): bool
    {
        // solo admin, coordinator o el creador pueden modificar (si no está en estado final)
        if ($reservation->isFinalState()) {
            return false;
        }

        return $user->canManageAllReservations() || $reservation->created_by === $user->id;
    }

    /**
     * Determina si el usuario puede cambiar el estado
     */
    public function changeState(User $user, Reservation $reservation, ReservationState $newState): bool
    {
        // no se puede modificar estados finales
        if ($reservation->isFinalState()) {
            return false;
        }

        // verificar transición válida
        if (!$reservation->canTransitionTo($newState)) {
            return false;
        }

        // verificar permisos basados en el estado destino y el rol
        return match ($newState) {
            ReservationState::SCHEDULED => $user->canChangeToScheduled(),
            ReservationState::INSTALLED => $user->canChangeToInstalled(),
            ReservationState::UNINSTALLED => $user->canChangeToUninstalled(),
            ReservationState::CANCELED => $user->canChangeToCanceled(),
            default => false,
        };
    }

    /**
     * Determina si el usuario puede eliminar la reserva
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        // solo admin puede eliminar, y solo si no está en estado final
        return $user->isAdmin() && !$reservation->isFinalState();
    }
}