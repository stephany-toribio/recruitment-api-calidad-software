# Portal de Empleos — API para Calidad de Software

API REST del portal CREO+, implementada con Laravel 12 y Sanctum 4. El proyecto requiere PHP 8.2 o superior; el Dockerfile utiliza PHP 8.3 y Docker Compose proporciona MySQL 8.0.

Frontend del mismo proyecto: [web-app-calidad-software](https://github.com/stephany-toribio/web-app-calidad-software).

## Alcance para Calidad de Software

El objetivo de esta copia del Portal de Empleos es evaluar un proceso completo con datos persistentes, permisos y decisiones de aprobación. La base de trabajo es `develop`; `main` conserva una versión anterior y las ramas `deploy/*` tienen ajustes de despliegue que deben revisarse antes de publicar una entrega.

### Flujo que se evaluará

1. Registrar candidatos y empresas e iniciar y cerrar sesión.
2. Registrar una vacante desde la cuenta de empresa.
3. Aprobar o no aprobar la vacante desde administración y completar el pago simulado cuando corresponda.
4. Buscar una vacante disponible, consultar su detalle y postular como candidato.
5. Revisar postulaciones desde la empresa propietaria y avanzar por Recibido, En revisión y Entrevista, hasta Contratado o Rechazado, según las transiciones permitidas.
6. Consultar el resultado y el historial; comprobar el retiro de una postulación cuando el estado lo permita.

### Reglas que deben tener evidencia

- Validación de campos, datos duplicados y persistencia después de volver a consultar.
- Autenticación, sesión y permisos por rol y por propietario del recurso.
- Publicación y disponibilidad de la vacante: aprobación, pago simulado exitoso, empresa activa, vigencia, estado y puestos aún disponibles.
- Una sola postulación por candidato y vacante, incluso ante solicitudes concurrentes.
- Transiciones válidas, motivo de rechazo cuando corresponda e historial de cambios.
- Integridad de los datos ante errores, retiros y solicitudes simultáneas.

### Herramientas y entregables previstos

| Herramienta | Qué se comprobará | Evidencia esperada |
| --- | --- | --- |
| Karate | Contratos API, validaciones, permisos, postulaciones y decisiones | Escenarios `.feature`, datos de prueba y reporte de ejecución |
| JMeter | Consultas, autenticación y postulaciones bajo concurrencia controlada | Plan `.jmx`, datos CSV cuando correspondan, resultados y reporte HTML |
| Selenium | Flujo web del candidato y de la empresa | Código, resultados y capturas de los casos ejecutados |
| Appium | Inicio de sesión, búsqueda, detalle y postulación en Android | Código, capabilities, versiones del dispositivo o emulador y resultados |
| Jira | Relación entre requisitos, casos, ejecuciones y defectos | Tickets y enlaces a las evidencias |

Estas evidencias están previstas para el curso. Los archivos o pantallas presentes en el repositorio no equivalen a una ejecución aprobada de las pruebas.

### Móvil y PWA

La prueba móvil debe ejecutarse con Appium en un dispositivo o emulador Android. La vista adaptable de un navegador de escritorio no sustituye esa ejecución.

La PWA es la opción propuesta para una entrega instalable. Su aceptación como producto móvil debe contrastarse con el requisito del profesor. Appium también permite automatizar Chrome en Android; técnicamente no requiere convertir primero la web en PWA. Si se elige PWA, habrá que implementar y verificar manifiesto, iconos, instalación, navegación, sesión y servicio de actualización o caché. No se presenta como funcionalidad terminada.

### Límites de esta entrega

El matching y la integración con servicios Python son complementarios: no bloquean el flujo principal ni las cuatro automatizaciones. Los pagos se simulan; no se integra una pasarela bancaria real. Los paneles y catálogos se incluirán en las pruebas cuando sean necesarios para preparar o completar el flujo elegido.

Las pruebas deben usar datos ficticios y una base de QA aislada. Antes de cada ejecución se registrarán las versiones del frontend y la API, la configuración utilizada, los datos y el resultado esperado. Un despliegue que abre correctamente no acredita por sí solo el cumplimiento del alcance.

## Estado de la base revisada

En `develop` existen autenticación, controles de rol, perfiles, vacantes, moderación, pago simulado, postulaciones, retiro, Kanban e historial. También hay pruebas PHPUnit de permisos, validación, disponibilidad, postulaciones y concurrencia. Su presencia no acredita que hayan sido ejecutadas correctamente en el entorno de esta copia.

El README anterior describía Laravel 11 y endpoints que ya cambiaron. Las rutas actuales están en `routes/api_v1/` y se cargan desde `routes/api.php`, con prefijo `/api/v1`.

## Requisitos y preparación

- Git con acceso al repositorio privado.
- Docker Engine y Docker Compose v2, o Docker Desktop.
- Para ejecutar sin Docker: PHP 8.2 o superior, Composer 2 y MySQL 8.0 con las extensiones requeridas por Composer.

```bash
git clone --branch develop https://github.com/stephany-toribio/recruitment-api-calidad-software.git
cd recruitment-api-calidad-software
cp .env.example .env
```

Para el entorno Docker local, configurar en `.env`:

```ini
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=portal_empleos
DB_USERNAME=laravel
DB_PASSWORD=secret
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Estos valores de conexión corresponden exclusivamente al Compose local de desarrollo. `APP_KEY` se genera en el siguiente paso. Para una instalación sin Docker, usar la dirección del servidor MySQL local en `DB_HOST` y sus propias credenciales.

## Primer arranque con Docker

Instalar las dependencias antes de iniciar el servicio persistente de la API:

```bash
docker compose build app
docker compose run --rm --no-deps app composer install
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose ps
```

`docker compose run` crea un contenedor temporal para instalar Composer aunque `app` todavía no pueda iniciar. El comando del servicio ejecuta `php artisan serve`, que necesita `vendor/` desde el primer arranque.

Los servicios son `app` y `mysql`; los contenedores se llaman `portal_empleos_app` y `portal_empleos_mysql`. La API local queda en `http://localhost:8000/api/v1`.

Las migraciones y seeders inicializan roles, administrador y catálogos. Consultar los seeders para preparar las cuentas de la demostración; no utilizar datos personales reales. El borrado y recreación de una base no forma parte del arranque habitual.

## Endpoints principales del flujo

Todos los paths siguientes parten de `/api/v1`. Las rutas protegidas utilizan `Authorization: Bearer <token>`.

| Operación | Método | Path | Acceso |
| --- | --- | --- | --- |
| Registrar candidato | POST | `/candidatos` | Público; valida los datos |
| Registrar empresa | POST | `/empresas` | Público; valida los datos |
| Iniciar sesión | POST | `/auth/login` | Público |
| Consultar sesión | GET | `/auth/me` | Autenticado |
| Cerrar sesión | POST | `/auth/logout` | Autenticado |
| Consultar o actualizar perfil propio | GET / PUT | `/candidatos/me` | Candidato |
| Consultar o actualizar empresa propia | GET / PUT | `/empresas/me` | Empresa |
| Registrar o listar vacantes propias | POST / GET | `/empresas/me/vacantes` | Empresa |
| Aprobar vacante | PATCH | `/admin/vacantes/{vacante}/aprobar` | Administrador |
| No aprobar vacante | PATCH | `/admin/vacantes/{vacante}/no-aprobar` | Administrador |
| Registrar pago simulado | POST | `/empresas/me/vacantes/{vacante}/pago` | Empresa propietaria |
| Buscar vacantes disponibles | GET | `/vacantes` | Candidato |
| Consultar detalle disponible | GET | `/vacantes/{vacante}` | Candidato |
| Postular o listar postulaciones propias | POST / GET | `/candidatos/me/postulaciones` | Candidato |
| Retirar postulación | PATCH | `/candidatos/me/postulaciones/{postulacion}/retirar` | Candidato propietario |
| Consultar Kanban | GET | `/empresas/me/vacantes/{vacante}/postulaciones` | Empresa propietaria |
| Avanzar postulación | PATCH | `/empresas/me/vacantes/{vacante}/postulaciones/{postulacion}/avanzar` | Empresa propietaria |
| Rechazar postulación | PATCH | `/empresas/me/vacantes/{vacante}/postulaciones/{postulacion}/rechazar` | Empresa propietaria |
| Contratar candidato | PATCH | `/empresas/me/vacantes/{vacante}/postulaciones/{postulacion}/contratar` | Empresa propietaria |
| Consultar historial | GET | `/empresas/me/vacantes/{vacante}/postulaciones/historial` | Empresa propietaria |

El formulario de postulación envía `vacante_id`; la identidad del candidato se obtiene del usuario autenticado. La disponibilidad exige una vacante aprobada, con pago simulado exitoso, activa, vigente, sin eliminar, con puestos disponibles y de una empresa activa. Estas condiciones también deben prepararse en los datos de prueba.

## Pruebas existentes y automatización del curso

El comando general ejecuta las suites Unit y Feature definidas en `phpunit.xml`:

```bash
docker compose exec app php artisan test
```

La configuración predeterminada usa SQLite en memoria. Verificar que el contenedor tenga la extensión correspondiente antes de ejecutar esas suites.

Las pruebas en `tests/MySql/` no están incluidas en las suites del comando anterior. Requieren ejecución expresa, entorno `testing`, configuración sin caché y una base MySQL aislada llamada exactamente `portalempleoshu04testing`, según sus protecciones actuales. Las pruebas de concurrencia también necesitan lanzar procesos independientes.

Preparar esa base, sus permisos y migraciones en el entorno de pruebas y configurar sus variables de conexión antes de invocar `php artisan test tests/MySql`. Hay que sustituir de forma explícita la conexión SQLite definida en `phpunit.xml`; copiar el nombre de una base en `.env` no basta. No interpretar la ejecución de Unit y Feature como validación de las pruebas MySQL.

Las suites del curso con Karate y JMeter quedan por incorporar y ejecutar. PHPUnit complementa esas herramientas y no sustituye los entregables solicitados. Registrar el commit probado, el ambiente, los datos, los resultados y las incidencias.

## Colección Postman de referencia

Se conservan estos archivos:

- `postman/postman_collection_demo1.json`.
- `postman/environment.local.json`.

Son una referencia heredada para exploración de la API. Contrastar las rutas, el esquema del registro y las credenciales con la versión actual antes de usarlos; no asumir que todos los ejemplos anteriores siguen vigentes. Postman no reemplaza la suite Karate prevista para el curso.

## Orden de revisión de errores

1. Preparar un ambiente local reproducible y ejecutar las pruebas existentes.
2. Registrar los fallos con datos y pasos; corregirlos y repetir el caso que los detectó.
3. Verificar el flujo completo con el frontend de este proyecto y datos de QA.
4. Automatizar los casos API, web y Android y definir las cargas y umbrales de rendimiento.
5. Desplegar en QA y repetir la comprobación de integración, configuración y conectividad.

Se pueden detectar errores antes del despliegue. Las comprobaciones locales y las del entorno desplegado cubren aspectos distintos y ambas necesitan evidencia.

## Ramas y contribución

Crear las ramas de trabajo desde `develop` y presentar los cambios mediante Pull Request hacia esa rama. Seguir [CONTRIBUTING.md](CONTRIBUTING.md). Revisar los ajustes de `deploy/backend` antes de preparar un entorno publicado y coordinar su URL con el frontend de QA.

## Referencias técnicas

- [Appium UiAutomator2: automatización Android y contexto web](https://github.com/appium/appium-uiautomator2-driver).
- [Angular: incorporación del service worker y recursos de PWA](https://angular.dev/ecosystem/service-workers/getting-started).
