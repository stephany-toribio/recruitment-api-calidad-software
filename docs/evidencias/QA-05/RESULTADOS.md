# QA-05 — Autenticación, sesiones y permisos

Fecha de ejecución reportada: 24/11/2026 (America/Lima).
Issue: https://github.com/stephany-toribio/recruitment-api-calidad-software/issues/14
Rama API: `feature/QA-05-autenticacion-permisos`.
Base de trabajo inicial: `8c96afd`.

## Identificación de la ejecución

Las ejecuciones fueron realizadas por el implementador en Windows y reportadas
durante la preparación de QA-05. Las verificaciones corresponden al árbol de
trabajo con los cambios de esta tarea, antes de su commit final. El commit que
incorpora este informe y los commits coordinados de API/frontend deben quedar
identificados en los PR. Este informe no acredita su integración ni aprobación.

Dependencia directa: QA-02, previamente integrada mediante PR API #30.
Responsable original: R1; revisión cruzada indicada por la issue: R2.

## Entorno y aislamiento

- PHP 8.3.37; PHPUnit 11.5.56; MySQL 8.0 en Docker Desktop.
- API manual: `http://localhost:8002/api/v1`.
- API y frontend a través de Nginx: `http://127.0.0.1:4202/api/v1`.
- `phpunit.xml`: SQLite `:memory:`, ambiente `testing`.
- `phpunit.mysql.xml`: MySQL, host `mysql`, base `portalempleoshu04testing`.
- Fixtures para navegador: base distinta, `portal_empleos_calidad_qa`.
- Antes de preparar MySQL se comprobó la base efectiva y se ejecutaron sus
  migraciones pendientes. No se utilizó `migrate:fresh`, `db:wipe` ni QaSeeder
  sobre la base de integración.
- Las suites SQLite crean usuarios exclusivos en memoria y ejecutan las
  migraciones reales necesarias para autenticación.
- La suite MySQL verifica ambiente, configuración no cacheada, host y nombre
  efectivo de base antes de iniciar una transacción. Los datos creados se
  revierten al finalizar cada prueba o al fallar su preparación.
- No se desactivaron claves foráneas ni se eliminaron datos de la API manual.

## Resultados automatizados

| Ejecución | Pruebas | Aserciones | Resultado |
| --- | ---: | ---: | --- |
| Línea base inicial de Auth, antes de cambios | 15 | 69 | PASS |
| Regresión final de Auth | 55 | 573 | PASS |
| Propiedad MySQL final | 19 | 157 | PASS |
| Regresión ampliada Unit/Feature final | 259 | 1635 | PASS |

La regresión ampliada incluye las 55 pruebas de Auth. Por tanto, las dos
ejecuciones finales distintas suman **278 pruebas y 1792 aserciones**:
259 Unit/Feature más 19 MySQL. No se suman otra vez las ejecuciones parciales.
No se declara ejecutada toda la suite MySQL del repositorio.

Se añadieron 59 pruebas parametrizadas de QA-05:
18 de autenticación, 14 de suspensión, 8 de contraseña y 19 de propiedad.

### Reproducción

Desde la raíz de la API, con los servicios encendidos y las bases preparadas:

```powershell
docker compose exec app php vendor/bin/phpunit tests/Feature/Auth --testdox
docker compose exec app php vendor/bin/phpunit --configuration phpunit.mysql.xml tests/MySql/Qa05PropiedadMySqlTest.php --testdox
docker compose exec app php vendor/bin/phpunit --colors=never
git diff --check
```

El log conservado de la regresión ampliada es [unit-feature.txt](unit-feature.txt).
Las salidas parciales y MySQL fueron revisadas durante la ejecución; no se
atribuyen archivos de log que no se hayan guardado.

## Cobertura de criterios

| Criterio QA-05 | Evidencia técnica |
| --- | --- |
| Credenciales válidas e inválidas, validaciones y bloqueo | Login real de candidato/empresa/admin; 422 de validación; 401 por credenciales; quinto fallo 429; quince minutos; rechazo durante bloqueo; recuperación y reinicio de contador |
| Identidad y permisos resueltos por API | Identidad obtenida con token real; rol enviado por cliente no eleva permisos; perfiles propios y ajenos comprobados |
| 401 sin token/inválido y 403 por rol/propiedad | Pruebas existentes de Sanctum y nuevas pruebas de Auth y MySQL; sin mutación de los recursos ajenos comprobados |
| Logout, revocación y suspensión | Token actual revocado por logout; otras sesiones conservadas; suspensión administrativa revoca sesiones del afectado; reactivación no restaura tokens; nuevo login permitido |
| Correcciones y regresión | Dos defectos backend corregidos y cubiertos; defecto de mensaje frontend con retest manual documentado en el repositorio frontend |

La verificación del frontend, sus confirmaciones manuales y límites se detallan
en `docs/evidencias/QA-05/RESULTADOS.md` del repositorio frontend.

## Contratos observados y cubiertos por pruebas

Estas decisiones técnicas se documentan para la revisión contractual de R1 y
para sincronizar las referencias que permanecían pendientes en QA-04. No se
marcan aprobadas todas las variantes de una familia CP por cubrir un escenario.

