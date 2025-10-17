<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     title="Reservations Management API",
 *     version="1.0.0",
 *     description="Sistema de gestión de reservas - Prueba Técnica",
 *     @OA\Contact(
 *         name="Equipo de Desarrollo",
 *         email="desarrollo@cornerstone.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints de autenticación"
 * )
 *
 * @OA\Tag(
 *     name="Reservations", 
 *     description="Operaciones relacionadas con reservas"
 * )
 *
 * @OA\Schema(
 *     schema="PermissionsByRole",
 *     title="Permisos por Rol",
 *     description="Descripción de Permisos por Rol de Usuario

Resumen de Capacidades:

- Admin: Acceso completo - Gestión global, programar, instalar, desinstalar, cancelar
- Coordinator: Gestión operativa - Gestión global, programar, instalar, cancelar (no puede desinstalar)
- Technician: Ejecución técnica - Instalar, desinstalar (no puede gestionar global, programar ni cancelar)
- Seller: Gestión comercial - Cancelar (solo puede gestionar sus propias reservas y cancelarlas)",
 *     @OA\Property(
 *         property="admin",
 *         type="object",
 *         description="Administrador - Acceso completo",
 *         @OA\Property(property="can_manage_all_reservations", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_scheduled", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_installed", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_uninstalled", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_canceled", type="boolean", example=true)
 *     ),
 *     @OA\Property(
 *         property="coordinator",
 *         type="object",
 *         description="Coordinador - Gestión y programación",
 *         @OA\Property(property="can_manage_all_reservations", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_scheduled", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_installed", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_uninstalled", type="boolean", example=false),
 *         @OA\Property(property="can_change_to_canceled", type="boolean", example=true)
 *     ),
 *     @OA\Property(
 *         property="technician",
 *         type="object", 
 *         description="Técnico - Instalación/desinstalación",
 *         @OA\Property(property="can_manage_all_reservations", type="boolean", example=false),
 *         @OA\Property(property="can_change_to_scheduled", type="boolean", example=false),
 *         @OA\Property(property="can_change_to_installed", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_uninstalled", type="boolean", example=true),
 *         @OA\Property(property="can_change_to_canceled", type="boolean", example=false)
 *     ),
 *     @OA\Property(
 *         property="seller", 
 *         type="object",
 *         description="Vendedor - Creación y cancelación",
 *         @OA\Property(property="can_manage_all_reservations", type="boolean", example=false),
 *         @OA\Property(property="can_change_to_scheduled", type="boolean", example=false),
 *         @OA\Property(property="can_change_to_installed", type="boolean", example=false),
 *         @OA\Property(property="can_change_to_uninstalled", type="boolean", example=false),
 *         @OA\Property(property="can_change_to_canceled", type="boolean", example=true)
 *     )
 * )
 */
class SwaggerDocs
{
    // Clase para documentación Swagger centralizada
}