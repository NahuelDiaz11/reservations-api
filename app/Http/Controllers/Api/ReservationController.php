<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

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
     * Lista reservas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $canViewAll = $request->user()->canManageAllReservations();
            $user = $canViewAll ? null : $request->user();

            $query = $this->queryService->buildQuery($request);
            $reservations = $this->queryService->paginateResults($query, $request);
            $metadata = $this->queryService->getFiltersMetadata($request, $reservations);

            return response()->json([
                'data' => ReservationResource::collection($reservations),
                'meta' => $metadata,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error al listar reservas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null,
                'filters' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Error al obtener la lista de reservas',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

    /**
     * Actualizar reserva
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (isset($validated['state'])) {
                unset($validated['state']);
            }

            $reservation->update($validated);

            return response()->json([
                'message' => 'Reserva actualizada exitosamente',
                'data' => new ReservationResource($reservation->fresh()->load(['user', 'user.role'])),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar la reserva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reservation_id' => $reservation->id ?? null,
                'user_id' => $request->user()->id ?? null,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Error al actualizar la reserva',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
