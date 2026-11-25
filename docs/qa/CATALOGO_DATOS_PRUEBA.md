# Catálogo de Datos Ficticios de Prueba — QA-04

- **Proyecto**: Portal de Empleos CREO+ (Web, PWA, APK Android y API REST) — Calidad de Software.
- **Responsable**: R3 (@luisvalenzuela25).
- **Validación**: Verificación técnica y evidencia.
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.4.0 (Datos verificados en QA-05).
- **Fecha de versión**: 25/11/2026.
- **Rama de actualización**: docs/QA-04-trazabilidad-post-QA-05.

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

### 6.1 Alcance exacto y restricciones de `QaCleanupSeeder`
El seeder `database/seeders/QaCleanupSeeder.php` incorpora una protección estricta contra vaciados indiscriminados de la base de datos:
- Elimina **únicamente** las cuentas de prueba autorizadas (`admin@qa.example.test`, `empresa1/2@qa.example.test`, `candidato1@qa.example.test`, `candidato2@qa.example.test`, `suspendido@qa.example.test`) y sus tokens y perfiles directos.
- **Aborta intencionalmente si existen dependencias funcionales**: Si una ejecución de prueba creó vacantes vinculadas a `empresa1` o postulaciones vinculadas a `candidato1`, `QaCleanupSeeder` **no las elimina y detiene su ejecución** para evitar registros huérfanos o corrupción de datos.

### 6.2 Estrategia de limpieza por suite sin comandos destructivos no controlados

Para no comprometer evidencias de otros integrantes ni vaciar tablas sin comprobación, se establecen los siguientes mecanismos por tipo de prueba:

> **Estado de preparación:** las seis cuentas son fixtures documentados por QA-02, cuya existencia local debe verificarse. Las doce condiciones de vacantes, las postulaciones por etapa y las cuentas de carga son datos diseñados: su disponibilidad no se acredita por esta documentación. Cada suite debe preparar, verificar y registrar sus propios datos antes de ejecutar.

#### A. Pruebas Unitarias y Feature (Laravel)
- **Aislamiento**: Utilizan SQLite en memoria (`:memory:`).
- **Limpieza**: Aislamiento mediante SQLite en memoria y los mecanismos de preparación y limpieza definidos por cada clase. Verificar el uso efectivo de RefreshDatabase, migraciones o creación explícita de esquema antes de atribuir un mecanismo común a toda la suite.

#### B. Pruebas MySQL de Integración
- **Aislamiento**: Conexión exclusiva a `portalempleoshu04testing`; desde el host se usa el puerto publicado 3307 y entre contenedores `mysql:3306`.
- **Verificación de conexión previa obligatoria**:
```bash
docker compose exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=mysql \
  -e DB_PORT=3306 \
  -e DB_DATABASE=portalempleoshu04testing \
  -e DB_URL= \
  app php artisan tinker --execute='dump(DB::selectOne("SELECT DATABASE() AS nombre"));'
```
- **Limpieza por preparar/verificar** (no acreditada como implementada en esta revisión):
  - En pruebas secuenciales: verificar por clase si se aplica `DatabaseTransactions` y si sus operaciones usan la misma conexión. Solo entonces puede esperarse rollback al finalizar cada método (R1, QA-02/QA-27).
  - En pruebas de concurrencia: **procedimiento propuesto, pendiente de implementación y verificación** por R1/R3 en QA-24/QA-25/QA-27. Los procesos independientes no quedan cubiertos por el rollback de la conexión principal. Registrar entidades e IDs por ejecución, detener sus procesos y eliminar únicamente sus datos en el orden real de claves foráneas, incluido el historial y demás dependencias. No se presupone que exista un script ni que un truncado global sea apropiado. El comando `php artisan migrate --env=testing` se reserva exclusivamente para la preparación inicial del esquema estructural, no para la limpieza de datos.

#### C. Suites Funcionales y E2E (Karate, Selenium, Appium, JMeter)
- **Principio de no colisión**: Las pruebas deben crear sus entidades con identificadores de ejecución únicos y registrar los IDs creados. **Diseño propuesto, no mecanismo acreditado como disponible**: R1 prepara fixtures Karate en QA-14; R2/R3 coordinan los datos E2E en QA-18/QA-21; R3 prepara datos de carga y restauración en QA-24/QA-25. Un prefijo por sí solo no evita interferencias en cuentas compartidas ni la revocación de sus tokens: reservar cuentas por ejecución o serializar las suites que comparten fixtures.
- **Restablecimiento rápido de usuarios sin dependencias**:
Si solo se utilizaron las cuentas base y no se generaron vacantes ni postulaciones persistentes:
```bash
docker compose exec app php artisan db:seed --class=QaCleanupSeeder
docker compose exec app php artisan db:seed --class=QaSeeder
```
- **Reconstrucción estructural en entorno desechable exclusivo**:
El comando `migrate:fresh` es potencialmente destructivo y **nunca debe ejecutarse en caliente ni asumirse como paso automático entre pruebas**. Solo se permite como reset inicial de un entorno local desechable bajo las siguientes tres comprobaciones previas obligatorias:
1. Confirmar que la base y su volumen son desechables, exclusivos de esta ejecución y no contienen evidencias que deban conservarse; comprobar que no hay otras suites ni usuarios activos.
2. Verificar que la base de datos conectada sea estrictamente la base aislada de QA:
```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan tinker --execute='dump(DB::selectOne("SELECT DATABASE() AS nombre"));'
```
*(El resultado debe ser estrictamente `portal_empleos_calidad_qa`)*.
3. Solo tras confirmar lo anterior, proceder a la reconstrucción en frío:
```bash
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan db:seed --class=QaSeeder
```

