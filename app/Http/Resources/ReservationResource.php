<?php

namespace App\Http\Resources;

use App\Enums\ReservationState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_by' => $this->created_by,
            'creator' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role->name,
            ],
            'address' => $this->address,
            'coordinates' => [
                'lat' => (float) $this->lat,
                'lng' => (float) $this->lng,
            ],
            'state' => $this->state->value,
            // traducción del estado
            'state_label' => $this->getStateLabel($this->state),
            'is_final_state' => $this->isFinalState(),
            'allowed_transitions' => $this->getAllowedTransitions($request->user()),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            // enlaces relacionados
            'links' => [
                'self' => route('reservations.show', $this->id),
                'update' => route('reservations.update', $this->id),
                'change_state' => route('reservations.state.update', $this->id),
            ],
        ];
    }

    // Traduce el estado al español
    private function getStateLabel(ReservationState $state): string
    {
        return match ($state) {
            ReservationState::RESERVED => 'Reservado',
            ReservationState::SCHEDULED => 'Programado',
            ReservationState::INSTALLED => 'Instalado',
            ReservationState::UNINSTALLED => 'Desinstalado',
            ReservationState::CANCELED => 'Cancelado',
        };
    }

    // Devuelve las transiciones de estado permitidas para el usuario actual
    private function getAllowedTransitions($user): array
    {
        if ($this->isFinalState()) {
            return [];
        }

        $allowed = [];
        $currentState = $this->state;

        foreach (ReservationState::cases() as $state) {
            if ($currentState->canTransitionTo($state) && 
                $user->can('changeState', [$this->resource, $state])) {
                $allowed[] = [
                    'state' => $state->value,
                    'label' => $this->getStateLabel($state),
                    'endpoint' => route('reservations.state.update', $this->id),
                    'method' => 'PATCH',
                ];
            }
        }

        return $allowed;
    }
    
}