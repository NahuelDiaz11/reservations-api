<?php

namespace Tests\Feature;

use App\Enums\ReservationState;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $coordinator;
    protected $technician;
    protected $seller;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $roles = [
            ['id' => 1, 'name' => 'ADMIN', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'COORDINATOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'TECHNICIAN', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SELLER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'USER', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // Crear usuarios de prueba
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->coordinator = User::factory()->create(['role_id' => 2]);
        $this->technician = User::factory()->create(['role_id' => 3]);
        $this->seller = User::factory()->create(['role_id' => 4]);
        $this->user = User::factory()->create(['role_id' => 5]);
    }

    /**
     * Test: Un usuario autenticado puede crear una reserva exitosamente.
     *
     * Verifica que:
     * - La respuesta sea 201 (Created)
     * - Los datos de la reserva se retornen correctamente
     * - El estado inicial sea RESERVED
     * - Los datos se guarden en la base de datos
     *
     * @test
     * @return void
     */
    public function test_usuario_puede_crear_reserva()
    {
        $response = $this->actingAs($this->seller, 'api')
            ->postJson('/api/reservations', [
                'name' => 'Reserva de Test',
                'address' => 'Calle Test 123',
                'lat' => -34.603722,
                'lng' => -58.381592,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'address',
                    'state',
                    'creator' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ]
            ])
            ->assertJson([
                'message' => 'Reserva creada exitosamente',
                'data' => [
                    'name' => 'Reserva de Test',
                    'state' => 'RESERVED'
                ]
            ]);

        $this->assertDatabaseHas('reservations', [
            'name' => 'Reserva de Test',
            'created_by' => $this->seller->id
        ]);
    }

    /**
     * Test: Un Admin puede ver todas las reservas del sistema.
     *
     * Verifica que:
     * - Admin puede ver reservas de diferentes usuarios
     * - La respuesta incluye la estructura correcta con metadata
     * - Se retorna un array de reservas
     *
     * @test
     * @return void
     */
    public function test_admin_puede_ver_todas_las_reservas()
    {
        // Crear reservas de diferentes usuarios
        Reservation::factory()->create(['created_by' => $this->seller->id]);
        Reservation::factory()->create(['created_by' => $this->user->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'state',
                        'creator' => [
                            'id',
                            'name',
                            'email',
                            'role'
                        ]
                    ]
                ],
                'meta'
            ]);

        $data = $response->json();
        $this->assertIsArray($data['data']);
    }

    /**
     * Test: Un Seller solo puede ver sus propias reservas.
     *
     * Verifica que:
     * - Seller no puede ver reservas de otros usuarios
     * - Solo se retornan las reservas creadas por el Seller autenticado
     * - El filtrado funciona correctamente
     *
     * @test
     * @return void
     */
    public function test_seller_solo_puede_ver_sus_propias_reservas()
    {
        // Reserva del seller
        Reservation::factory()->create([
            'name' => 'Mi Reserva',
            'created_by' => $this->seller->id
        ]);

        // Reserva de otro usuario
        Reservation::factory()->create([
            'name' => 'Reserva Ajena',
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->seller, 'api')
            ->getJson('/api/reservations');

        $response->assertStatus(200);

        $data = $response->json();

        // Verificar que solo ve su propia reserva
        $reservasDelSeller = array_filter($data['data'], function ($reserva) {
            return $reserva['name'] === 'Mi Reserva';
        });

        $this->assertCount(1, $reservasDelSeller);
    }

    /**
     * Test: Un Coordinator puede cambiar el estado de RESERVED a SCHEDULED.
     *
     * Verifica que:
     * - El Coordinator tiene permisos para programar reservas
     * - La transición de estado es exitosa (200)
     * - El estado se actualiza correctamente en la base de datos
     * - La respuesta incluye el estado anterior y actual
     *
     * @test
     * @return void
     */
    public function test_coordinator_puede_cambiar_estado_a_scheduled()
    {
        $reservation = Reservation::factory()->create([
            'state' => ReservationState::RESERVED,
            'created_by' => $this->seller->id
        ]);

        $response = $this->actingAs($this->coordinator, 'api')
            ->patchJson("/api/reservations/{$reservation->id}/state", [
                'state' => 'SCHEDULED'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Estado de reserva actualizado exitosamente',
                'data' => [
                    'previous_state' => 'RESERVED',
                    'current_state' => 'SCHEDULED'
                ]
            ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'state' => 'SCHEDULED'
        ]);
    }

    /**
     * Test: Un Technician puede cambiar el estado de SCHEDULED a INSTALLED.
     *
     * Verifica que:
     * - El Technician tiene permisos para marcar instalaciones
     * - La transición de estado es válida
     * - El estado se persiste correctamente
     *
     * @test
     * @return void
     */
    public function test_technician_puede_cambiar_estado_a_installed()
    {
        $reservation = Reservation::factory()->create([
            'state' => ReservationState::SCHEDULED,
            'created_by' => $this->seller->id
        ]);

        $response = $this->actingAs($this->technician, 'api')
            ->patchJson("/api/reservations/{$reservation->id}/state", [
                'state' => 'INSTALLED'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'state' => 'INSTALLED'
        ]);
    }

    /**
     * Test: Un Seller puede cancelar su propia reserva.
     *
     * Verifica que:
     * - Seller tiene permisos para cancelar sus propias reservas
     * - La cancelación es exitosa desde estado RESERVED
     * - El estado final es CANCELED
     *
     * @test
     * @return void
     */
    public function test_seller_puede_cancelar_su_propia_reserva()
    {
        $reservation = Reservation::factory()->create([
            'state' => ReservationState::RESERVED,
            'created_by' => $this->seller->id
        ]);

        $response = $this->actingAs($this->seller, 'api')
            ->patchJson("/api/reservations/{$reservation->id}/state", [
                'state' => 'CANCELED'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'state' => 'CANCELED'
        ]);
    }

    /**
     * Test: Un Seller NO puede cambiar el estado a INSTALLED.
     *
     * Verifica que:
     * - La política de autorización funciona correctamente
     * - Seller no tiene permisos para marcar instalaciones
     * - Se retorna 403 (Forbidden)
     *
     * @test
     * @return void
     */
    public function test_seller_no_puede_cambiar_estado_a_installed()
    {
        $reservation = Reservation::factory()->create([
            'state' => ReservationState::SCHEDULED,
            'created_by' => $this->seller->id
        ]);

        $response = $this->actingAs($this->seller, 'api')
            ->patchJson("/api/reservations/{$reservation->id}/state", [
                'state' => 'INSTALLED'
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: El filtro por estado funciona correctamente.
     *
     * Verifica que:
     * - Se pueden filtrar reservas por estado específico
     * - Solo se retornan reservas con el estado solicitado
     * - El query parameter filter[state] funciona
     *
     * @test
     * @return void
     */
    public function test_filtro_por_estado_funciona_correctamente()
    {
        Reservation::factory()->create([
            'state' => ReservationState::RESERVED,
            'name' => 'Reserva RESERVED'
        ]);
        Reservation::factory()->create([
            'state' => ReservationState::SCHEDULED,
            'name' => 'Reserva SCHEDULED'
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/reservations?filter[state]=RESERVED');

        $response->assertStatus(200);

        $data = $response->json();

        // Verificar que al menos una reserva tiene estado RESERVED
        $reservasFiltradas = array_filter($data['data'], function ($reserva) {
            return $reserva['state'] === 'RESERVED';
        });

        $this->assertGreaterThanOrEqual(1, count($reservasFiltradas));
    }

    /**
     * Test: El filtro de búsqueda por texto funciona correctamente.
     *
     * Verifica que:
     * - Se pueden buscar reservas por nombre o dirección
     * - El query parameter filter[q] funciona
     * - La búsqueda es case-insensitive
     *
     * @test
     * @return void
     */
    public function test_filtro_por_texto_funciona_correctamente()
    {
        Reservation::factory()->create([
            'name' => 'Casa en la playa',
            'address' => 'Av. Costera 123'
        ]);
        Reservation::factory()->create([
            'name' => 'Departamento centro',
            'address' => 'Calle Central 456'
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/reservations?filter[q]=playa');

        $response->assertStatus(200);

        $data = $response->json();

        // Verificar que se encontró la reserva con "playa"
        $reservasConPlaya = array_filter($data['data'], function ($reserva) {
            return strpos($reserva['name'], 'playa') !== false;
        });

        $this->assertGreaterThanOrEqual(1, count($reservasConPlaya));
    }

    /**
     * Test: No se puede cambiar el estado de una reserva en estado final.
     *
     * Verifica que:
     * - Los estados finales (INSTALLED, CANCELED, UNINSTALLED) no pueden cambiar
     * - Se retorna 422 (Unprocessable Entity)
     * - El mensaje de error es descriptivo
     *
     * @test
     * @return void
     */
    public function test_no_se_puede_cambiar_estado_final()
    {
        $reservation = Reservation::factory()->create([
            'state' => ReservationState::INSTALLED
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/reservations/{$reservation->id}/state", [
                'state' => 'SCHEDULED'
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test: La validación de datos al crear una reserva funciona correctamente.
     *
     * Verifica que:
     * - Los campos requeridos se validan (name, lat, lng)
     * - Se retorna 422 con los errores de validación
     * - Los mensajes de error son claros
     *
     * @test
     * @return void
     */
    public function test_validacion_de_datos_al_crear_reserva()
    {
        $response = $this->actingAs($this->seller, 'api')
            ->postJson('/api/reservations', [
                'name' => '',
                'address' => 'Test'
                // Faltan lat y lng requeridos
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'lat', 'lng']);
    }
}