---

## 7. Datos adicionales para APK Android y Appium

| Identificador | Dato o recurso | Finalidad | Estado |
|---|---|---|---|
| MOB-APK-01 | APK de primera version | Instalacion y apertura | Pendiente |
| MOB-APK-02 | APK de segunda version | Actualizacion entre versiones | Pendiente |
| MOB-DEV-01 | Emulador o dispositivo Android | Ejecucion Appium | Pendiente |
| MOB-USR-01 | Candidato activo reservado | Flujo E2E | Pendiente de reserva |
| MOB-VAC-01 | Vacante disponible exclusiva | Postulacion movil | Pendiente de creacion |
| MOB-VAC-02 | Vacante disponible exclusiva | Cancelacion y confirmacion nativa | Pendiente de creacion |
| MOB-NET-01 | Condiciones de red controladas | Pruebas offline y recuperacion | Pendiente |

Cada APK debe identificarse mediante:

- Commit de origen.
- Identificador de aplicacion Android.
- VersionCode y VersionName.
- Hash SHA-256.
- Configuracion del ambiente.
- Tipo de compilacion.

Para comprobar una actualizacion, ambos APK deben conservar
el mismo identificador de aplicacion y una firma compatible,
con VersionCode creciente.

Las pruebas moviles deben reservar cuentas y entidades
exclusivas o serializar su ejecucion cuando compartan datos.

Los datos transaccionales se limpiaran siguiendo el
procedimiento controlado de la suite correspondiente.

No ejecutar QaCleanupSeeder esperando que elimine
dependencias funcionales que no esta autorizado a borrar.

## 8. Datos de carga JMeter

Las 25 cuentas inicialmente propuestas corresponden
al perfil objetivo inicial de 25 usuarios virtuales.

QA-24 debe preparar suficientes cuentas unicas para cada
perfil de carga que se apruebe.

Si se ejecuta el escenario exploratorio de 50 usuarios
autenticados simultaneamente, deben existir al menos
50 cuentas independientes cuando cada usuario requiera
credenciales propias.

Los CSV no deben contener credenciales reales ni secretos
versionados. La generacion y limpieza se documentaran
por ejecucion.

## 9. Identificadores Jira

Las claves Jira se registraran una vez creados los tickets.

No utilizar identificadores ficticios como evidencia real.

Cada registro de ejecucion relacionara el escenario CP,
control RUB, ticket Jira, ambiente, datos, resultado
y reporte correspondiente.


<!-- QA04-POST-QA05 -->

## 10. Datos utilizados en QA-05

| Fixture | Uso verificado |
|---|---|
| USR-CND-01 | Login, perfil, permisos y logout |
| USR-CND-02 | Suspensión temporal y reactivación |
| USR-CND-03 | Rechazo de login por suspensión |
| USR-EMP-01 | Login, perfil y logout empresa |
| USR-ADM-01 | Login y administración |

### Condiciones de ejecución

- API QA: http://127.0.0.1:8002.
- Frontend QA: http://127.0.0.1:4201.
- Base funcional: portal_empleos_calidad_qa.
- Base integración: portalempleoshu04testing.
- Unit/Feature: SQLite en memoria.
- Credenciales parametrizadas o introducidas de forma
  interactiva sin mostrarlas en los logs.

### Aislamiento y limpieza

- Utilizar contextos independientes de navegador.
- Resolver identificadores dinámicamente.
- No compartir tokens entre usuarios.
- Reservar las cuentas mutables durante la ejecución.
- Restaurar USR-CND-02 al finalizar.
- Mantener USR-CND-03 suspendido.
- Revocar tokens emitidos durante la prueba.
- No ejecutar limpieza destructiva sobre datos ajenos.

La ejecución final de QA-05 confirmó la reactivación
de USR-CND-02 y cero tokens pendientes de revocación
registrados por el runner.

Una ejecución previa fue interrumpida. La limpieza final
no acredita retrospectivamente aquella ejecución.

Las suites futuras deben documentar su propio
procedimiento de preparación y restauración de datos.

## Anexo QA-06 - Fixtures de registro

<!-- QA06-DATOS -->

- Correos sinteticos con dominio example.test.
- DNI, celular y RUC generados para la suite.
- Identificadores de catalogo consultados dinamicamente.
- Contrasena sintetica exclusiva de integracion.
- Seeders de roles, estudios, areas, idiomas y habilidades.
- Preparacion dentro de transacciones aisladas.
- Reversion al finalizar cada prueba.

Base de integracion: portalempleoshu04testing.

No utilizar datos personales ni credenciales reales.

## Anexo QA-08 - Datos de postulacion

<!-- QA08-DATOS -->

- Candidatos sinteticos con correos example.test.
- Vacante aprobada y pagada mediante simulacion.
- Vacante con un puesto para pruebas concurrentes.
- Tokens Sanctum temporales.
- Estados iniciales, intermedios y terminales.
- Vacantes pausadas y eliminadas.
- Conexiones MySQL independientes.
- Limpieza de fixtures y tokens al finalizar.

Base aislada: portalempleoshu04testing.

## Anexo QA-09 - Datos de prueba

<!-- QA09-DATOS -->

- Empresas y candidatos sinteticos.
- Correos example.test.
- Vacantes con uno y dos puestos.
- Cinco estados de postulacion.
- Motivo de rechazo sintetico.
- Tokens temporales de empresa y candidato.
- Conexiones MySQL independientes.
- Notificaciones aisladas mediante mocks.
- Limpieza de fixtures, movimientos y tokens.

Base aislada: portalempleoshu04testing.
