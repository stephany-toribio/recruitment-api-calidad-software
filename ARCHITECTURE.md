# 🏗️ Portal de Empleos API — Guía de Arquitectura y Estándares del Equipo

> **Propósito de este documento:**
> Esta guía es de lectura **obligatoria** para cualquier desarrollador backend antes de tocar una sola línea de código. Explica la estructura del proyecto, las decisiones de arquitectura tomadas, las convenciones acordadas por el equipo y las razones detrás de cada decisión.
>
> El proyecto es una **API REST en Laravel 12** que será consumida por:
> - Un frontend Angular (en dominio/puerto diferente).
> - Un microservicio de Python (para matching con IA).
>
> Por ello, la arquitectura está pensada para ser **estable, versionada y con contratos JSON predecibles**.

---

## 📁 Mapa General del Proyecto

```
portal-empleos-api/
│
├── app/
│   ├── Exceptions/              # Excepciones de reglas de negocio (no usar try/catch genéricos)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/          # ← Todos los controladores van aquí
│   │   │           ├── Admin/   # Controladores exclusivos para el rol administrador
│   │   │           ├── BaseApiController.php   # ← Clase base (LEER PRIMERO)
│   │   │           ├── AuthController.php
│   │   │           ├── CandidatoController.php
│   │   │           ├── EmpresaController.php
│   │   │           ├── VacanteController.php
│   │   │           ├── PostulacionController.php
│   │   │           └── MatchController.php
│   │   │
│   │   ├── Requests/            # Validaciones (FormRequests), organizadas por módulo
│   │   │   ├── Auth/
│   │   │   ├── Candidatos/
│   │   │   ├── Vacantes/
│   │   │   └── Postulaciones/
│   │   │
│   │   └── Resources/           # Transformadores de JSON (API Resources)
│   │       └── Api/
│   │           └── V1/
│   │               ├── UserResource.php
│   │               ├── CandidatoResource.php
│   │               ├── EmpresaResource.php
│   │               ├── VacanteResource.php
│   │               ├── PostulacionResource.php
│   │               └── HabilidadResource.php
│   │
│   ├── Models/                  # Modelos Eloquent
│   └── Providers/               # Service Providers (AppServiceProvider, etc.)
│
├── bootstrap/
│   └── app.php                  # ← Configuración global de excepciones y middlewares
│
├── config/
│   └── scramble.php             # Configuración de la documentación automática
│
├── docker/
│   └── php/
│       └── Dockerfile           # Imagen PHP 8.3 con extensiones del proyecto
│
├── postman/
│   ├── environment.local.json   # Environment de Postman listo para importar
│   └── README.md                # Instrucciones de configuración de Postman
│
├── routes/
│   ├── api.php                  # Punto de entrada — solo define el prefijo v1
│   └── api_v1/                  # ← Un archivo de rutas por módulo
│       ├── auth.php
│       ├── candidatos.php
│       ├── empresas.php
│       ├── vacantes.php
│       ├── postulaciones.php
│       ├── match.php
│       └── admin.php
│
├── .env                         # Variables de entorno locales (NO se sube a Git)
├── docker-compose.yml           # Definición de todos los servicios Docker
├── SETUP.md                     # Guía de instalación paso a paso
└── ARCHITECTURE.md              # Este documento
```

---

## 🐳 Infraestructura Docker

El proyecto corre completamente dentro de Docker. **No necesitas PHP ni MySQL instalados en tu máquina.** El archivo `docker-compose.yml` define 4 servicios:

| Servicio | Imagen | Puerto | Propósito |
| :------- | :----- | :----- | :-------- |
| **`app`** | PHP 8.3 CLI personalizada | `8000` | Servidor Laravel (`php artisan serve`) |
| **`mysql`** | MySQL 8.0 | `3306` | Base de datos principal |
| **`redis`** | Redis Alpine | `6379` | Caché y driver de colas |
| **`mailpit`** | axllent/mailpit | `8025` (UI), `1025` (SMTP) | Capturador de correos para desarrollo |

### ¿Cómo interactúan los servicios?
El contenedor `app` se conecta a `mysql`, `redis` y `mailpit` usando sus **nombres de servicio como hostname** (no `localhost`), porque dentro de la red interna de Docker, cada contenedor se identifica por su nombre. Eso es lo que configuran estas líneas del `.env`:
```env
DB_HOST=mysql        # ← No es 127.0.0.1, es el nombre del servicio
REDIS_HOST=redis
MAIL_HOST=mailpit
```

