# Catálogo de Datos Ficticios de Prueba — QA-04

- **Proyecto**: Portal de Empleos CREO+ (PWA y API REST) — Calidad de Software.
- **Responsable**: R3 (@luisvalenzuela25).
- **Revisor asignado**: R1 (@stephany-toribio).
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.0.0.
- **Fecha**: 24/11/2026.
- **Rama**: `feature/QA-04-matriz-pruebas`.

---

## 1. Principios de gestión de datos de prueba

1. **Sin credenciales ni datos personales reales**: No se almacenan contraseñas en texto claro, tokens vigentes, números reales de DNI, RUC ni tarjetas bancarias reales en repositorios, suites ni evidencias.
2. **Identificadores lógicos**: Las pruebas automatizadas (Karate, Selenium, Appium, JMeter) no deben asumir identificadores numéricos autoincrementales fijos (`id: 1`, `id: 5`), sino resolver dinámicamente las entidades por atributos unívocos (correo, RUC, código lógico).
3. **Contraseña parametrizada**: La contraseña de todas las cuentas ficticias de prueba se alimenta exclusivamente de la variable de entorno local `QA_FIXTURE_PASSWORD` (mínimo 12 caracteres).
4. **Distinción de estado de datos**: Se diferencian los datos **existentes** (inicializados por `QaSeeder.php` en QA-02) de los datos **propuestos / pendientes de preparación** (diseñados para las suites de carga en QA-24 y pruebas concurrentes).

---

## 2. Cuentas ficticias por rol y propiedad

### 2.1 Cuentas Existentes (Línea Base QA-02 — `QaSeeder.php`)

| ID Lógico | Correo de prueba | Rol asignado | Estado de cuenta | Datos complementarios | Propiedad / Finalidad en pruebas |
|---|---|---|---|---|---|
| `USR-ADM-01` | `admin@qa.example.test` | `admin` | Activo | Sin perfil empresarial ni candidato | Actor de moderación y aprobación de vacantes (`RF-05`, `CP-08`). |
| `USR-EMP-01` | `empresa1@qa.example.test` | `empresa` | Activo | Razón Social: *Empresa QA Alfa*<br>RUC: `20000000001`<br>Encargado: Responsable QA | **Empresa Propietaria A**. Creación de vacantes, pago simulado, gestión de su propio tablero Kanban e historial. |
| `USR-EMP-02` | `empresa2@qa.example.test` | `empresa` | Activo | Razón Social: *Empresa QA Beta*<br>RUC: `20000000002`<br>Encargado: Responsable QA | **Empresa Propietaria B (Ajena)**. Empleada para pruebas negativas de autorización cruzada (acceso prohibido a recursos ajenos, `CP-06-03`, `CP-09-03`, `CP-15-03`). |
| `USR-CND-01` | `candidato1@qa.example.test` | `candidato` | Activo | Nombre: *Ana Prueba QA*<br>DNI: `00000001`<br>Celular: `900000001`<br>Primer empleo: Sí | **Candidato Propietario 1**. Búsqueda de empleo, primera postulación, seguimiento y retiro propio (`CP-03`, `CP-06`, `CP-12`, `CP-20`). |
| `USR-CND-02` | `candidato2@qa.example.test` | `candidato` | Activo | Nombre: *Bruno Prueba QA*<br>DNI: `00000002`<br>Celular: `900000002`<br>Primer empleo: Sí | **Candidato Competidor 2**. Empleado en postulaciones concurrentes, postulación a vacantes con cupo limitado y pruebas de autorización cruzada (`CP-14`, `CP-19`, `CP-20-02`). |
| `USR-CND-03` | `suspendido@qa.example.test` | `candidato` | Suspendido | Nombre: *Carlos Prueba QA*<br>DNI: `00000003`<br>Motivo: *Fixture QA de cuenta suspendida* | **Candidato Inactivo/Suspendido**. Verificación de rechazo de autenticación y postulación denegada por suspensión de cuenta (`CP-04-03`). |

### 2.2 Cuentas Propuestas (Pendientes de generación en QA-24 para JMeter)

| ID Lógico | Rango de correos | Rol | Cantidad | Finalidad | Origen previsto |
|---|---|---|---|---|---|
| `USR-LOAD-CND-XX` | `candidato.load01@qa.example.test` a `...load25@qa.example.test` | `candidato` | 25 | Inyección de carga de 25 usuarios virtuales concurrentes en login y búsqueda (`CP-31`, `CP-32`). | CSV ficticio en `qa/jmeter/data/` (QA-24). |
| `USR-RACE-CND-XX` | `candidato.race01@qa.example.test` a `...race05@qa.example.test` | `candidato` | 5 | Pruebas de colisión y condición de carrera en contratación al último puesto (`CP-19`). | Seeder transaccional de pruebas de carga. |

---

## 3. Condiciones lógicas de vacantes (Empleos)

Para validar exhaustivamente el ciclo de publicación y búsqueda (`RF-04`, `RF-05`, `RF-06`, `CP-07` a `CP-11`), se definen las 8 condiciones lógicas posibles de una vacante:

