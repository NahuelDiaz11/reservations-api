<?php

namespace Database\Factories;

use App\Enums\ReservationState;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'created_by' => $this->faker->userName,
            'address' => $this->faker->address,
            'lat' => $this->faker->latitude(-90, 90),
            'lng' => $this->faker->longitude(-180, 180),
            'state' => ReservationState::RESERVED,
        ];
    }
}