### Bind Mount: tu código es el código del contenedor
La línea más importante de `docker-compose.yml`:
```yaml
volumes:
  - .:/var/www/html
```
Esta instrucción enlaza tu carpeta local con la carpeta del contenedor. Cualquier archivo que guardes en tu editor **se refleja inmediatamente en el contenedor**, sin necesidad de reconstruir la imagen. Por eso, para ejecutar comandos de Artisan, Composer o cualquier cosa que requiera PHP, debes hacerlo dentro del contenedor:
```powershell
# Forma correcta ✅
docker compose exec app php artisan make:model Vacante

# Forma incorrecta ❌ (PHP no está instalado en Windows)
php artisan make:model Vacante
```

---

## 🗺️ Estándares de la API

### 1. Versionado desde el día 1

**Todos los endpoints viven bajo el prefijo `/api/v1/`.**

El archivo `routes/api.php` es el único punto de entrada y solo define el grupo de versión:
```php
Route::prefix('v1')->group(function () {
    require __DIR__.'/api_v1/auth.php';
    require __DIR__.'/api_v1/candidatos.php';
    // ...
});
```

**¿Por qué?** Angular y el microservicio de Python dependen de contratos estables. Si mañana necesitamos romper la compatibilidad (cambiar el nombre de un campo de respuesta, eliminar un endpoint), creamos `api_v2/` y los clientes migran gradualmente. Sin versión, cualquier cambio puede romper el frontend silenciosamente.

### 2. Un archivo de rutas por módulo

En lugar de un enorme `api.php` de 300 líneas, cada módulo tiene su propio archivo en `routes/api_v1/`:

```
routes/api_v1/
├── auth.php         ← Gabriel trabaja aquí (módulo autenticación)
├── candidatos.php   ← Compañero A trabaja aquí
├── vacantes.php     ← Compañero B trabaja aquí
└── ...
```

**¿Por qué?** Cuando 3 personas trabajan en paralelo, tener un solo archivo de rutas garantiza conflictos de merge en Git cada vez. Con archivos separados, cada desarrollador trabaja en su propio archivo sin pisarse.

---

## 🎮 Convenciones de Controladores

### Estructura de carpetas
Todos los controladores se crean en `app/Http/Controllers/Api/V1/`:
```
Api/V1/
├── BaseApiController.php   ← Clase base abstracta, NUNCA tocar
├── AuthController.php
├── CandidatoController.php
├── VacanteController.php
├── PostulacionController.php
├── EmpresaController.php
├── MatchController.php
└── Admin/
    └── HabilidadController.php
```

### La clase `BaseApiController` (Obligatorio extenderla)

**Todos los controladores deben extender `BaseApiController`**, nunca el `Controller` base de Laravel directamente.

```php
class VacanteController extends BaseApiController  // ✅ Correcto
class VacanteController extends Controller         // ❌ Incorrecto
```

`BaseApiController` provee dos métodos de respuesta que garantizan que **el 100% de las respuestas JSON de la API tengan siempre la misma forma**:

```php
// Para éxito (HTTP 200, 201, etc.)
return $this->successResponse($data, 'Mensaje opcional', 200);
// Resultado:
// { "success": true, "message": "Mensaje opcional", "data": {...} }

// Para errores manuales (no excepciones)
return $this->errorResponse('Mensaje de error', 400, $detallesOpcionales);
// Resultado:
// { "success": false, "message": "Mensaje de error", "errors": null }
```

### No crear "God Controllers"

Un controlador debe manejar un solo dominio. Para operaciones que no son CRUD puro (postular a una vacante, calcular compatibilidad, cambiar estado de postulación), usa **Controladores de Acción Única** con el método `__invoke`:

```php
// En lugar de sobrecargar PostulacionController con métodos de negocio:
class PostularVacanteController extends BaseApiController
{
    public function __invoke(PostularVacanteRequest $request): JsonResponse
    {
        // Lógica específica de postular
    }
}

// Y en la ruta:
Route::post('/postular', PostularVacanteController::class);
```

---

## ✅ Convenciones de Validación (FormRequests)

**Regla de oro: Nunca valides datos dentro de un controlador.**

Cada acción de escritura (POST, PUT, PATCH) debe tener su propio `FormRequest`. Se organizan en `app/Http/Requests/{Modulo}/`:

```
app/Http/Requests/
├── Auth/
│   ├── LoginRequest.php
│   └── RegisterRequest.php
├── Candidatos/
│   └── RegistrarCandidatoRequest.php
├── Vacantes/
│   └── CrearVacanteRequest.php
└── Postulaciones/
    └── PostularVacanteRequest.php
```

**¿Por qué no en el controlador?**

