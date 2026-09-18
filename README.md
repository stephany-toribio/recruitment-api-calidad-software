# Portal de Empleos - API Backend

API REST transaccional desarrollada para la plataforma de intermediación laboral entre candidatos y empresas. Implementada en Laravel 11 y PHP 8.2 sobre contenedores Docker con MySQL 8.0.

---

## 1. Requisitos Previos

Para la ejecución del entorno contenerizado se requiere:
- Docker Desktop (con Docker Engine y Docker Compose v2+)
- Git

Opcional (para ejecución nativa sin Docker):
- PHP >= 8.2 (con extensiones `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`)
- Composer >= 2.6
- MySQL Server >= 8.0

---

## 2. Puesta en Marcha con Docker (Entorno Oficial)

El proyecto incluye configuración completa mediante Docker Compose (`portal_empleos_app` y `portal_empleos_mysql`).

### Paso 1: Configurar variables de entorno
Dentro de la carpeta `portal-empleos-api`, verificar o generar el archivo `.env`. Si no existe, crear una copia a partir del ejemplo:

```bash
cp .env.example .env
```

Asegurarse de que los parámetros de base de datos coincidan con los servicios de Docker:

```ini
APP_NAME=PortalEmpleos
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=portal_empleos
DB_USERNAME=laravel
DB_PASSWORD=secret
```

### Paso 2: Construir y levantar contenedores
Ejecutar el siguiente comando en la raíz del proyecto para descargar imágenes, compilar el contenedor de PHP y levantar los servicios en segundo plano:

```bash
docker compose up -d --build
```

Verificar que los dos contenedores se encuentren en estado activo:

```bash
docker compose ps
```

Deberá mostrarse:
- `portal_empleos_mysql` (Healthy, puerto 3306)
- `portal_empleos_app` (Up, puerto 8000)

### Paso 3: Instalación de dependencias y generación de llave
Si es la primera vez que se clona el proyecto, ejecutar Composer dentro del contenedor:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

### Paso 4: Ejecutar migraciones y datos semilla (Seeders)
Para crear las tablas del sistema y cargar los catálogos base (roles, habilidades técnicas y blandas, catálogo de carreras):

```bash
docker compose exec app php artisan migrate:fresh --seed
```

La API quedará accesible en: `http://localhost:8000/api/v1`

---

## 3. Puesta en Marcha Local (Sin Docker)

Si se prefiere ejecutar la aplicación directamente en la máquina anfitriona:

1. Instalar dependencias del proyecto:
   ```bash
   composer install
   ```

2. Configurar el archivo `.env` apuntando a la base de datos local:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=portal_empleos
   DB_USERNAME=root
   DB_PASSWORD=tu_password
   ```

3. Generar la llave de la aplicación:
   ```bash
   php artisan key:generate
   ```

4. Ejecutar las migraciones y seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. Iniciar el servidor local de desarrollo:
   ```bash
   php artisan serve --port=8000
   ```

---

## 4. Guía de Pruebas de Endpoints con Postman

En el directorio `postman/` se proporcionan la colección completa de endpoints y el archivo de variables de entorno para su validación inmediata.

### Archivos disponibles:
- Colección: `postman/postman_collection_demo1.json`
- Entorno: `postman/environment.local.json`

### Procedimiento de Importación:
1. Abrir Postman.
2. Hacer clic en **Import** y seleccionar ambos archivos (`postman_collection_demo1.json` y `environment.local.json`).
3. En la esquina superior derecha de Postman, seleccionar el entorno **Portal Empleos - Local (Docker)**.
4. La variable `base_url` estará preconfigurada en `http://localhost:8000/api/v1`.

---

## 5. Módulos y Flujo de Prueba Recomendado

La colección está dividida en 4 carpetas organizadas por módulo. Cada petición incluye scripts de prueba automatizados (`Tests`) que validan los códigos de estado HTTP y guardan automáticamente los tokens y llaves primarias en las variables de entorno.

### 5.1. Módulo Auth & Usuarios
Rutas de autenticación, control de sesiones y reseteo de claves.

| Orden | Petición | Método | Endpoint | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| 1 | 1.1 Login - Éxito | POST | `/auth/login` | Inicia sesión y almacena `token` y `usuario_id` en el entorno. |
| 2 | 1.2 Login - Error Credenciales | POST | `/auth/login` | Retorna 401 con mensaje neutro según estándar OWASP. |
| 3 | 1.3 Login - Error Validación | POST | `/auth/login` | Retorna 422 si los campos requeridos están ausentes. |
| 4 | 1.4 Ver Perfil /me | GET | `/auth/me` | Retorna el usuario autenticado usando Bearer Token. |
| 5 | 1.5 Ver Perfil /me - Error Sin Token | GET | `/auth/me` | Retorna 401 si no se envía encabezado de autorización. |
| 6 | 1.6 Ver Perfil por ID | GET | `/auth/usuarios/{id}` | Retorna el perfil mediante el identificador numérico. |
| 7 | 1.7 Ver Perfil por ID - No Existe | GET | `/auth/usuarios/99999` | Retorna 404 con código `RESOURCE_NOT_FOUND`. |
| 8 | 1.8 Cambiar Contraseña - Éxito | PUT | `/auth/usuarios/{id}/password` | Actualiza la clave validando la contraseña actual. |
| 9 | 1.9 Cambiar Contraseña - Error Actual | PUT | `/auth/usuarios/{id}/password` | Retorna 422 si la contraseña actual no coincide. |
| 10 | 1.11 Forgot Password - Éxito | POST | `/auth/forgot-password` | Envío de enlace de recuperación (Mock). |
| 11 | 1.13 Logout - Éxito | POST | `/auth/logout` | Revoca el token activo en Sanctum. |

