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
    public function update(User $user, Reservation $reservation): Response
    {
        if ($reservation->isFinalState()) {
            return Response::deny('No se puede modificar una reserva en estado final: ' . $reservation->state->value);
        }

        // if (!($user->canManageAllReservations() || $reservation->created_by === $user->id)) {
        //     return Response::deny('No tiene permisos para modificar esta reserva.');
        // }

        return Response::allow();
    }

    /**
     * Determina si el usuario puede cambiar el estado
     */
    public function changeState(User $user, Reservation $reservation, ReservationState $newState): Response
    {
        // no se puede modificar estados finales
        if ($reservation->isFinalState()) {
            return Response::deny('No se puede modificar una reserva en estado final: ' . $reservation->state->value);
        }

        // verificar transición válida
        if (!$reservation->canTransitionTo($newState)) {
            return Response::deny('Transición de estado no válida: ' . $reservation->state->value . ' → ' . $newState->value);
        }

        // verificar permisos basados en el estado destino y el rol
        $hasPermission = match ($newState) {
            ReservationState::SCHEDULED => $user->canChangeToScheduled(),
            ReservationState::INSTALLED => $user->canChangeToInstalled(),
            ReservationState::UNINSTALLED => $user->canChangeToUninstalled(),
            ReservationState::CANCELED => $user->canChangeToCanceled(),
            default => false,
        };

         if (!$hasPermission) {
            $roleName = $user->role->name;
            return Response::deny("Su rol {$roleName} no tiene permisos para cambiar al estado {$newState->value}");
        }

        return Response::allow();
    }

}