```php
// ❌ Nunca hacer esto — el controlador se mezcla con validación
public function store(Request $request)
{
    $data = $request->validate([
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
        // 10 reglas más...
    ]);
}

// ✅ Así se hace — el controlador queda limpio y enfocado
public function store(CrearVacanteRequest $request): JsonResponse
{
    // Si llega hasta aquí, los datos YA están validados y son confiables.
    // El controlador solo orquesta.
    $vacante = $this->vacanteService->crear($request->validated());
    return $this->successResponse(new VacanteResource($vacante), 'Vacante creada.', 201);
}
```

**Cómo crear un FormRequest:**
```powershell
docker compose exec app php artisan make:request Vacantes/CrearVacanteRequest
```

**Estructura de un FormRequest:**
```php
class CrearVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // true = el endpoint es público o la autorización se maneja con middleware/policies
        // En Sprint 1: verificar que $request->user()->esEmpresa()
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo'    => ['required', 'string', 'max:150'],
            'modalidad' => ['required', 'in:remoto,presencial,hibrido'],
        ];
    }

    public function messages(): array
    {
        // Mensajes en español para que Angular los muestre directamente al usuario
        return [
            'titulo.required'   => 'El título del puesto es obligatorio.',
            'modalidad.in'      => 'La modalidad debe ser remoto, presencial o híbrido.',
        ];
    }
}
```

---

## 📦 Convenciones de API Resources (Transformadores JSON)

**Regla de oro: Nunca devuelvas un modelo Eloquent directamente.**

```php
// ❌ Nunca hacer esto — expone todas las columnas de la BD, incluyendo passwords
return response()->json($vacante);
return response()->json($candidato);

// ✅ Siempre usar API Resources
return $this->successResponse(new VacanteResource($vacante));
return $this->successResponse(VacanteResource::collection($vacantes));
```

Los Resources están en `app/Http/Resources/Api/V1/` y tienen 3 funciones:

1. **Proteger campos sensibles:** Nunca exponer passwords, tokens internos, etc.
2. **Definir un contrato estable:** Si renombras una columna en la BD, solo actualizas el Resource. Angular y Python no notan la diferencia.
3. **Implementar reglas de negocio de privacidad (RNF-06):**

```php
// CandidatoResource.php — Ejemplo de RNF-06
public function toArray(Request $request): array
{
    $puedeVerContacto = false; // En Sprint 1: verificar con relaciones de BD

    return [
        'id'       => $this->id,
        'nombre'   => $this->nombre,
        'correo'   => $puedeVerContacto ? $this->correo : null,   // ← Privado por defecto
        'telefono' => $puedeVerContacto ? $this->telefono : null, // ← Privado por defecto
    ];
}
```

**El mismo Resource sirve para la vista pública y privada.** No se crean 2 endpoints distintos para el mismo recurso.

### Cómo cargar relaciones eficientemente (sin N+1)

```php
// En el controlador, siempre cargar las relaciones que el Resource va a usar:
$vacante = Vacante::with(['empresa', 'habilidades'])->findOrFail($id);
return $this->successResponse(new VacanteResource($vacante));

// En el Resource, usar whenLoaded para que sea opcional:
'empresa'     => new EmpresaResource($this->whenLoaded('empresa')),
'habilidades' => HabilidadResource::collection($this->whenLoaded('habilidades')),
```

---

## 🚨 Manejo Centralizado de Errores

**Regla de oro: No uses bloques `try/catch` en los controladores para errores de negocio.**

Todo el manejo de excepciones está centralizado en `bootstrap/app.php`. El mapa de errores configurado es:

| Excepción | Código HTTP | `error_code` | Cuándo ocurre |
| :-------- | :---------- | :----------- | :------------ |
| `BusinessException` | Variable (409, 422...) | Definido al lanzar | Reglas de negocio violadas |
| `ValidationException` | `422` | `VALIDATION_ERROR` | FormRequest rechaza los datos |
| `NotFoundHttpException` | `404` | `RESOURCE_NOT_FOUND` | Ruta o modelo no encontrado |
| `AuthenticationException` | `401` | `UNAUTHENTICATED` | Sin token o token inválido |
| `AuthorizationException` | `403` | `FORBIDDEN` | Sin permisos suficientes |

### Cómo lanzar un error de regla de negocio

```php
// En Sprint 1, si un candidato intenta postular dos veces:
if ($candidato->yaPostulo($vacanteId)) {
    throw new PostulacionDuplicadaException(); // ← Una sola línea
}
// Laravel responderá automáticamente con HTTP 409 y el JSON de error estandarizado.
```

### Cómo crear una nueva excepción de negocio

```php
// app/Exceptions/VacanteCerradaException.php
class VacanteCerradaException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            'Esta vacante ya no está recibiendo postulaciones.',
            409,
            'VACANCY_CLOSED'
        );
    }
}
```

Eso es todo. No hay que tocar `bootstrap/app.php`. Automáticamente Angular recibirá:
```json
{
  "success": false,
  "message": "Esta vacante ya no está recibiendo postulaciones.",
  "error_code": "VACANCY_CLOSED"
}
```

