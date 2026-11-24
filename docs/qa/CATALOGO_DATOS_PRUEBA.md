# Catálogo de Datos Ficticios de Prueba — QA-04

- **Proyecto**: Portal de Empleos CREO+ (PWA y API REST) — Calidad de Software.
- **Responsable**: R3 (@luisvalenzuela25).
- **Revisor asignado**: R1 (@stephany-toribio).
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.1.0 (Corregida tras revisión cruzada).
- **Fecha**: 24/11/2026.
- **Rama**: `feature/QA-04-matriz-pruebas`.

---

## 1. Principios de gestión de datos de prueba

1. **Sin credenciales ni datos personales reales**: No se almacenan contraseñas en texto claro, tokens vigentes, números reales de DNI, RUC ni tarjetas bancarias en el repositorio, suites ni evidencias.
2. **Identificadores lógicos**: Las pruebas automatizadas (Karate, Selenium, Appium, JMeter) no deben asumir identificadores numéricos autoincrementales fijos para entidades de negocio (`candidato_id: 1`, `vacante_id: 5`), sino resolver dinámicamente las entidades por atributos unívocos (correo, RUC, slug) o capturando el ID devuelto por la API en respuestas previas.
3. **Contraseña parametrizada**: La contraseña de todas las cuentas ficticias de prueba se alimenta exclusivamente de la variable de entorno local `QA_FIXTURE_PASSWORD` (mínimo 12 caracteres).
4. **Catálogos inmutables vs. Entidades transaccionales**: Los IDs de catálogos base (estados de postulación, motivos de rechazo, roles) están sembrados de forma determinista por los seeders oficiales del sistema; no obstante, las suites deben preferir consultar las rutas públicas de catálogo (`GET /api/v1/motivos-rechazo`, `GET /api/v1/niveles-experiencia`) para mayor resiliencia.

---

## 2. Cuentas ficticias por rol y propiedad

### 2.1 Cuentas Existentes (Línea Base QA-02 — `QaSeeder.php`)

| ID Lógico | Correo de prueba | Rol asignado | Estado de cuenta | Datos complementarios | Propiedad / Finalidad en pruebas |
|---|---|---|---|---|---|
| `USR-ADM-01` | `admin@qa.example.test` | `admin` | Activo | Sin perfil empresarial ni de candidato | **Administrador del Sistema**. Moderación y aprobación/rechazo de vacantes (`RF-05`, `CP-08`). |
| `USR-EMP-01` | `empresa1@qa.example.test` | `empresa` | Activo | Razón Social: *Empresa QA Alfa*<br>RUC: `20000000001`<br>Encargado: Responsable QA | **Empresa Propietaria A**. Creación de vacantes, pago simulado, gestión de su propio tablero Kanban e historial. |
| `USR-EMP-02` | `empresa2@qa.example.test` | `empresa` | Activo | Razón Social: *Empresa QA Beta*<br>RUC: `20000000002`<br>Encargado: Responsable QA | **Empresa Propietaria B (Ajena)**. Empleada para pruebas negativas de control de acceso cruzado (acceso prohibido a recursos ajenos, `CP-06-03`, `CP-09-04`, `CP-15-03`). |
| `USR-CND-01` | `candidato1@qa.example.test` | `candidato` | Activo | Nombre: *Ana Prueba QA*<br>DNI: `00000001`<br>Celular: `900000001`<br>Primer empleo: Sí | **Candidato Propietario 1**. Búsqueda de empleo, primera postulación, seguimiento y retiro propio (`CP-03`, `CP-06`, `CP-12`, `CP-20`). |
| `USR-CND-02` | `candidato2@qa.example.test` | `candidato` | Activo | Nombre: *Bruno Prueba QA*<br>DNI: `00000002`<br>Celular: `900000002`<br>Primer empleo: Sí | **Candidato Competidor 2**. Empleado en postulaciones concurrentes, postulación a vacantes con cupo limitado y pruebas de autorización cruzada (`CP-14`, `CP-19`, `CP-20-02`). |
| `USR-CND-03` | `suspendido@qa.example.test` | `candidato` | Suspendido | Nombre: *Carlos Prueba QA*<br>DNI: `00000003`<br>Motivo: *Fixture QA de cuenta suspendida* | **Candidato Inactivo/Suspendido**. Verificación de rechazo de autenticación y postulación denegada por suspensión de cuenta (`CP-04-03`, `CP-12-02`). |

### 2.2 Cuentas Propuestas (Pendientes de generación en QA-24 para JMeter)