| Referencia | Comportamiento comprobado |
| --- | --- |
| CP-03-01/02/03 | Login 200 con token persistido e identidad y rol del servidor |
| CP-04-01 | Password incorrecto: 401, success false, mensaje de credenciales incorrectas, contador incrementado y ningún token nuevo |
| CP-04-02 | Quinto fallo: 429 y bloqueo de quince minutos; durante bloqueo se rechaza incluso el password correcto sin emitir token ni aumentar intentos; después del vencimiento se permite autenticar |
| CP-04-03 | Login de cuenta suspendida: 403 y mensaje explícito, sin emitir token |
| CP-05 | Logout 200; reutilización del token revocado 401; token falso 401; logout conserva tokens de otras sesiones |
| CP-06-01 | Perfil consultado desde la identidad del token |
| CP-06-02 | Edición de campos propios; la prueba modifica nombre y comprueba que el otro perfil permanece intacto |
| CP-06-03 | Perfil por postulación: inexistente 404 RESOURCE_NOT_FOUND; postulación de otra empresa 403 FORBIDDEN; empresa propietaria autorizada 200 |
| CP-06-04 | Identificadores y rol no validados en edición de perfil se ignoran; 200 sobre el perfil del token, sin modificar el usuario/perfil ajeno |
| CP-15-01 | Consulta de postulaciones sin token: 401 UNAUTHENTICATED |
| CP-15-02 | Operación real de empresa invocada por candidato: 403 FORBIDDEN. Se usa pausar una vacante, no el GET de listado descrito originalmente en el catálogo |
| CP-15-03 | Pausar, reanudar, finalizar o eliminar vacante ajena: 403 FORBIDDEN, conservando la fila de la vacante |
| CP-15-04 | candidato_id en el body de postulación: 422 VALIDATION_ERROR sin inserciones; petición válida sin ese campo crea para el candidato del token |

El cambio de contraseña correcto revoca todas las sesiones del usuario y
conserva las de otros usuarios. Contraseña actual incorrecta o confirmación
inválida no cambia el hash ni revoca el token.

## Defectos reproducidos y correcciones

### QA05-B01 — Estado suspendido no aplicado a rutas de identidad

Precondición: usuario activo con token real, posteriormente suspendido mediante
cambio directo de estado en la base de prueba sin eliminar ese token.

- Antes: `GET /auth/me` y `GET /auth/usuarios/me` respondían 200.
- Esperado: rechazar la cuenta actualmente suspendida.
- Corrección: aplicar el middleware existente `rol:candidato,empresa,admin` a
  las rutas de identidad y cambio de contraseña, después de `auth:sanctum`.
- Logout sigue disponible para un token existente de una cuenta suspendida.
- Retest: cuatro variantes de identidad devuelven 403; dos variantes de
  contraseña suspendida devuelven 403 sin cambiar el hash.
- La suspensión administrativa normal ya revocaba correctamente los tokens;
  el defecto no se atribuye a ese mecanismo.

### QA05-B02 — Empresa sin vínculo consulta un perfil por ruta directa

- Acción: empresa sin postulación vinculada solicita `GET /candidatos/{id}`.
- Antes: 200 y datos del candidato.
- Corrección: administrador conserva acceso; empresa requiere una postulación
  no retirada a una vacante propia no eliminada.
- La relación `Postulacion::empleo()` usa `withTrashed()`: se añadió el filtro
  explícito `empleos.deleted_at IS NULL` únicamente en la autorización de esta
  ruta. La relación global y las consultas históricas se conservan.
- Retest: empresa sin vínculo, postulación retirada o vacante eliminada reciben
  403; empresa vinculada y administrador reciben 200.
- La consulta específica del perfil por postulación conserva sus contratos
  propios; no se uniformaron indiscriminadamente todos los rechazos.

### Incidencias de preparación de pruebas, no defectos de aplicación

- Reloj congelado con microsegundos frente a persistencia a segundos: se fijó
  el reloj de prueba a segundos completos, manteniendo los quince minutos.
- Comparación de modelo recién creado frente a fila recargada: se capturó
  también la fila inicial mediante `fresh()` para incluir los defaults.
- Fixture con literal `recibido`: se reemplazó por la constante del modelo
  `EstadoPostulacion::RECIBIDO`.

## Archivos de implementación y pruebas

- `routes/api_v1/auth.php`.
- `app/Http/Controllers/Api/V1/CandidatoController.php`.
- `tests/Feature/Auth/Qa05AutenticacionTest.php`.
- `tests/Feature/Auth/Qa05SuspensionTest.php`.
- `tests/Feature/Auth/Qa05PasswordTest.php`.
- `tests/MySql/Qa05PropiedadMySqlTest.php`.

## Límites y cierre

- No se implementaron despliegue, APK ni suites completas de otras tareas.
- La regresión SQLite no acredita todo el esquema MySQL; los endpoints de
  propiedad sí se ejecutaron sobre las migraciones MySQL completas.
