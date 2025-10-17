<?php

namespace App\Http\Requests;

use App\Enums\ReservationState;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('reservation'));
    }

    public function rules(): array
    {
        $reservation = $this->route('reservation');

        return [
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:500',
            'lat' => 'sometimes|required|numeric|between:-90,90',
            'lng' => 'sometimes|required|numeric|between:-180,180',
            'state' => [
                'sometimes',
                'string',
                Rule::in(ReservationState::values()),
                function ($attribute, $value, $fail) use ($reservation) {
                    $newState = ReservationState::from($value);

                    if ($reservation->isFinalState()) {
                        $fail('No se puede modificar una reserva en estado final: ' . $reservation->state->value);
                        return;
                    }

                    if (!$reservation->canTransitionTo($newState)) {
                        $fail('Transición de estado no válida: ' . $reservation->state->value . ' → ' . $value);
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la reserva es obligatorio',
            'address.required' => 'La dirección es obligatoria',
            'lat.required' => 'La latitud es obligatoria',
            'lat.between' => 'La latitud debe estar entre -90 y 90',
            'lng.required' => 'La longitud es obligatoria',
            'lng.between' => 'La longitud debe estar entre -180 y 180',
            'state.in' => 'El estado proporcionado no es válido',
        ];
    }
}
