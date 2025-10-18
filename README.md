# Reservations API

API RESTful para gestión de reservas con sistema de roles y autenticación mediante Laravel Passport.

## 📋 Tabla de Contenidos

- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Usuarios de Prueba](#usuarios-de-prueba)
- [Uso](#uso)
- [Documentación API](#documentación-api)
- [Testing](#testing)
- [Roles y Permisos](#roles-y-permisos)
- [Estados de Reserva](#estados-de-reserva)

## ✨ Características

- 🔐 Autenticación con Laravel Passport (OAuth2)
- 👥 Sistema de roles (Admin, Coordinator, Technician, Seller, User)
- 📦 Gestión completa de reservas (CRUD)
- 🔄 Máquina de estados para reservas
- 🗺️ Filtros geográficos por coordenadas y radio
- 🔍 Búsqueda y filtrado avanzado
- 📄 Paginación de resultados
- 📚 Documentación Swagger/OpenAPI interactiva
- ✅ Tests automatizados con PHPUnit
- 🛡️ Políticas de autorización (Policies)

## 🔧 Requisitos

- PHP >= 8.2
- Composer
- MySQL >= 8.0 o PostgreSQL >= 13
- Laravel 11.x

## 📥 Instalación

1. **Clonar el repositorio**

```bash
git clone <repository-url>
cd reservations-api
```

2. **Instalar dependencias**

```bash
composer install
```

3. **Configurar variables de entorno**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos en `.env`**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reservations_db
DB_USERNAME=root
DB_PASSWORD=
```

5. **Ejecutar migraciones y seeders**

```bash
php artisan migrate --seed
```

6. **Instalar Laravel Passport**

```bash
php artisan passport:install
```

7. **Generar documentación Swagger**

```bash
php artisan l5-swagger:generate
```

## ⚙️ Configuración

### Roles del Sistema

El sistema incluye 5 roles predefinidos:

| ID | Rol | Descripción |
|----|-----|-------------|
| 1 | Admin | Acceso total al sistema |
| 2 | Coordinator | Gestiona reservas y puede cambiar a estado SCHEDULED |
| 3 | Technician | Puede cambiar estados a INSTALLED/UNINSTALLED |
| 4 | Seller | Crea reservas y puede cancelar las propias |
| 5 | User | Usuario básico |

## 👤 Usuarios de Prueba

El sistema incluye usuarios precargados para testing. **Todos los usuarios tienen la misma contraseña: `123456`**

| Rol | Nombre | Email | Contraseña |
|-----|--------|-------|------------|
| **Admin** | Administrator | `admin@cornerstone.com` | `123456` |
| **Coordinator** | Project Coordinator | `coordinator@cornerstone.com` | `123456` |
| **Technician** | Field Technician | `technician@cornerstone.com` | `123456` |
| **Seller** | Sales Agent | `seller@cornerstone.com` | `123456` |
| **User** | Regular User | `user@cornerstone.com` | `123456` |

## 🚀 Uso

### Iniciar servidor de desarrollo

```bash
php artisan serve
```

La API estará disponible en: `http://localhost:8000`

## 📚 Documentación API

### 🎯 Acceder a Swagger UI (Recomendado)

Una vez iniciado el servidor, visita:

```
http://localhost:8000/api/documentation
```

### 🔐 Cómo Iniciar Sesión

1. Ve a la documentación Swagger: `http://localhost:8000/api/documentation`
2. Busca el endpoint `POST /api/login`
3. Usa cualquiera de los usuarios de la tabla anterior
4. Copia el `access_token` de la respuesta
5. Haz clic en el botón **"Authorize"** 🔒 en la parte superior de Swagger
6. Ingresa: `Bearer {tu_access_token}`
7. ¡Listo! Ya puedes probar todos los endpoints autenticados

**Ejemplo de Login:**
```json
{
  "email": "admin@cornerstone.com",
  "password": "123456"
}
```
### Endpoints Principales

#### Autenticación

```
POST /api/register          # Registrar nuevo usuario
POST /api/login             # Iniciar sesión (obtener token)
POST /api/logout            # Cerrar sesión (revocar token)
GET  /api/user              # Obtener datos del usuario autenticado
```

#### Reservas

```
GET    /api/reservations              # Listar reservas (con filtros)
POST   /api/reservations              # Crear nueva reserva
GET    /api/reservations/{id}         # Ver reserva específica
PUT    /api/reservations/{id}         # Actualizar datos de reserva
PATCH  /api/reservations/{id}/state   # Cambiar estado de reserva
```

---

## 📖 Guía Rápida con Swagger

### 1️⃣ Autenticación

#### Paso 1: Iniciar Sesión

1. En Swagger, ve a **Authentication** → `POST /api/login`
2. Haz clic en **"Try it out"**
3. Ingresa las credenciales:

```json
{
  "email": "admin@cornerstone.com",
  "password": "123456"
}
```

4. Haz clic en **"Execute"**
5. Copia el `access_token` de la respuesta

**Respuesta exitosa:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_at": "2025-11-01 12:00:00"
}
```

#### Paso 2: Autorizar en Swagger

1. Haz clic en el botón **"Authorize"** 🔒 (arriba a la derecha)
2. Ingresa en el campo: `Bearer {tu_access_token}`
3. Haz clic en **"Authorize"** y luego **"Close"**
4. ¡Ahora todos los endpoints protegidos están disponibles!

---

### 2️⃣ Crear una Reserva

1. Ve a **Reservations** → `POST /api/reservations`
2. Haz clic en **"Try it out"**
3. Completa el JSON:

```json
{
  "name": "Reserva Hotel Playa",
  "address": "Av. Costanera 123, Mar del Plata",
  "lat": -38.0055,
  "lng": -57.5426
}
```

4. Haz clic en **"Execute"**

**Respuesta (201 Created):**
```json
{
  "message": "Reserva creada exitosamente",
  "data": {
    "id": 1,
    "name": "Reserva Hotel Playa",
    "address": "Av. Costanera 123, Mar del Plata",
    "lat": -38.0055,
    "lng": -57.5426,
    "state": "RESERVED",
    "creator": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@cornerstone.com",
      "role": "Admin"
    },
    "created_at": "2025-10-17T15:30:00.000000Z",
    "updated_at": "2025-10-17T15:30:00.000000Z"
  }
}
```

---

### 3️⃣ Listar Reservas con Filtros

#### Listar Todas las Reservas

1. Ve a `GET /api/reservations`
2. Haz clic en **"Try it out"**
3. Haz clic en **"Execute"**

#### Filtrar por Estado

1. Ve a `GET /api/reservations`
2. Haz clic en **"Try it out"**
3. En el campo `filter[state]` ingresa: `SCHEDULED`
4. Haz clic en **"Execute"**

#### Búsqueda por Texto

1. En el campo `filter[q]` ingresa: `playa`
2. Haz clic en **"Execute"**

#### Filtro Geográfico

1. Completa los campos:
   - `filter[lat]`: `-34.603722`
   - `filter[lng]`: `-58.381592`
   - `filter[radius_km]`: `10`
2. Haz clic en **"Execute"**

---

### 4️⃣ Cambiar Estado de una Reserva

**Importante:** Asegúrate de estar autenticado con un usuario que tenga los permisos necesarios.

#### Ejemplo: RESERVED → SCHEDULED (requiere Coordinator o Admin)

1. Inicia sesión con `coordinator@cornerstone.com`
2. Ve a `PATCH /api/reservations/{id}/state`
3. Haz clic en **"Try it out"**
4. Ingresa el ID de la reserva
5. Completa el body:

```json
{
  "state": "SCHEDULED"
}
```

6. Haz clic en **"Execute"**

**Respuesta (200 OK):**
```json
{
  "message": "Estado de reserva actualizado exitosamente",
  "data": {
    "previous_state": "RESERVED",
    "current_state": "SCHEDULED",
    "reservation": {
      "id": 1,
      "name": "Reserva Hotel Playa",
      "state": "SCHEDULED",
      "creator": {
        "id": 2,
        "name": "Project Coordinator",
        "email": "coordinator@cornerstone.com",
        "role": "Coordinator"
      }
    }
  }
}
```

---

### 5️⃣ Actualizar Datos de una Reserva

1. Ve a `PUT /api/reservations/{id}`
2. Haz clic en **"Try it out"**
3. Ingresa el ID de la reserva
4. Actualiza los campos:

```json
{
  "name": "Reserva Hotel Playa Actualizada",
  "address": "Av. Costanera 456, Mar del Plata",
  "lat": -38.0060,
  "lng": -57.5430
}
```

5. Haz clic en **"Execute"**

---

## ⚠️ Respuestas de Error Comunes

### 401 - No Autenticado

```json
{
  "message": "Unauthenticated."
}
```

**Solución:** 
1. Verifica que hayas hecho clic en **"Authorize"** en Swagger
2. Asegúrate de haber incluido `Bearer ` antes del token
3. Verifica que el token no haya expirado

---

### 403 - No Autorizado

```json
{
  "message": "Su rol Seller no tiene permisos para cambiar al estado INSTALLED"
}
```

**Solución:** 
1. Verifica en la [Matriz de Permisos](#matriz-de-permisos) qué roles pueden realizar la acción
2. Cierra sesión y autentícate con un usuario que tenga los permisos necesarios

---

### 422 - Error de Validación

```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "lat": ["The lat field is required."],
    "lng": ["The lng field is required."]
  }
}
```

**Solución:** Verifica que todos los campos requeridos estén presentes y sean válidos.

---

### 422 - Transición de Estado Inválida

```json
{
  "message": "Transición de estado no válida: RESERVED → INSTALLED"
}
```

**Solución:** Consulta el [Diagrama de Transiciones](#diagrama-de-transiciones) para ver las transiciones permitidas.

---

### 422 - Estado Final

```json
{
  "message": "No se puede modificar una reserva en estado final: CANCELED"
}
```

**Solución:** Las reservas en estado `CANCELED` o `UNINSTALLED` no pueden modificarse.

---

## 🧪 Testing

### Ejecutar todos los tests

```bash
php artisan test
```

### Ejecutar tests específicos

```bash
# Tests de reservas
php artisan test --filter ReservationApiTest

# Test específico
php artisan test --filter test_coordinator_puede_cambiar_estado_a_scheduled
```

### Tests con Coverage

```bash
php artisan test --coverage
```

### Cobertura de Tests

Los tests incluyen:

- ✅ CRUD de reservas
- ✅ Autorización por roles
- ✅ Cambios de estado válidos e inválidos
- ✅ Filtros y búsquedas
- ✅ Validaciones de datos
- ✅ Transiciones de estados
- ✅ Permisos por rol

---

## 👥 Roles y Permisos

### Matriz de Permisos

| Acción | Admin | Coordinator | Technician | Seller | User |
|--------|:-----:|:-----------:|:----------:|:------:|:----:|
| Ver todas las reservas | ✅ | ✅ | ❌ | ❌ | ❌ |
| Ver propias reservas | ✅ | ✅ | ✅ | ✅ | ✅ |
| Crear reserva | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar cualquier reserva | ✅ | ✅ | ❌ | ❌ | ❌ |
| Editar propia reserva | ✅ | ✅ | ✅ | ✅ | ✅ |
| RESERVED → SCHEDULED | ✅ | ✅ | ❌ | ❌ | ❌ |
| SCHEDULED → INSTALLED | ✅ | ✅ | ✅ | ❌ | ❌ |
| INSTALLED → UNINSTALLED | ✅ | ❌ | ✅ | ❌ | ❌ |
| * → CANCELED | ✅ | ✅ | ❌ | ✅* | ❌ |

**\*** Seller solo puede cancelar sus propias reservas.

---

## 🔄 Estados de Reserva

### Diagrama de Transiciones

```
RESERVED ──┬──> SCHEDULED ──> INSTALLED ──> UNINSTALLED (final)
           │                       
           └──> CANCELED (final)
```

### Reglas de Transición

#### ✅ Transiciones Permitidas

1. **RESERVED → SCHEDULED**
   - **Requiere:** Admin o Coordinator
   - **Descripción:** Programar fecha de instalación

2. **RESERVED → CANCELED**
   - **Requiere:** Admin, Coordinator o Seller (solo propias)
   - **Descripción:** Cancelar reserva antes de programar

3. **SCHEDULED → INSTALLED**
   - **Requiere:** Admin, Coordinator o Technician
   - **Descripción:** Confirmar instalación completada

4. **SCHEDULED → CANCELED**
   - **Requiere:** Admin, Coordinator o Seller (solo propias)
   - **Descripción:** Cancelar reserva ya programada

5. **INSTALLED → UNINSTALLED**
   - **Requiere:** Admin o Technician
   - **Descripción:** Registrar desinstalación del servicio

#### ❌ Estados Finales

- **CANCELED**: No permite ninguna transición ni edición de datos
- **UNINSTALLED**: No permite ninguna transición ni edición de datos

---

## 📝 Estructura del Proyecto

```
app/
├── Enums/
│   └── ReservationState.php          # Enum de estados de reserva
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php    # Autenticación
│   │       └── ReservationController.php # CRUD de reservas
│   ├── Requests/
│   │   ├── ChangeStateRequest.php    # Validación cambio de estado
│   │   ├── StoreReservationRequest.php # Validación creación
│   │   └── UpdateReservationRequest.php # Validación actualización
│   └── Resources/
│       └── ReservationResource.php   # Transformador de datos
├── Models/
│   ├── Reservation.php               # Modelo de reserva
│   ├── Role.php                      # Modelo de rol
│   └── User.php                      # Modelo de usuario
├── Policies/
│   └── ReservationPolicy.php         # Políticas de autorización
└── Services/
    └── ReservationQueryService.php   # Servicio de consultas y filtros
```


## 📊 Parámetros de Filtrado

### Filtros Disponibles

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `filter[q]` | string | Búsqueda por nombre, dirección o usuario | `playa` |
| `filter[state]` | string | Filtrar por estado específico | `SCHEDULED` |
| `filter[created_by]` | integer | Filtrar por ID de usuario creador | `5` |
| `filter[lat]` | float | Latitud para filtro geográfico* | `-34.603722` |
| `filter[lng]` | float | Longitud para filtro geográfico* | `-58.381592` |
| `filter[radius_km]` | float | Radio en kilómetros* | `10` |
| `page` | integer | Número de página | `2` |
| `per_page` | integer | Elementos por página (max: 100) | `25` |
| `sort` | string | Ordenar por campo** | `-created_at` |

**\* Filtro geográfico:** Requiere `lat`, `lng` y `radius_km` juntos.  
**\*\* Ordenamiento:** Prefijar con `-` para orden descendente.

### Ejemplos de Uso en Swagger

Todos estos filtros están disponibles directamente en Swagger UI:

1. **Buscar "hotel" con estado SCHEDULED:**
   - `filter[q]`: `hotel`
   - `filter[state]`: `SCHEDULED`
   - `sort`: `-created_at`

2. **Filtro geográfico (10km de radio):**
   - `filter[lat]`: `-34.603722`
   - `filter[lng]`: `-58.381592`
   - `filter[radius_km]`: `10`

3. **Paginación personalizada:**
   - `page`: `2`
   - `per_page`: `20`
