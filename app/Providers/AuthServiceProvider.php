<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Policies\ReservationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Reservation::class => ReservationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // configura rutas de Passport para tokens
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // define scopes
        Passport::tokensCan([
            'reservations:create' => 'Create reservations',
            'reservations:read' => 'Read reservations',
            'reservations:update' => 'Update reservations',
            'reservations:delete' => 'Delete reservations',
            'reservations:change-state' => 'Change reservation states',
        ]);
    }
}