### 5.2. Módulo Candidatos
Registro transaccional, consulta y actualización de perfiles de postulantes.

| Orden | Petición | Método | Endpoint | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| 1 | 2.1 Registrar Candidato - Éxito | POST | `/candidatos` | Crea usuario, candidato, habilidades y carreras (201 Created). Almacena `candidato_id`. |
| 2 | 2.2 Registrar Candidato - Error Duplicado | POST | `/candidatos` | Retorna 422 si el DNI o correo ya existen. |
| 3 | 2.3 Registrar Candidato - Error Formato | POST | `/candidatos` | Valida 8 dígitos exactos en DNI y formato de celular peruano. |
| 4 | 2.4 Ver Perfil Candidato | GET | `/candidatos/{candidato}` | Retorna perfil con relaciones cargadas vía Route Model Binding. |
| 5 | 2.5 Ver Perfil - No Encontrado | GET | `/candidatos/99999` | Retorna 404 directo sin procesar lógica adicional. |
| 6 | 2.6 Actualizar Perfil - Mismo DNI | PUT | `/candidatos/{candidato}` | Actualiza datos permitiendo enviar el mismo DNI sin conflicto de unicidad. |
| 7 | 2.7 Actualizar Perfil - No Existe | PUT | `/candidatos/99999` | Retorna 404 antes de ejecutar la validación del body. |
| 8 | 2.8 Actualizar Perfil - DNI Colisiona | PUT | `/candidatos/{candidato}` | Retorna 422 si se intenta usar el DNI de otro candidato. |

### 5.3. Módulo Empresas
Registro con validación de RUC corporativo y gestión del perfil empresarial autenticado.

| Orden | Petición | Método | Endpoint | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| 1 | 3.1 Registrar Empresa - Éxito | POST | `/empresas` | Valida RUC de 11 dígitos y contraseña robusta (201 Created). |
| 2 | 3.2 Registrar Empresa - Error Duplicado | POST | `/empresas` | Retorna 422 si el RUC o correo ya están registrados. |
| 3 | 3.3 Registrar Empresa - Password Débil | POST | `/empresas` | Retorna 422 por incumplimiento de complejidad de contraseña. |
| 4 | 3.4 Login Empresa - Éxito | POST | `/auth/login` | Inicia sesión con la empresa y almacena `token_empresa`. |
| 5 | 3.5 Ver Perfil Empresa /me | GET | `/empresas/me` | Retorna datos de la empresa autenticada con Bearer Token. |
| 6 | 3.7 Actualizar Empresa /me | PUT | `/empresas/me` | Actualiza razón social, sitio web y descripción. |

### 5.4. Módulo Habilidades (Catálogo)
Mantenimiento centralizado del catálogo de habilidades técnicas y blandas.

| Orden | Petición | Método | Endpoint | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| 1 | 4.1 Listar Habilidades Activas | GET | `/habilidades` | Retorna lista ordenada alfabéticamente de habilidades activas. |
| 2 | 4.2 Crear Habilidad - Éxito | POST | `/habilidades` | Inserta nueva habilidad validando tipo (`tecnica` o `blanda`). |
| 3 | 4.3 Crear Habilidad - Duplicada | POST | `/habilidades` | Retorna 422 si el nombre ya existe en el catálogo. |
| 4 | 4.5 Actualizar Habilidad | PATCH | `/habilidades/{id}` | Modifica nombre, tipo o cambia estado (`activo` o `inactivo`). |
| 5 | 4.6 Actualizar Habilidad - No Existe | PATCH | `/habilidades/99999` | Retorna 404 con código `RESOURCE_NOT_FOUND`. |

---

## 6. Estructura del Código Fuente

```text
app/
├── Http/
│   ├── Controllers/Api/V1/   # Controladores REST (orquestación)
│   ├── Requests/             # Form Requests (validación de entrada y mensajes en español)
│   └── Resources/Api/V1/     # API Resources (transformación y serialización JSON)
├── Models/                   # Modelos Eloquent y definiciones de relaciones
└── Services/                 # Capa de lógica de negocio y transacciones de base de datos
database/
├── migrations/               # Definición de tablas relacionales
└── seeders/                  # Carga de datos iniciales (roles, carreras, habilidades)
routes/
└── api_v1/                   # Definición de rutas modularizadas por funcionalidad
postman/                      # Colección y entorno de pruebas para la API
```

---

## 7. Estándar de Respuestas HTTP

Todas las respuestas de la API cumplen con el siguiente formato JSON estandarizado:

### Respuesta Exitosa (200, 201)
```json
{
    "success": true,
    "message": "Mensaje descriptivo de la operación",
    "data": { ... }
}
```

### Respuesta de Error de Validación (422)
```json
{
    "success": false,
    "message": "Los datos proporcionados no son válidos.",
    "error_code": "VALIDATION_ERROR",
    "errors": {
        "campo": [
            "Mensaje de validación detallado"
        ]
    }
}
```

### Respuesta de Recurso No Encontrado (404)
```json
{
    "success": false,
    "message": "El recurso solicitado no fue encontrado.",
    "error_code": "RESOURCE_NOT_FOUND"
}
```