- El manejo frontend y las confirmaciones visuales realizadas están registrados
  en su informe. El reporte inicial de pérdida de sesión admin no se reprodujo
  en el último intento: suspensión HTTP 200 y navegación posterior funcional,
  según confirmación del implementador. No se atribuye una causa no demostrada.
- La auditoría histórica de frontend reportó 43 alertas, incluidas dos críticas.
  No se ejecutó una auditoría nueva como parte de esta revisión ni se resolvieron
  esas alertas dentro de QA-05.
- La cuenta manual candidato2 se bloqueó intencionalmente para comprobar 429;
  el implementador confirmó la recuperación de acceso después del plazo.
- El candidato1 reactivado ingresó y mostró su perfil. Tras la última
  reproducción de suspensión del candidato1, reactivarlo nuevamente si quedó
  suspendido y cerrar las sesiones utilizadas antes de finalizar.
- Solicitar revisión de otro integrante, identificar commits coordinados,
  integrar los PR y sincronizar issue/Project/Jira según las reglas vigentes.
- Este informe no declara QA-05 cerrada ni sustituye la revisión contractual de
  los escenarios de QA-04 que estaban pendientes.


## Regresión complementaria en WSL2

Fecha: 24/11/2026 (America/Lima).

Base de implementación: 15592dc.
Rama: feature/QA-05-autenticacion-permisos.

Se recuperó la implementación original de QA-05 y se
incorporó la variante GET del escenario CP-15-02.

### Aislamiento comprobado

- API QA: HTTP 200 en localhost:8002/up.
- MySQL saludable.
- Base funcional: portal_empleos_calidad_qa.
- Base de integración: portalempleoshu04testing.
- Migraciones de integración ejecutadas.

### Resultados

| Suite | Pruebas | Aserciones | Tiempo | Estado |
|---|---:|---:|---:|---|
| CP-15-02 GET | 1 | 9 | 0.479 s | PASS |
| Propiedad MySQL | 20 | 166 | 2.689 s | PASS |
| Autenticación | 55 | 573 | 1.484 s | PASS |
| Unit/Feature | 259 | 1635 | 4.626 s | PASS |

Total sin duplicar ejecuciones: 279 pruebas y 1801
aserciones aprobadas.

Auth está incluida dentro de Unit/Feature.
La prueba GET está incluida dentro de Propiedad MySQL.

No se declara ejecutada toda la suite MySQL.

### Ampliación CP-15-02

Se comprobaron dos operaciones reservadas al rol empresa:

- GET /api/v1/empresas/me/vacantes.
- PATCH /api/v1/empresas/me/vacantes/{id}/pausar.

Ambas responden 403 FORBIDDEN cuando el solicitante
es un candidato autenticado.

La prueba GET adicional verifica también la ausencia
de datos de respuesta y que la vacante no se modifica.

### Contratos QA-04

Se actualizaron los contratos de CP-04-01, CP-04-02,
CP-06-03, CP-06-04 y CP-15-04 en el catálogo oficial.

Los escenarios mantienen su estado pendiente para las
suites académicas de automatización.

### Evidencias reproducibles

- [CP-15-02 GET](regresion-wsl/cp15-02-get.txt)
- [Propiedad MySQL](regresion-wsl/mysql-propiedad.txt)
- [Autenticación](regresion-wsl/auth-regresion.txt)
- [Unit/Feature](regresion-wsl/unit-feature.txt)
- [SHA256 de evidencias](regresion-wsl/SHA256SUMS.txt)

### Condición de integración

No se exige revisión cruzada formal entre integrantes.
Se mantienen la validación técnica, el registro de
evidencias, la trazabilidad y la integración en develop.

La parte frontend de QA-05 se valida de forma
coordinada mediante su PR correspondiente.

Los defectos y resultados deben incorporarse a Jira
mediante QA-29 cuando esté disponible su estructura.


## Contrato específico de invalidación de sesión

Se amplió RolMiddleware para distinguir una cuenta
no habilitada de una restricción normal de permisos.

Se conserva HTTP 403 y error_code FORBIDDEN.
Cuando la cuenta autenticada no está habilitada,
se incorpora reason ACCOUNT_NOT_ACTIVE.

El frontend utiliza este motivo para invalidar
exclusivamente la sesión asociada a la petición.

### Regresión posterior al cambio

| Suite | Pruebas | Aserciones | Resultado |
|---|---:|---:|---|
| Auth | 55 | 583 | PASS |
| Unit/Feature | 259 | 1645 | PASS |
| Propiedad MySQL | 20 | 166 | PASS |
| Total sin duplicar | 279 | 1811 | PASS |

Auth está incluida en Unit/Feature.

Se incorporaron diez aserciones adicionales.
No se registraron fallos en las suites ejecutadas.

### Evidencias

- [Auth](regresion-sesiones/auth-reason.txt)
- [Unit/Feature](regresion-sesiones/unit-feature-reason.txt)
- [MySQL](regresion-sesiones/mysql-reason.txt)
- [Hashes](regresion-sesiones/SHA256SUMS.txt)

La integración requiere coordinar las versiones
de backend y frontend.
