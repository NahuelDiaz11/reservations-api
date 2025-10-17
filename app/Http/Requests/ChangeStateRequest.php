<?php

namespace App\Http\Requests;

use App\Enums\ReservationState;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChangeStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'state' => [
                'required',
                'string',
                Rule::in(ReservationState::values()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'state.required' => 'El nuevo estado es obligatorio',
            'state.in' => 'El estado proporcionado no es válido',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $reservation = $this->route('reservation');
            $newState = ReservationState::from($this->input('state'));

            if ($reservation->isFinalState()) {
                $validator->errors()->add(
                    'state',
                    'No se puede modificar el estado de una reserva en estado final: ' . $reservation->state->value
                );
                return;
            }

            if (!$reservation->canTransitionTo($newState)) {
                $validator->errors()->add(
                    'state',
                    'Transición de estado no válida: ' . $reservation->state->value . ' → ' . $newState->value
                );
            }

        });
    }
}
