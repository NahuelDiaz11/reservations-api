<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReservationQueryService
{

    /**
     * Construye query
     */
    public function buildQuery(Request $request, ?User $user = null): QueryBuilder
    {
        $baseQuery = Reservation::with(['user', 'user.role']);

        if ($user && !$user->canManageAllReservations()) {
            $baseQuery->where('created_by', $user->id);
        }

        $queryBuilder = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                // filtro de texto (busqueda por nombre, direccion, usuario)
                AllowedFilter::callback('q', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                            ->orWhere('address', 'like', "%{$value}%")
                            ->orWhereHas('user', function ($userQuery) use ($value) {
                                $userQuery->where('name', 'like', "%{$value}%")
                                    ->orWhere('email', 'like', "%{$value}%");
                            });
                    });
                }),

                // filtro por estado
                AllowedFilter::exact('state', 'state'),

                // filtro por usuario
                AllowedFilter::callback('created_by', function ($query, $value) {
                    if (is_numeric($value)) {
                        $query->where('created_by', $value);
                    } else {
                        $query->whereHas('user', function ($userQuery) use ($value) {
                            $userQuery->where('name', 'like', "%{$value}%")
                                ->orWhere('email', 'like', "%{$value}%");
                        });
                    }
                }),

                // filtros geográficos (radio geográfico)
                AllowedFilter::exact('lat', 'lat'),
                AllowedFilter::exact('lng', 'lng'),
                AllowedFilter::callback('radius_km', function ($query, $value) {
                 
                }),
            ])
            ->allowedSorts([
                'id',
                'name',
                'state',
                'created_at',
                'updated_at'
            ])
            ->defaultSort('-created_at');

        // aplica filtro geografico si corresponde
        $this->applyGeographicFilterIfNeeded($request, $queryBuilder);

        return $queryBuilder;
    }

    /**
     * Aplica filtro geografico por proximidad si estan presentes lat y lng
     */
    private function applyGeographicFilterIfNeeded(Request $request, QueryBuilder $queryBuilder): void
    {
        $lat = $request->input('filter.lat');
        $lng = $request->input('filter.lng');

        if ($lat === null || $lng === null) {
            return;
        }

        $radius = $request->input('filter.radius_km', 10);
        $eloquentBuilder = $queryBuilder->getEloquentBuilder();

        $wheres = $eloquentBuilder->getQuery()->wheres;
        $eloquentBuilder->getQuery()->wheres = array_filter($wheres, function ($where) {
            return !isset($where['column']) ||
                ($where['column'] !== 'lat' && $where['column'] !== 'lng');
        });

        $this->applyGeoFilter($eloquentBuilder, [
            'lat' => $lat,
            'lng' => $lng,
            'radius_km' => $radius
        ]);
    }

    /**
     * Aplica filtro geografico usando Haversine
     */
    private function applyGeoFilter($query, array $geoParams): void
    {
        $lat = $geoParams['lat'] ?? null;
        $lng = $geoParams['lng'] ?? null;
        $radius = $geoParams['radius_km'] ?? 10;

        if (!$lat || !$lng) {
            return;
        }

        if (
            !is_numeric($lat) || $lat < -90 || $lat > 90 ||
            !is_numeric($lng) || $lng < -180 || $lng > 180
        ) {
            return;
        }

        $haversine = $this->getHaversineFormula($lat, $lng);

        $query->selectRaw("reservations.*, {$haversine} AS distance")
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
    }


    /**
     * Formula Haversine para distancia en kilómetros
     */
    private function getHaversineFormula(float $lat, float $lng): string
    {
        $earthRadius = 6371;

        return "
            ({$earthRadius} * acos(
                cos(radians({$lat})) * 
                cos(radians(lat)) * 
                cos(radians(lng) - radians({$lng})) + 
                sin(radians({$lat})) * 
                sin(radians(lat))
            ))
        ";
    }

    /**
     * Obtiene resultados paginados
     */
    public function paginateResults(QueryBuilder $query, Request $request): LengthAwarePaginator
    {
        $page = $request->input('page', 1);
        $size = min($request->input('size', 15), 100); // Máximo 100 por página

        return $query->paginate($size, ['*'], 'page', $page)
            ->appends($request->query());
    }

    /**
     * Obtiene metadata de los filtros aplicados
     */
    public function getFiltersMetadata(Request $request, LengthAwarePaginator $paginator): array
    {
        $appliedFilters = collect($request->only([
            'q',
            'state',
            'created_by',
            'created_after',
            'created_before'
        ]))->filter()->toArray();

        if ($request->has('near')) {
            $appliedFilters['near'] = $request->input('near');
        }

        return [
            'filters' => [
                'applied' => $appliedFilters,
                'available' => [
                    'q' => 'Texto en nombre, dirección o usuario',
                    'state' => 'Estado exacto (RESERVED, SCHEDULED, etc)',
                    'created_by' => 'ID o nombre/email del usuario creador',
                    'created_after' => 'Fecha de creación desde (YYYY-MM-DD)',
                    'created_before' => 'Fecha de creación hasta (YYYY-MM-DD)',
                    'near' => [
                        'lat' => 'Latitud del centro',
                        'lng' => 'Longitud del centro',
                        'radius_km' => 'Radio en kilómetros (default: 10)'
                    ],
                ],
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'sorting' => [
                'allowed' => ['id', 'name', 'state', 'created_at', 'updated_at'],
                'default' => '-created_at',
                'current' => $request->input('sort', '-created_at'),
            ],
        ];
    }

    /**
     * Busca reservas cercanas con Spatie
     */
    public function findNearbyReservations(float $lat, float $lng, float $radius = 10, ?int $limit = 10): array
    {
        $haversine = $this->getHaversineFormula($lat, $lng);

        $results = QueryBuilder::for(Reservation::class)
            ->selectRaw("*, {$haversine} AS distance")
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->with('user')
            ->limit($limit)
            ->get();

        return [
            'center' => ['lat' => $lat, 'lng' => $lng],
            'radius_km' => $radius,
            'results' => $results->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'name' => $reservation->name,
                    'address' => $reservation->address,
                    'coordinates' => [
                        'lat' => (float) $reservation->lat,
                        'lng' => (float) $reservation->lng,
                    ],
                    'distance_km' => round($reservation->distance, 2),
                    'state' => $reservation->state->value,
                    'created_by' => $reservation->user->name,
                ];
            })->toArray(),
            'total_found' => $results->count(),
        ];
    }

    /**
     * Obtiene estadisticas para los filtros disponibles
     */
    public function getFilterStats(?User $user = null): array
    {
        $baseQuery = Reservation::query();

        if ($user && !$user->canManageAllReservations()) {
            $baseQuery->where('created_by', $user->id);
        }

        return [
            'total_reservations' => $baseQuery->count(),
            'states' => $baseQuery->selectRaw('state, count(*) as count')
                ->groupBy('state')
                ->pluck('count', 'state')
                ->toArray(),
            'top_users' => $baseQuery->join('users', 'reservations.created_by', '=', 'users.id')
                ->selectRaw('users.name, users.id, count(*) as count')
                ->groupBy('users.id', 'users.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }
}
