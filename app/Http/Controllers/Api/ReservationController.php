<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStateRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @OA\Tag(
 *     name="Reservations",
 *     description="Operaciones relacionadas con reservas"
 * )
 */
class ReservationController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private ReservationQueryService $queryService
    ) {}

    protected function booted(): void
    {
        $this->middleware('auth:api');
        $this->authorizeResource(Reservation::class, 'reservation');
    }

    /**
     * Listar reservas
     *
     * @OA\Get(
     *     path="/api/reservations",
     *     operationId="getReservations",
     *     tags={"Reservations"},
     *     summary="Obtener lista de reservas",
     *     description="Retorna una lista paginada de reservas. Los usuarios regulares solo ven sus propias reservas, mientras que admins y coordinadores ven todas.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="filter[q]",
     *         in="query",
     *         description="Búsqueda por nombre, dirección o usuario",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[state]",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"RESERVED", "SCHEDULED", "INSTALLED", "UNINSTALLED", "CANCELED"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="filter[created_by]",
     *         in="query",
     *         description="Filtrar por ID de usuario creador",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter[lat]",
     *         in="query",
     *         description="Latitud para filtro geográfico (debe usarse con lng y radius_km)",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=-34.603722)
     *     ),
     *     @OA\Parameter(
     *         name="filter[lng]",
     *         in="query",
     *         description="Longitud para filtro geográfico (debe usarse con lat y radius_km)",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=-58.381592)
     *     ),
     *     @OA\Parameter(
     *         name="filter[radius_km]",
     *         in="query",
     *         description="Radio en kilómetros para filtro geográfico (debe usarse con lat y lng)",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=10.5)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordenar por campo (prefijar con - para orden descendente)",
     *         required=false,
     *         @OA\Schema(type="string", example="-created_at")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de reservas obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ReservationResource")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="filters_applied", type="object")
     *             ),
     *             @OA\Property(property="message", type="string", example="No se encontraron reservas.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $canViewAll = $request->user()->canManageAllReservations();
            $user = $canViewAll ? null : $request->user();

            $query = $this->queryService->buildQuery($request, $user);
            $reservations = $this->queryService->paginateResults($query, $request);
            $metadata = $this->queryService->getFiltersMetadata($request, $reservations);

            $response = [
                'data' => ReservationResource::collection($reservations),
                'meta' => $metadata,
            ];

            if ($reservations->isEmpty()) {
                $response['message'] = 'No se encontraron reservas.';
            }

            return response()->json($response, Response::HTTP_OK);
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
     *
     * @OA\Post(
     *     path="/api/reservations",
     *     operationId="createReservation",
     *     tags={"Reservations"},
     *     summary="Crear una nueva reserva",
     *     description="Crea una nueva reserva en el sistema",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreReservationRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reserva creada exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/ReservationResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado"
     *     )
     * )
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
     *
     * @OA\Get(
     *     path="/api/reservations/{id}",
     *     operationId="getReservation",
     *     tags={"Reservations"},
     *     summary="Obtener una reserva específica",
     *     description="Retorna los detalles de una reserva específica",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reserva obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ReservationResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reserva no encontrada"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para ver esta reserva"
     *     )
     * )
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
     *
     * @OA\Put(
     *     path="/api/reservations/{id}",
     *     operationId="updateReservation",
     *     tags={"Reservations"},
     *     summary="Actualizar una reserva existente",
     *     description="Actualiza los datos de una reserva existente. No permite cambiar el estado.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateReservationRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reserva actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reserva actualizada exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/ReservationResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para actualizar esta reserva"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            $this->authorize('update', $reservation);

            $validated = $request->validated();

            if (isset($validated['state'])) {
                unset($validated['state']);
            }

            $reservation->update($validated);

            return response()->json([
                'message' => 'Reserva actualizada exitosamente',
                'data' => new ReservationResource($reservation->fresh()->load(['user', 'user.role'])),
            ], Response::HTTP_OK);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
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

     /**
     * Cambiar estado de reserva
     *
     * @OA\Patch(
     *     path="/api/reservations/{id}/state",
     *     operationId="changeReservationState",
     *     tags={"Reservations"},
     *     summary="Cambiar el estado de una reserva",
     *     description="Cambia el estado de una reserva existente. Solo permite transiciones válidas según el estado actual y los permisos del usuario.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"state"},
     *             @OA\Property(property="state", type="string", enum={"RESERVED", "SCHEDULED", "INSTALLED", "UNINSTALLED", "CANCELED"}, example="SCHEDULED")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Estado actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Estado de reserva actualizado exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="previous_state", type="string"),
     *                 @OA\Property(property="current_state", type="string"),
     *                 @OA\Property(property="reservation", ref="#/components/schemas/ReservationResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para cambiar el estado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación (estado inválido, mismo estado, etc.)"
     *     )
     * )
     */
public function changeState(ChangeStateRequest $request, Reservation $reservation): JsonResponse
{
    try {
        // Cargar la relación role ANTES de las autorizaciones
        $request->user()->load('role');
        
        $this->authorize('update', $reservation);
        $newState = $request->enum('state', \App\Enums\ReservationState::class);

        $this->authorize('changeState', [$reservation, $newState]);

        $oldState = $reservation->state;

        $reservation->update(['state' => $newState]);

        return response()->json([
            'message' => 'Estado de reserva actualizado exitosamente',
            'data' => [
                'previous_state' => $oldState->value,
                'current_state' => $reservation->state->value,
                'reservation' => new ReservationResource($reservation->load(['user', 'user.role'])),
            ],
        ], Response::HTTP_OK);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        return response()->json([
            'message' => $e->getMessage(),
        ], Response::HTTP_FORBIDDEN);
    } catch (\Throwable $e) {
        Log::error('Error al cambiar el estado de la reserva', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'reservation_id' => $reservation->id ?? null,
            'user_id' => $request->user()->id ?? null,
            'payload' => $request->all(),
        ]);

        return response()->json([
            'message' => 'Error al cambiar el estado de la reserva',
            'error' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
}
