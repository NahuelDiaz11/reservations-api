<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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
        $reservation = Reservation::create([
            'name' => $request->name,
            'created_by' => $request->user()->id,
            'address' => $request->address,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'state' => $request->state,
        ]);

        return response()->json([
            'message' => 'Reserva creada exitosamente',
            'data' => new ReservationResource($reservation->load(['user', 'user.role']))
        ], Response::HTTP_CREATED);
    }

}
