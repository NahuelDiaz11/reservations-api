<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
  public function __construct(
        private ReservationQueryService $queryService
    ) {}

    protected function booted(): void
    {
        $this->middleware('auth:api');
        $this->authorizeResource(Reservation::class, 'reservation');
    }


    /**
     * Crear nueva reserva
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        try {
            $reservation = Reservation::create([
                'name' => $request->name,
                'created_by' => $request->user()->id,
                'address' => $request->address,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'state' => $request->state ?? 'RESERVED',
            ]);

            return response()->json([
                'message' => 'Reserva creada exitosamente',
                'data' => new ReservationResource(
                    $reservation->load(['user', 'user.role'])
                )
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error('Error al crear la reserva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'message' => 'Error al crear la reserva',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar reserva específica
     */
    public function show(Reservation $reservation): JsonResponse
    {
        try {
            $reservation->load(['user', 'user.role']);

            return response()->json([
                'data' => new ReservationResource($reservation),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error al mostrar la reserva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reservation_id' => $reservation->id ?? null,
            ]);

            return response()->json([
                'message' => 'Error al obtener la reserva',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
