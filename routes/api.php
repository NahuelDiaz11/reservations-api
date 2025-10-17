<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas protegidas
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::prefix('reservations')->group(function () {
        Route::post('/', [ReservationController::class, 'store'])
            ->name('reservations.store');

        Route::get('/{reservation}', [ReservationController::class, 'show'])
            ->name('reservations.show');
    });

    Route::patch('/{reservation}/state', [ReservationController::class, 'changeState'])
        ->name('reservations.state.update');
});
