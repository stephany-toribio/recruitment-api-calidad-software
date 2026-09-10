# 🚀 Portal de Empleos API — Guía de Instalación Local

Esta guía te llevará desde cero hasta tener el proyecto corriendo localmente en menos de 10 minutos. Sigue cada paso en orden.

---

## 📋 Requisitos Previos

Instala las siguientes herramientas **antes de comenzar**. Si ya las tienes, verifica las versiones mínimas.

| Herramienta | Versión mínima | Descarga |
| :---------- | :------------- | :------- |
| Git | 2.x | https://git-scm.com/downloads |
| Docker Desktop | 4.x | https://www.docker.com/products/docker-desktop |
| Postman | Cualquiera (Plan gratuito) | https://www.postman.com/downloads |

> ⚠️ **Importante con Docker Desktop:**
> - En Windows, asegúrate de que **WSL 2** esté habilitado (Docker Desktop lo solicita automáticamente al instalar).
> - Docker Desktop debe estar **abierto y corriendo** antes de ejecutar cualquier comando `docker`.

---

## 🛠️ Instalación Paso a Paso

### Paso 1: Clonar el Repositorio

Abre **PowerShell** (o la terminal de tu preferencia) y ejecuta:

```powershell
# Clona el repositorio en tu máquina local
git clone <URL_DEL_REPOSITORIO>

# Entra a la carpeta del proyecto
cd portal-empleos-api
```

> 💡 Reemplaza `<URL_DEL_REPOSITORIO>` con la URL del repo en GitHub/GitLab. Pídela al líder del equipo si no la tienes.

---

### Paso 2: Configurar el Archivo de Variables de Entorno (`.env`)

El archivo `.env` contiene las credenciales y configuraciones locales. **Nunca se sube a Git** (está en `.gitignore`), por lo que debes crearlo tú manualmente a partir de la plantilla de ejemplo:

```powershell
# Copia la plantilla de ejemplo
copy .env.example .env
```

Luego abre el `.env` que se acaba de crear y **reemplaza su contenido completo** con el siguiente bloque (ya configurado para el entorno Docker local):

```env
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

REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@portalempleos.test
```

> ⚠️ **Nota:** `APP_KEY=` se deja vacío a propósito. Se generará automáticamente en el Paso 5.

---

### Paso 3: Construir la Imagen Docker

Este comando descarga PHP 8.3, instala las extensiones necesarias (`pdo_mysql`, `gd`, `zip`, etc.) y prepara el contenedor de la aplicación. **Solo es necesario hacerlo una vez** (o cuando cambie el `Dockerfile`).

```powershell
docker compose build app
```

⏳ La primera vez puede tardar entre 2 y 5 minutos dependiendo de tu conexión. Verás un progreso en la terminal.

---

### Paso 4: Levantar Todos los Servicios

```powershell
docker compose up -d
```

Esto iniciará **4 contenedores** en segundo plano (`-d` = detached mode):

| Contenedor | Descripción | Puerto local |
| :--------- | :---------- | :----------- |
| `portal_empleos_app` | Servidor Laravel (PHP 8.3) | `http://localhost:8000` |
| `portal_empleos_mysql` | Base de datos MySQL 8.0 | `localhost:3306` |
| `portal_empleos_redis` | Caché y colas en memoria | (interno) |
| `portal_empleos_mailpit` | Servidor de correos de prueba | `http://localhost:8025` |

Puedes verificar que todos están corriendo con:
```powershell
docker compose ps
```
Deberías ver todos con estado **`Up`** o **`Up (healthy)`**.

---

### Paso 5: Generar la Clave de la Aplicación

Este comando genera la clave de encriptación de Laravel y la escribe en tu `.env` en la variable `APP_KEY`:

```powershell
docker compose run --rm app php artisan key:generate
```

Después de ejecutarlo, tu `.env` tendrá algo como:
```
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

### Paso 6: Instalar el Scaffolding de la API (Sanctum)

Instala `laravel/sanctum` y ejecuta las migraciones iniciales de la base de datos:

```powershell
docker compose run --rm app php artisan install:api --no-interaction
```

Este comando:
1. Instala el paquete de autenticación `laravel/sanctum` via Composer.
2. Publica el archivo de rutas `routes/api.php`.
3. Crea la estructura de tablas en MySQL:
   - `users`
   - `cache`
   - `jobs`
   - `personal_access_tokens` (tokens de Sanctum)

---

### Paso 7: Verificar que Todo Funciona

Abre tu navegador y entra a:

- ✅ **`http://localhost:8000`** → Deberías ver la pantalla de bienvenida de Laravel.
- ✅ **`http://localhost:8000/docs/api`** → Deberías ver la documentación interactiva de la API (generada automáticamente por Scramble).
- ✅ **`http://localhost:8025`** → Deberías ver el panel de Mailpit para ver correos de prueba.

---

## 📬 Configurar Postman (Para Probar Endpoints)

### 1. Importar el Entorno Local