| ID Lógico | Rango de correos | Rol | Cantidad | Finalidad | Origen previsto |
|---|---|---|---|---|---|
| `USR-LOAD-CND-XX` | `candidato.load01@qa.example.test` a `...load25@qa.example.test` | `candidato` | 25 | Inyección de carga de 25 usuarios virtuales concurrentes en login y búsqueda (`CP-31`, `CP-32`). | CSV ficticio en `qa/jmeter/data/` (QA-24). |
| `USR-RACE-CND-XX` | `candidato.race01@qa.example.test` a `...race05@qa.example.test` | `candidato` | 5 | Pruebas de colisión y condición de carrera en contratación al último puesto (`CP-19`). | Seeder transaccional de pruebas de carga. |

---

## 3. Condiciones lógicas de vacantes (Empleos)

Para validar exhaustivamente las 7 reglas de disponibilidad del sistema (`RF-04`, `RF-05`, `RF-06`, `CP-07` a `CP-11`), se definen las **12 condiciones lógicas posibles de una vacante**, detallando su estado interno y su método de preparación:

| ID Lógico | Condición de negocio | Estado Moderación (`revisiones_vacante`) | Estado Pago (`pagos_vacante`) | Estado Empresa (`usuarios`) | Estado Empleo (`empleos`) | Fechas Vigencia | Soft Delete | Cupos vs Contratados | ¿Visible en Búsqueda? | ¿Admite Postulación? | Método de Preparación / Origen |
|---|---|---|---|---|---|---|---|---|---|---|---|
| `VAC-BORRADOR` | Registrada inicial | Pendiente (sin fila) | Sin pago (sin fila) | Activa | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) | Creada por API `POST /empresas/me/vacantes`. |
| `VAC-RECHAZADA` | No aprobada admin | `decision = 'no_aprobada'`, motivo registrado | Sin pago | Activa | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) | Creada y rechazada con `PATCH /admin/vacantes/{id}/no-aprobar`. |
| `VAC-APROB-SIN-PAGO` | Aprobada pendiente pago | `decision = 'aprobada'` | Sin pago (sin fila) | Activa | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) | Aprobada con `PATCH /admin/vacantes/{id}/aprobar`. |
| `VAC-PAGO-FALLIDO` | Pago rechazado | `decision = 'aprobada'` | `resultado = 'fallido'` | Activa | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) | Pago simulado fallido mediante `POST .../pago`. |
| `VAC-DISPONIBLE` | **Publicada y disponible** | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | `CURDATE() BETWEEN inicio AND fin` | NULL | 0 contratados < 2 puestos | **Sí** | **Sí** (201) | Flujo completo: registro -> aprobación -> pago exitoso. |
| `VAC-PAUSADA` | Pausada por empresa | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `pausada` | Vigente | NULL | 0 / 2 | **No** (Histórica) | **No** (404) | `PATCH /empresas/me/vacantes/{id}/pausar`. |
| `VAC-FINALIZADA` | Finalizada por empresa | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `finalizada` | Vigente | NULL | 0 / 2 | **No** (Histórica) | **No** (404) | `PATCH /empresas/me/vacantes/{id}/finalizar`. |
| `VAC-VENCIDA` | Fuera de vigencia | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | `fin < CURDATE()` | NULL | 0 / 2 | **No** (Histórica) | **No** (404) | Creada con `fecha_fin_vigencia` en el pasado. |
| `VAC-VIGENCIA-FUTURA` | Convocatoria futura | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | `inicio > CURDATE()` | NULL | 0 / 2 | **No** | **No** (404) | Creada con `fecha_inicio_vigencia` en fecha posterior. |
| `VAC-COMPLETA` | Cupo agotado | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | Vigente | NULL | 2 contratados == 2 puestos | **No** (Histórica) | **No** (404) | Contratando hasta igualar `numero_vacantes`. |
| `VAC-EMPRESA-SUSP` | Empresa suspendida | `decision = 'aprobada'` | `resultado = 'exitoso'` | Suspendida | `activa` | Vigente | NULL | 0 / 2 | **No** | **No** (404) | Modificando `estado_cuenta = 'suspendido'` en usuario empresa. |
| `VAC-ELIMINADA` | Eliminada lógicamente | `decision = 'aprobada'` | `resultado = 'exitoso'` | Activa | `activa` | Vigente | NOT NULL | 0 / 2 | **No** (Histórica postulantes) | **No** (404) | `DELETE /empresas/me/vacantes/{id}` (Soft delete). |

---

