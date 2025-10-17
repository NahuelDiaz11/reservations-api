<?php

namespace App\Http\Requests;

use App\Enums\ReservationState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="StoreReservationRequest",
 *     required={"name", "address", "lat", "lng"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Reserva Ejemplo"),
 *     @OA\Property(property="address", type="string", maxLength=500, example="Calle Falsa 123"),
 *     @OA\Property(property="lat", type="number", format="float", minimum=-90, maximum=90, example=-34.603722),
 *     @OA\Property(property="lng", type="number", format="float", minimum=-180, maximum=180, example=-58.381592),
 *     @OA\Property(property="state", type="string", enum={"RESERVED", "SCHEDULED", "INSTALLED", "UNINSTALLED", "CANCELED"}, example="RESERVED")
 * )
 */
class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización se maneja en el Policy
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'state' => [
                'sometimes',
                'string',
                Rule::in(ReservationState::values()),
            ],
            // created_by se asigna automáticamente desde el usuario autenticado
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

    public function prepareForValidation()
    {
        // asegura que el estado por defecto sea RESERVED si no se proporciona
        if (!$this->has('state')) {
            $this->merge([
                'state' => ReservationState::RESERVED->value,
            ]);
        }
    }
}