1. Abre Postman.
2. Haz clic en **Import** (arriba a la izquierda).
3. Selecciona el archivo: 📁 `postman/environment.local.json`
4. En la esquina superior derecha de Postman, selecciona en el menú desplegable:
   **`Portal Empleos - Local (Docker)`**

### 2. Importar la Colección de Endpoints

1. En Postman, haz clic en **Import** nuevamente.
2. En la barra de URL pega:
   ```
   http://localhost:8000/docs/api.json
   ```
3. Haz clic en **Import** para generar la colección completa.

> 🔄 **¿Cómo actualizar la colección cuando haya nuevos endpoints?**
> Cada vez que algún compañero agregue nuevas rutas y hagas `git pull`, vuelve a hacer **Import** de `http://localhost:8000/docs/api.json` y selecciona **"Replace/Update"**. Postman actualizará todo automáticamente.

---

## 🔧 Comandos Útiles del Día a Día

### Iniciar el entorno de trabajo (cada vez que abras tu laptop)
```powershell
# Asegúrate de estar en la carpeta del proyecto
cd portal-empleos-api

# Encender los contenedores
docker compose up -d
```

### Apagar los contenedores al terminar
```powershell
docker compose down
```

### Ejecutar comandos de Artisan
En lugar de correr PHP directamente, **todos los comandos de Artisan van con el prefijo de Docker**:

```powershell
# Crear una migración
docker compose exec app php artisan make:migration create_vacantes_table

# Crear un controlador
docker compose exec app php artisan make:controller Api/V1/VacanteController

# Ejecutar migraciones
docker compose exec app php artisan migrate

# Ver todas las rutas registradas
docker compose exec app php artisan route:list

# Instalar un paquete de Composer
docker compose exec app composer require nombre/paquete
```

### Abrir una terminal dentro del contenedor (para múltiples comandos)
```powershell
docker compose exec app bash
# Ahora puedes escribir directamente:
# php artisan migrate
# composer install
# exit  <- para salir
```

### Ver logs de la aplicación en tiempo real
```powershell
docker compose logs -f app
```

### Ver el estado de todos los contenedores
```powershell
docker compose ps
```

---

## 🗄️ Acceder a la Base de Datos

Si necesitas inspeccionar la base de datos directamente (sin cliente visual), puedes conectarte vía MySQL CLI desde dentro del contenedor:

```powershell
docker compose exec mysql mysql -u laravel -psecret portal_empleos
```

O si prefieres un cliente visual como **TablePlus**, **HeidiSQL**, **DBeaver** o **MySQL Workbench**, conéctate con estos datos:

| Campo | Valor |
| :---- | :---- |
| Host | `127.0.0.1` |
| Puerto | `3306` |
| Usuario | `laravel` |
| Contraseña | `secret` |
| Base de datos | `portal_empleos` |

---

## ❓ Problemas Frecuentes

### 🔴 "docker: command not found" o "docker compose: command not found"
Docker Desktop no está instalado o no está corriendo. Ábrelo desde el menú de aplicaciones de Windows y espera a que el ícono de la ballena aparezca en la barra de tareas.

### 🔴 "Error response from daemon: Ports are already in use"
Otro proceso ya está usando el puerto `8000`, `3306` o `8025`. Cierra otros servidores locales que estés corriendo (XAMPP, Laragon, Laragon, etc.) o cambia el puerto en `docker-compose.yml`.

### 🔴 "ERROR Unable to set application key. No APP_KEY variable was found"
El archivo `.env` no tiene la línea `APP_KEY=`. Verifica que el contenido de tu `.env` sea exactamente el del Paso 2.

### 🔴 "Port 3306 already in use" al levantar MySQL
Tienes MySQL instalado localmente en Windows corriendo en ese puerto. Detén el servicio de MySQL local o cambia el puerto del contenedor en `docker-compose.yml` a `"3307:3306"` y actualiza `DB_PORT=3307` en tu `.env`.

### 🔴 "No such container: portal_empleos_app"
Los contenedores no están levantados. Ejecuta `docker compose up -d` primero.

---

## 📌 Convenciones del Equipo

Para mantener el código consistente entre los 3 desarrolladores del backend, seguimos estos acuerdos:

- **Versión de API:** Todos los endpoints van bajo `/api/v1/` — nunca sin versionar.
- **Controladores:** Se ubican en `app/Http/Controllers/Api/V1/`. Un controlador por dominio.
- **Validaciones:** Se usan `FormRequests` en `app/Http/Requests/{Modulo}/`. **Nunca** validar manualmente dentro del controlador.
- **Respuestas JSON:** Usar los métodos `$this->successResponse()` y `$this->errorResponse()` heredados de `BaseApiController`.
- **Excepciones de negocio:** Extender de `BusinessException` y lanzarlas con `throw`. El manejo centralizado en `bootstrap/app.php` se encarga del resto.
- **Formato de commits (Git):** `feat:`, `fix:`, `refactor:`, `chore:` + descripción corta en español.
  - Ejemplo: `feat: agrega endpoint de registro de candidato`

---

*Ante cualquier duda, consulta al equipo por el canal de comunicación acordado.*