---

## 🔒 Autenticación (Sanctum con Tokens Bearer)

La autenticación se realiza con **Laravel Sanctum en modo Token Bearer** (stateless), NO con cookies de sesión.

**¿Por qué Tokens Bearer y no cookies?**
Angular corre en `http://localhost:4200` y el backend en `http://localhost:8000`. Los dominios/puertos diferentes hacen que la gestión de cookies CSRF sea compleja en desarrollo. El microservicio de Python tampoco maneja cookies de navegador.

**Flujo estándar para endpoints protegidos (Sprint 1):**
1. El cliente hace `POST /api/v1/auth/login` y recibe un `token` en la respuesta.
2. En cada petición posterior, el cliente envía el header:
   ```
   Authorization: Bearer {token}
   ```
3. Las rutas protegidas usan el middleware `auth:sanctum`.

**¿Por qué NO se usa `spatie/laravel-permission`?**
Con solo 3 roles fijos (`candidato`, `empresa`, `admin`) no se justifica instalar un paquete que crea 5 tablas adicionales. Se usará una columna `rol` en la tabla `users` con métodos auxiliares en el modelo. Si en el futuro se necesitan permisos granulares por módulo, se puede migrar, pero por ahora es sobreingeniería.

---

## 📚 Documentación Automática (Scramble)

El proyecto usa **[dedoc/scramble](https://scramble.dedoc.co/)** para generar documentación OpenAPI 3.1 automáticamente sin escribir anotaciones.

**Accesos locales (solo con Docker corriendo):**
- **Documentación interactiva:** `http://localhost:8000/docs/api`
- **Archivo OpenAPI JSON (para Postman):** `http://localhost:8000/docs/api.json`

**¿Cómo funciona?** Scramble lee tus rutas, `FormRequests` (los campos requeridos, los tipos de dato) y `API Resources` (el schema de respuesta) y genera la documentación sola. No hay nada que mantener manualmente.

**Configuración relevante en `config/scramble.php`:**
```php
'api_path' => 'api/v1',         // Documenta solo rutas bajo /api/v1
'ui' => ['title' => 'Portal de Empleos API'],
```

---

## 📬 Postman para el Equipo

Ver `postman/README.md` para el paso a paso completo.

**Resumen:**
1. Importa `postman/environment.local.json` → Tendrás las variables `{{base_url}}` y `{{token}}`.
2. Importa la colección desde `http://localhost:8000/docs/api.json`.
3. Cuando haya nuevos endpoints, vuelve a importar la URL de Scramble y selecciona "Update".

**No es necesario pagar Postman.** Cada desarrollador usa una cuenta gratuita personal. La colección se sincroniza a través del código de Laravel y Scramble, no a través de la nube de Postman.

---

## 📌 Convenciones de Nomenclatura y Commits

### Nombres de archivos y clases

| Tipo | Convención | Ejemplo |
| :--- | :--------- | :------ |
| Controlador | `{Dominio}Controller` | `VacanteController` |
| FormRequest | `{Accion}{Dominio}Request` | `CrearVacanteRequest` |
| API Resource | `{Dominio}Resource` | `VacanteResource` |
| Excepción de negocio | `{Regla}Exception` | `PostulacionDuplicadaException` |
| Migración | Snake_case | `create_vacantes_table` |
| Modelo Eloquent | PascalCase singular | `Vacante`, `Candidato` |

### Formato de commits Git (Convencional Commits)

```
feat: agrega endpoint de registro de candidato
fix: corrige validación de fecha de cierre en vacante
refactor: extrae lógica de matching al MatchService
chore: actualiza dependencias de composer
docs: añade comentarios a CandidatoResource
```

---

## ⚠️ Reglas que NO se deben violar

> [!IMPORTANT]
> Respetar estas reglas evita bugs de seguridad y conflictos de merge.

1. **No subir el archivo `.env` a Git.** Está en `.gitignore`. Las credenciales reales nunca van en el repositorio.
2. **No validar datos dentro de controladores.** Siempre usar `FormRequests`.
3. **No devolver modelos Eloquent directamente.** Siempre usar `API Resources`.
4. **No hacer `try/catch` para errores de negocio.** Usar `BusinessException` y dejar que `bootstrap/app.php` los maneje.
5. **No hardcodear URLs ni tokens en Postman.** Usar siempre `{{base_url}}` y `{{token}}`.
6. **No instalar `spatie/laravel-permission`.** Los roles se manejan nativamente (ver sección de Autenticación).
7. **No crear rutas directamente en `routes/api.php`.** Cada módulo va en su archivo dentro de `routes/api_v1/`.