| ID Lógico | Condición de negocio | Estado de moderación (`revisiones_vacante`) | Estado de pago (`pagos_vacante`) | Estado empresa (`usuarios`) | Estado empleo (`empleos`) | Fechas de vigencia | Soft delete (`deleted_at`) | Puestos vs Contratados | ¿Visible en Búsqueda? | ¿Admite Postulación? |
|---|---|---|---|---|---|---|---|---|---|---|
| `VAC-BORRADOR` | Registrada inicial | Pendiente (sin fila) | Sin pago (sin fila) | Activa | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) |
| `VAC-RECHAZADA` | No aprobada admin | `decision = 'no_aprobada'`, motivo registrado | Sin pago | Activa | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) |
| `VAC-APROB-SIN-PAGO` | Aprobada pendiente pago | `decision = 'aprobada'` | Sin pago o fallido | Activa | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) |
| `VAC-DISPONIBLE` | **Publicada y disponible** | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | `CURDATE() BETWEEN inicio AND fin` | NULL | 0 contratados < 2 puestos | **Sí** | **Sí** (201) |
| `VAC-PAUSADA` | Pausada por empresa | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `pausada` | Vigente | NULL | 0 / 2 | **No** (Histórica) | **No** (404) |
| `VAC-FINALIZADA` | Finalizada por empresa | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `finalizada` | Vigente | NULL | 0 / 2 | **No** (Histórica) | **No** (404) |
| `VAC-VENCIDA` | Fuera de vigencia | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | `fin < CURDATE()` | NULL | 0 / 2 | **No** (Histórica) | **No** (404) |
| `VAC-COMPLETA` | Cupo agotado | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | Vigente | NULL | 2 contratados == 2 puestos | **No** (Histórica) | **No** (404) |
| `VAC-ELIMINADA` | Eliminada lógicamente | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | Vigente | NOT NULL | 0 / 2 | **No** (Histórica para postulantes previos) | **No** (404) |

---

## 4. Condiciones lógicas de postulaciones

Para validar el ciclo de selección (`RF-07`, `RF-08`, `RF-09`, `RF-10`, `CP-12` a `CP-22`), se definen los siguientes estados lógicos:

| ID Lógico | Estado en base de datos (`estado_postulacion_id`) | Nombre de estado | Marca de retiro (`retirada_en`) | Acciones permitidas por la empresa | Acciones permitidas por el candidato | Estado final |
|---|---|---|---|---|---|---|
| `POS-RECIBIDO` | 1 | `Recibido` | NULL | Avanzar a `En Revisión` | Retirar | No |
| `POS-REVISION` | 2 | `En Revisión` | NULL | Avanzar a `Entrevista`, Rechazar con motivo | Retirar | No |
| `POS-ENTREVISTA`| 3 | `Entrevista` | NULL | Contratar (si hay cupo), Rechazar con motivo | Retirar | No |
| `POS-CONTRATADO`| 4 | `Contratado` | NULL | Ninguna (Bloqueado) | Ninguna (Bloqueado el retiro) | **Sí** |
| `POS-RECHAZADO` | 5 | `Rechazado` | NULL | Ninguna (Bloqueado) | Ninguna (Bloqueado el retiro) | **Sí** |
| `POS-RETIRADA`  | 1, 2 o 3 (Conserva etapa) | `Recibido`, `En Revisión` o `Entrevista` | NOT NULL (Timestamp) | Ninguna (`APPLICATION_WITHDRAWN`) | Ninguna (No permite repostular al mismo empleo) | **Sí (Operativo)** |

---

## 5. Catálogos base de soporte (Inmutables en pruebas)

Los catálogos están inicializados por los seeders base del sistema (`database/seeders/`):
- **Estados de postulación** (5): `Recibido` (1), `En Revisión` (2), `Entrevista` (3), `Contratado` (4), `Rechazado` (5).
- **Motivos de rechazo** (3):
  1. `Perfil no se ajusta` (`id: 1`).
  2. `Expectativa salarial alta` (`id: 2`).
  3. `Proceso cerrado` (`id: 3`).
- **Roles del sistema** (3): `admin` (1), `empresa` (2), `candidato` (3).
- **Países inicializados**: 5 registros activos.
- **Habilidades técnicas**: 153 habilidades activas.

---

## 6. Procedimientos de aislamiento, limpieza y restauración

### 6.1 Aislamiento de entornos
- Las pruebas no tocan bases de datos de producción ni del portal original.
- Pruebas funcionales y API: se ejecutan contra la base `portal_empleos_calidad_qa` en MySQL Docker (puerto externo 3307).
- Pruebas MySQL de concurrencia/integración: se ejecutan contra `portalempleoshu04testing`.
- Pruebas Unitarias y Feature Laravel: se ejecutan en SQLite en memoria (`:memory:`).

### 6.2 Limpieza controlada
Para depurar exclusivamente las entidades de prueba creadas durante las ejecuciones sin alterar catálogos base:
```bash
docker compose exec app php artisan db:seed --class=QaCleanupSeeder
```
*Regla crítica de seguridad*: `QaCleanupSeeder` verifica que la base sea estrictamente `portal_empleos_calidad_qa` y aborta inmediatamente si detecta dependencias funcionales o si se invoca fuera del entorno local/testing.

### 6.3 Restauración de datos
Para volver a la línea base limpia de datos de prueba:
```bash
docker compose exec app php artisan db:seed --class=QaSeeder
```
Este comando es **idempotente**: si los usuarios ya existen, actualiza sus credenciales y estado, revoca tokens previos y garantiza que no se generen registros duplicados.