## 4. Condiciones lógicas de postulaciones

| ID Lógico | Estado en base de datos (`estado_postulacion_id`) | Nombre de estado | Marca de retiro (`retirada_en`) | Acciones permitidas por la empresa | Acciones permitidas por el candidato | Estado final |
|---|---|---|---|---|---|---|
| `POS-RECIBIDO` | 1 | `Recibido` | NULL | Avanzar a `En Revisión` | Retirar | No |
| `POS-REVISION` | 2 | `En Revisión` | NULL | Avanzar a `Entrevista`, Rechazar con motivo | Retirar | No |
| `POS-ENTREVISTA`| 3 | `Entrevista` | NULL | Contratar (si hay cupo), Rechazar con motivo | Retirar | No |
| `POS-CONTRATADO`| 4 | `Contratado` | NULL | Ninguna (Bloqueado) | Ninguna (Bloqueado el retiro) | **Sí** |
| `POS-RECHAZADO` | 5 | `Rechazado` | NULL | Ninguna (Bloqueado) | Ninguna (Bloqueado el retiro) | **Sí** |
| `POS-RETIRADA`  | 1, 2 o 3 (Conserva etapa previa) | `Recibido`, `En Revisión` o `Entrevista` | NOT NULL (Timestamp) | Ninguna (`APPLICATION_WITHDRAWN`) | Ninguna (No permite repostular al mismo empleo) | **Sí (Operativo)** |

---

## 5. Catálogos base de soporte (Inmutables en pruebas)

- **Estados de postulación** (`EstadoPostulacionSeeder`):
  - 1 = `Recibido`
  - 2 = `En Revisión`
  - 3 = `Entrevista`
  - 4 = `Contratado`
  - 5 = `Rechazado`
- **Motivos de rechazo** (`MotivoRechazoSeeder`):
  - 1 = `Perfil no se ajusta`
  - 2 = `Expectativa salarial alta`
  - 3 = `Proceso cerrado`
- **Roles del sistema** (`RolSeeder`):
  - 1 = `admin`
  - 2 = `empresa`
  - 3 = `candidato`

---

## 6. Procedimientos de aislamiento, limpieza y restauración

### 6.1 Alcance exacto de `QaCleanupSeeder`
Es crítico notar que `database/seeders/QaCleanupSeeder.php` **no realiza un vaciado indiscriminado de la base de datos**. Su diseño de seguridad establece que:
- Elimina **únicamente** las cuentas de prueba autorizadas (`admin@qa.example.test`, `empresa1/2@qa.example.test`, `candidato1/2/3@qa.example.test`) y sus perfiles directos, tokens y solicitudes de password.
- **Aborta con excepción si detecta dependencias funcionales**: Si una ejecución de prueba creó vacantes vinculadas a `empresa1` o postulaciones vinculadas a `candidato1`, `QaCleanupSeeder` **no las elimina y detiene su ejecución** para evitar estados huérfanos o corrupción de datos.

### 6.2 Estrategia de limpieza y restauración según la suite de pruebas

| Tipo de suite | Entorno de BD | Mecanismo de Aislamiento y Limpieza | Procedimiento de Restauración |
|---|---|---|---|
| **PHPUnit Unit & Feature** | SQLite en memoria (`:memory:`) | Transacciones en memoria automáticas (`RefreshDatabase`). Cada prueba corre en aislamiento total sin persistencia en disco. | Automático al finalizar el proceso PHPUnit. |
| **PHPUnit MySQL Integración** | MySQL `portalempleoshu04testing` | `DatabaseTransactions` en cada clase de prueba, o truncado ordenado de tablas transaccionales (`postulaciones`, `pagos_vacante`, `revisiones_vacante`, `empleos`). | Ejecución controlada de migraciones testing: `php artisan migrate --env=testing`. |
| **Suites Funcionales / E2E (Karate, Selenium, Appium, JMeter)** | MySQL `portal_empleos_calidad_qa` | **Restauración estructural limpia**: Cuando una suite genere vacantes o postulaciones que bloqueen a `QaCleanupSeeder`, el procedimiento estándar de reset es refrescar la base de datos de QA: | ```bash
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan db:seed --class=QaSeeder
``` |
| **Restablecimiento rápido de usuarios sin dependencias** | MySQL `portal_empleos_calidad_qa` | Ejecutar `QaCleanupSeeder` y luego `QaSeeder`. | ```bash
docker compose exec app php artisan db:seed --class=QaCleanupSeeder
docker compose exec app php artisan db:seed --class=QaSeeder
``` |
