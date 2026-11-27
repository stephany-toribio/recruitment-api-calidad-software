# Portal de Empleos Web, PWA y Android — API — Calidad de Software

Laravel 12, Sanctum 4, PHP y MySQL. Esta copia soporta el flujo de negocio de la entrega web, PWA y APK Android híbrido.

Frontend del mismo proyecto: [web-app-calidad-software](https://github.com/stephany-toribio/web-app-calidad-software).

## Objetivo y delimitación

Este proyecto del curso Calidad de Software evalúa el Portal de Empleos CREO+ con datos persistentes, flujos de aprobación, permisos y pruebas reproducibles. La entrega incluye una **PWA instalable y un APK Android híbrido mediante Capacitor** y automatización con **Karate, JMeter, Selenium y Appium**.

La base de trabajo es `develop`. `main` se reserva para las entregas estables y ahora conserva una base anterior. El código ya existente se verifica antes de modificarlo: una pantalla, endpoint o test versionado no acredita por sí solo una ejecución aprobada.

### Requisitos incluidos

| ID | Alcance |
| --- | --- |
| RF-01 | Registro real de candidatos y empresas, validación y unicidad |
| RF-02 | Autenticación, roles, sesión, bloqueo/suspensión y logout |
| RF-03 | Consulta y actualización del perfil propio |
| RF-04 | Registro y gestión de vacantes de la empresa propietaria |
| RF-05 | Aprobación/no aprobación administrativa y pago simulado |
| RF-06 | Búsqueda, detalle y reglas de disponibilidad |
| RF-07 | Postulación persistente y prevención de duplicados |
| RF-08 | Revisión, entrevista, contratación/rechazo y cupos |
| RF-09 | Seguimiento y retiro propio en estados permitidos |
| RF-10 | Historial de decisiones, fechas y motivos |
| PWA-01 | PWA y APK instalables y apertura en Android |
| PWA-02 | Caché pública web, comportamiento offline y escrituras solo en línea en PWA y APK |
| PWA-03 | Actualización, reapertura y recuperación diferenciadas de PWA y APK |
| PWA-04 | Diseño móvil, accesibilidad, selectores estables e interacción nativa en APK |
| RNF-01 | Autorización del servidor y protección de sesiones/datos |
| RNF-02 | Integridad transaccional y concurrencia |
| RNF-03 | Rendimiento bajo carga controlada |
| RNF-04 | Reproducibilidad, CI y trazabilidad de la entrega |

### Fuera del alcance de esta entrega

- Matching con IA, rediseño del algoritmo o integración con un tercer servicio Python.
- Desarrollo de un frontend móvil independiente, publicación obligatoria en tiendas y soporte específico de iOS. El APK híbrido mediante Capacitor sí está incluido.
- Pasarela bancaria, cobros reales y datos reales de tarjetas.
- Postulaciones, pagos o decisiones sin conexión y colas automáticas de escrituras.
- Nuevos módulos de analítica, lector de CV, auditoría avanzada o ampliación de catálogos que no sean necesarios para el flujo evaluado.

Las funciones adicionales ya presentes —por ejemplo, vacantes guardadas o recuperación de contraseña— se conservan. Se revisará su regresión cuando una modificación las afecte, sin ampliar por ello el flujo obligatorio de la entrega.

## Flujo completo del sistema

| Paso | Actor | Operación y resultado | Condiciones o alternativa |
| --- | --- | --- | --- |
| 1 | Candidato y empresa | Se registran y se comprueba persistencia de sus perfiles | Campos y formatos válidos; correo/DNI/RUC únicos según contrato |
| 2 | Los tres roles | Inician sesión y acceden a su área | El rol y la autorización los decide la API; errores, bloqueo y suspensión se verifican |
| 3 | Empresa | Registra una vacante propia | Datos de la oferta, vigencia y número de puestos válidos |
| 4 | Administrador | Revisa y aprueba o no aprueba la vacante | Una vacante no aprobada se corrige antes de volver a revisión según el comportamiento vigente |
| 5 | Empresa propietaria | Completa el pago simulado de una vacante aprobada | Datos inválidos o un pago repetido se rechazan; no hay cobro real |
| 6 | Sistema | Publica una vacante disponible | Aprobación vigente, pago exitoso, empresa activa, estado activo, vigencia, sin eliminar y con puestos sin contratar |
| 7 | Candidato | Busca, filtra y consulta el detalle de una vacante | Las ofertas no disponibles no admiten nuevas postulaciones |
| 8 | Candidato | Postula y obtiene confirmación del servidor | El candidato sale del token; una sola postulación por candidato y empleo, incluso con concurrencia |
| 9 | Empresa propietaria | Consulta postulaciones y revisa el perfil del postulante | Otra empresa o un rol no autorizado no puede gestionar el recurso |
| 10 | Empresa propietaria | Avanza, entrevista, contrata o rechaza con motivo | Solo transiciones permitidas; contratar no puede exceder los puestos disponibles |
| 11 | Candidato | Consulta el resultado y puede retirar cuando corresponda | El retiro conserva el último estado y el historial; no habilita repostular al mismo empleo |
| 12 | Empresa y candidato | Consultan su seguimiento e historial permitido y cierran sesión | Fechas y motivos persistentes; logout revoca el token y limpia la sesión local |

### Transiciones de postulación

| Origen | Acción | Resultado |
| --- | --- | --- |
| Recibido | Avanzar | En revisión |
| En revisión | Avanzar | Entrevista |
| En revisión o Entrevista | Rechazar con motivo válido | Rechazado |
| Entrevista | Contratar, con puesto disponible | Contratado |
| Recibido, En revisión o Entrevista | Retiro por el candidato propietario | Marca de retiro; se conserva el último estado |

Contratado y Rechazado son estados finales. Se rechazan los saltos y las operaciones no permitidas sobre postulaciones retiradas. El número de puestos limita las **contrataciones**, no la cantidad de candidatos que pueden postular mientras la oferta siga disponible.

## PWA, APK Android y comportamiento móvil

La PWA y el APK Android híbrido forman parte del alcance. Ambos deben implementarse y validarse con evidencia real.

| ID | Cambio previsto | Comprobación de aceptación |
| --- | --- | --- |
| PWA-01 | Manifiesto, nombre, iconos 192/512, start_url, scope y modo standalone | Instalación y apertura desde el lanzador en Android bajo HTTPS |
| PWA-02 | Service worker para recursos estáticos y vista sin conexión | No cachear tokens, perfiles ni respuestas API privadas; explicar la falta de red |
| PWA-02 | Escrituras en línea y prevención de dobles envíos | Sin éxito ficticio ni reenvío automático de postulaciones, pagos o decisiones |
| PWA-03 | Actualización y recuperación entre builds | Aviso y navegación coherentes; sin pérdida silenciosa de formularios |
| PWA-04 | Diseño móvil y accesibilidad | Recorrido usable al menos en 360×800 y 412×915, con orientación y teclado virtual |

Si se pierde la respuesta durante una postulación, el resultado puede ser incierto: se debe consultar el estado persistente antes de reintentar y conservar la protección de unicidad de la API.

Appium se ejecutará sobre el APK generado mediante Capacitor en un dispositivo o emulador Android. Se documentarán las capabilities, UiAutomator2, los contextos NATIVE_APP y WebView, los gestos swipe y tap y el flujo funcional end-to-end. Se demostrará interacción con elementos realmente nativos. La vista móvil del escritorio no sustituye esta prueba. Los pasos manuales se distinguirán de los automatizados.

### Aplicación Android híbrida mediante Capacitor

El APK se generará reutilizando el frontend Angular existente.

- Se versionará la configuración de Capacitor y el proyecto Android.
- Se documentarán las versiones de Node.js, JDK, Android SDK y Gradle.
- El APK utilizará la API del ambiente QA autorizado mediante HTTPS.
- Se comprobarán la instalación y apertura en Android.
- Se incorporará una interacción nativa funcional.
- Appium verificará los gestos swipe y tap y los contextos disponibles.
- Se validará la persistencia de las postulaciones.
- La PWA y el APK tendrán mecanismos de actualización diferenciados.
- No se permitirá éxito ficticio ni escrituras en cola sin conexión.

Las pruebas del APK serán independientes de la comprobación
del manifiesto y service worker de la PWA.

## Plan de pruebas y evidencias

Todos los casos de la siguiente matriz están **planificados**. Su resultado se registrará al ejecutarlos; esta documentación no afirma que ya pasaron.

| Caso | Requisitos | Prueba | Resultado esperado | Herramienta |
| --- | --- | --- | --- | --- |
| CP-01 | RF-01 | Registro de candidato válido y datos inválidos o repetidos | Persistencia del perfil y errores por campo; sin registros parciales | Karate, Selenium |
| CP-02 | RF-01 | Registro de empresa y RUC/correo duplicados | Alta válida y rechazo de datos inválidos según contrato | Karate, Selenium |
| CP-03 | RF-02 | Login de candidato, empresa y administrador | Token y rol del servidor; navegación al área autorizada | Karate, Selenium, Appium |
| CP-04 | RF-02, RNF-01 | Credenciales incorrectas, bloqueo y cuenta suspendida | Rechazo y mensaje del contrato; acceso no concedido | Karate, Selenium |
| CP-05 | RF-02, RNF-01 | Logout y reutilización de token revocado/inválido | Revocación en API y limpieza de sesión; ruta protegida rechazada | Karate, Selenium, Appium |
| CP-06 | RF-03, RNF-01 | Consulta/edición propia y acceso a perfil ajeno | Persisten datos permitidos; autorización del servidor en todos los casos | Karate, Selenium |
| CP-07 | RF-04 | Creación de vacante con campos y fechas válidos/incorrectos | Validación, propiedad y persistencia sin publicación prematura | Karate, Selenium |
| CP-08 | RF-05, RNF-01 | Aprobación/no aprobación y rol incorrecto | Decisión del administrador y rechazo del actor no autorizado | Karate, Selenium |
| CP-09 | RF-05 | Pago simulado válido, inválido o duplicado | Se registra solo el intento permitido; no existe cobro bancario real | Karate, Selenium |
| CP-10 | RF-06 | Vacante sin aprobar/pagar, pausada, vencida, eliminada o completa | No admite nuevas postulaciones; listado y detalle aplican las reglas previstas | Karate |
| CP-11 | RF-06 | Búsqueda, filtros y detalle de vacante disponible | Resultados coherentes y datos de la API visibles | Karate, Selenium, Appium |
| CP-12 | RF-07 | Primera postulación de candidato autenticado | Una creación en recibido, asociada al candidato del token | Karate, Selenium, Appium |
| CP-13 | RF-07, RNF-02 | Postulación duplicada secuencial y carrera del mismo candidato | Una sola fila; en la carrera, una respuesta 201 y otra 409 | Karate, PHPUnit/MySQL, JMeter |
| CP-14 | RF-07, RNF-02 | Postulaciones simultáneas de candidatos diferentes | Se admiten si la vacante está disponible; el cupo no limita postulantes | PHPUnit/MySQL, JMeter |
| CP-15 | RNF-01 | Token ausente, rol incorrecto y recursos de otra empresa/candidato | 401/403 o respuesta del contrato sin mutaciones ni exposición indebida | Karate, Selenium |
| CP-16 | RF-08 | Recibido→En revisión→Entrevista→Contratado | Solo transiciones permitidas, cupo respetado y UI sincronizada | Karate, Selenium |
| CP-17 | RF-08 | Rechazo desde revisión/entrevista con motivo válido o ausente | Motivo validado y resultado persistente con historial | Karate, Selenium |
| CP-18 | RF-08 | Salto de estado, estado final y postulación retirada | Operación rechazada; no se altera estado ni historial | Karate |
| CP-19 | RF-08, RNF-02 | Contrataciones concurrentes al último puesto | No se supera numero_vacantes y se informa el conflicto | PHPUnit/MySQL, JMeter |
| CP-20 | RF-09 | Retiro propio, retiro ajeno o estado no permitido | Marca de retiro solo cuando corresponde; no permite repostular al mismo empleo | Karate, Selenium |
| CP-21 | RF-09 | Mis postulaciones y consulta de su vacante histórica | El candidato conserva seguimiento propio ante cambios de la vacante | Karate, Selenium |
| CP-22 | RF-10 | Consulta de historial tras decisiones y retiro | Movimientos, fechas y motivo cuando corresponde; sin pérdida de registros | Karate, Selenium |
| CP-23 | RF-01 a RF-10 | Recorrido integrado de los tres roles en web | El resultado final visible coincide con la API y la base | Selenium |
| CP-24 | PWA-01 | Instalación y apertura de PWA y APK Android | PWA standalone y APK instalable con verificaciones diferenciadas | Android, Appium |
| CP-25 | PWA-04 | Pantallas PWA y APK, orientación, teclado y foco | Acciones accesibles y sin desbordamientos que bloqueen el recorrido | Selenium, Appium |
| CP-26 | PWA-02, RNF-01 | Comportamiento offline de PWA y APK | Vista offline; sin exposición de datos privados ni almacenamiento inseguro de tokens; caché SW verificada en PWA | Android/DevTools, Appium |
| CP-27 | PWA-02, RF-07 | Corte de red antes/durante una escritura y recuperación | Sin éxito ficticio, cola automática ni duplicación; resultado incierto se consulta | Appium, API |
| CP-28 | PWA-03 | Actualización diferenciada de PWA y APK | Nueva versión usable, recuperación y formularios sin pérdida silenciosa | Android, Appium |
| CP-29 | PWA-01, PWA-04, RF-02, RF-06, RF-07 | Login, búsqueda, detalle y postulación desde APK Android | Appium con swipe, tap, interacción nativa, contextos y persistencia demostrada | Appium |
| CP-30 | PWA-02, PWA-03, RF-02 | Sesión inválida, logout y reapertura de PWA y APK | Reautenticación y mensajes coherentes; datos privados no expuestos | Appium |
| CP-31 | RNF-03 | Carga de búsqueda/listado/detalle | p95, throughput y errores medidos frente al objetivo aprobado | JMeter |
| CP-32 | RF-02, RNF-03 | Login concurrente con credenciales únicas | Correlación correcta de tokens, respuestas y tiempos | JMeter |
| CP-33 | RF-07, RF-08, RNF-02, RNF-03 | Carga de postulaciones y decisiones | Sin pérdida, duplicación ni sobrecontratación; integridad verificada al finalizar | JMeter, PHPUnit/MySQL |
| CP-34 | RNF-04 | Reproducción desde copia limpia, CI y trazabilidad Jira | Comandos repetibles, reportes, tickets vinculados y evidencia sin secretos | CI, Karate, Selenium, Appium, JMeter |

### Suites previstas

| Herramienta | Repositorio y ruta prevista | Entregable |
| --- | --- | --- |
| Karate | API: `qa/karate/` | Features positivos/negativos, Scenario Outline, esquemas JSON, tiempos, variables dinámicas, call, runner, fixtures y reporte |
| JMeter | API: `qa/jmeter/` | Thread Groups, planes JMX, CSV Data-Driven, listeners, correlación, Response/Duration Assertion, JTL y reporte HTML |
| Selenium | Frontend: `qa/selenium/` | Page Objects, selectores robustos sin XPath absolutos, esperas explícitas, recorridos end-to-end, capturas y reporte |
| Appium | Frontend: `qa/appium/` | APK Android, capabilities, UiAutomator2, contextos nativo/WebView, swipe, tap, flujo end-to-end y reporte |
| PHPUnit | API: `tests/Unit/`, `tests/Feature/`, `tests/MySql/` | Regresiones y pruebas de integridad/concurrencia; complementan las cuatro herramientas |

Las rutas `qa/*` son ubicaciones de implementación previstas; no se describen como suites ya incorporadas. Los reportes deben indicar commit, fecha, ambiente, datos y resultado, y excluir tokens, contraseñas y datos personales reales.

### Rendimiento

En QA-24 se medirá la línea base y se fijarán los umbrales de aceptación antes de ejecutar la carga de aceptación. Como objetivos iniciales de planificación: 25 usuarios virtuales, p95 de lecturas ≤2 s, p95 de login/escrituras ≤3 s, errores inesperados <1% y cero pérdida, duplicación o sobrecontratación.

La exploración de 10/25/50 usuarios se hará solo en un entorno QA aislado, con ramp-up, duración y condiciones de parada documentados. Se separarán los 4xx esperados de los errores inesperados y se informará tamaño de muestra y límites del alojamiento. Los objetivos no son resultados medidos ni promesas de capacidad.

## Cumplimiento de la rúbrica académica

La referencia de aceptación del proyecto es
[RUBRICA_EVALUACION.md](docs/RUBRICA_EVALUACION.md).

El objetivo es alcanzar el nivel Excelente en los cinco criterios:

| Criterio | Puntos máximos | Tareas principales |
|---|---:|---|
| Karate DSL | 4 | QA-14 a QA-17 |
| Apache JMeter | 4 | QA-24 a QA-26 |
| Selenium | 4 | QA-18 a QA-20 |
| Appium | 4 | QA-21 a QA-23 |
| Jira y reportes | 4 | QA-04, QA-29 a QA-31 |
| Total objetivo | 20 | Entrega integrada |

Cada prueba automatizada debe estar vinculada a un requisito,
caso de prueba, control RUB, ticket Jira, issue GitHub y reporte.

GitHub Projects mantiene la gestión técnica del desarrollo.
Jira es obligatorio para la trazabilidad académica y la gestión
de defectos y retests.

Los criterios planificados no representan resultados aprobados.
El cumplimiento se acredita mediante ejecución y evidencia real.

## Gestión y reparto para tres integrantes

El [plan maestro](https://github.com/stephany-toribio/recruitment-api-calidad-software/issues/3) contiene las ocho épicas, las 31 tareas nuevas, sus dependencias y los dos tickets documentales QA-01 de ambos repositorios.

| Rol de trabajo | Responsabilidad principal | Revisión cruzada | Puntos relativos de tareas nuevas |
| --- | --- | --- | --- |
| R1 | API y Karate | R2 | 35 |
| R2 | Web, PWA y Selenium | R3 | 33 |
| R3 | Integración, Appium y JMeter | R1 | 34 |

Las asignaciones nominales confirmadas son R1: @stephany-toribio, R2: @aquinoestoyxd y R3: @luisvalenzuela25. Las responsabilidades y dependencias se detallan en el plan maestro. Los puntos expresan tamaño relativo, no horas. Las fechas se fijarán con el plazo real y la disponibilidad del equipo.

Consultar [GESTION_PROYECTO.md](docs/GESTION_PROYECTO.md) para los hitos, estados, reglas de trabajo, índice de issues, riesgos y criterios de cierre.

### Criterios de entrega

- Los requisitos incluidos tienen casos y evidencia trazables.
- El flujo completo funciona con datos persistentes y permisos del servidor.
- Las cuatro suites se ejecutan y reportan resultados; la PWA y el APK se validan en Android. Appium demuestra gestos e interacciones nativas y un flujo end-to-end.
- Los casos P0/P1 están aprobados, sin bloqueos ni defectos Sev1/Sev2 pendientes dentro del alcance.
- Los resultados de rendimiento se comparan con los objetivos definidos, incluida la integridad posterior a la carga.
- Hay revisión de otro integrante, CI y regresión cuando corresponda, manual, informe y demostración reproducibles.

## Ramas y Pull Requests

`main` y `develop` son las dos ramas permanentes. Crear una rama temporal por tarea desde `develop`, abrir PR hacia `develop`, revisar y verificar el resultado y eliminarla después de integrarla.

Al trabajar tres personas puede haber varias ramas temporales a la vez. No se necesita conservar ramas `deploy/*` para separar ambientes: la configuración de entorno se versiona y documenta. La entrega coordinada pasa de `develop` a `main` solo después de superar los criterios de cierre.

Seguir [CONTRIBUTING.md](CONTRIBUTING.md) y la plantilla de PR para relacionar issue, requisito, caso y evidencia.

## Instalación y configuración de la API

El backend dispone de un entorno Docker independiente para
el proyecto de Calidad de Software, diseñado para evitar
conflictos con el Portal de Empleos original y facilitar
la ejecución reproducible de pruebas.

### Requisitos previos

- Git.
- Docker y Docker Compose.
- WSL2 recomendado para Windows.
- Acceso al repositorio.

PHP, Composer y MySQL se encuentran disponibles mediante
los contenedores Docker.

### Configuración del entorno local

| Componente | Configuración |
|---|---|
| Proyecto Docker | calidad_software |
| Backend | Laravel 12 |
| PHP | 8.3 |
| Motor de base de datos | MySQL 8.0 |
| URL local | http://localhost:8002 |
| Puerto externo MySQL | 3307 |
| Puerto interno MySQL | 3306 |

### Instalación rápida

```bash
git clone https://github.com/stephany-toribio/recruitment-api-calidad-software.git

cd recruitment-api-calidad-software

git switch develop

test -f .env || cp .env.example .env
```

Configurar la variable `QA_FIXTURE_PASSWORD` en el archivo
`.env` con una contraseña exclusiva de pruebas de al menos
12 caracteres.

```bash
docker compose config --quiet

docker compose build app

docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
```

Si `APP_KEY` todavía está vacía, generar la clave:

```bash
docker compose run --rm --no-deps app php artisan key:generate
```

Iniciar los servicios:

```bash
docker compose up -d
docker compose ps
```

Verificar la disponibilidad del backend:

```bash
curl -i http://localhost:8002/up
```

El resultado esperado es HTTP 200.

### Bases de datos aisladas

| Base de datos | Finalidad |
|---|---|
| `portal_empleos_calidad_qa` | Aplicación y pruebas funcionales |
| `portalempleoshu04testing` | Pruebas de integración MySQL |
| SQLite `:memory:` | Pruebas Unit y Feature |

Antes de ejecutar migraciones o seeders, verificar que la
conexión corresponde a la base local autorizada.

```bash
docker compose exec app php artisan config:clear

docker compose exec app php artisan tinker --execute='dump(DB::selectOne("SELECT DATABASE() AS nombre"));'
```

El resultado debe ser `portal_empleos_calidad_qa`.

```bash
docker compose exec app php artisan migrate

docker compose exec app php artisan db:seed

docker compose exec app php artisan db:seed --class=QaSeeder
```

### Usuarios ficticios de QA

| Correo | Rol | Estado |
|---|---|---|
| admin@qa.example.test | Administrador | Activo |
| empresa1@qa.example.test | Empresa | Activo |
| empresa2@qa.example.test | Empresa | Activo |
| candidato1@qa.example.test | Candidato | Activo |
| candidato2@qa.example.test | Candidato | Activo |
| suspendido@qa.example.test | Candidato | Suspendido |

Las contraseñas se obtienen de `QA_FIXTURE_PASSWORD`.

Los identificadores numéricos deben obtenerse dinámicamente.

El seeder permite repetir la preparación de cuentas sin
generar duplicados y restablece sus tokens de autenticación.

### Limpieza controlada

```bash
docker compose exec app php artisan db:seed --class=QaCleanupSeeder
```

La limpieza se limita a las cuentas ficticias autorizadas
y se cancela si existen dependencias funcionales.

No ejecutar durante pruebas concurrentes.

Para restaurar las cuentas:

```bash
docker compose exec app php artisan db:seed --class=QaSeeder
```

### Documentación del entorno

Consultar la [guía completa del entorno QA](docs/qa/ENTORNO_QA.md)
para conocer los procedimientos de instalación, migraciones,
configuración MySQL, datos ficticios y restricciones de seguridad.

### Endpoints principales

Los paths parten de `/api/v1`. Las rutas protegidas usan `Authorization: Bearer <token>`.

| Operación | Método | Path | Acceso |
| --- | --- | --- | --- |
| Registrar candidato / empresa | POST | `/candidatos` / `/empresas` | Público, con validación |
| Login | POST | `/auth/login` | Público |
| Sesión / logout | GET / POST | `/auth/me` / `/auth/logout` | Autenticado |
| Perfil propio | GET / PUT | `/candidatos/me` o `/empresas/me` | Rol correspondiente |
| Registrar/listar vacantes propias | POST / GET | `/empresas/me/vacantes` | Empresa |
| Aprobar/no aprobar | PATCH | `/admin/vacantes/{vacante}/aprobar` o `/admin/vacantes/{vacante}/no-aprobar` | Administrador |
| Pago simulado | POST | `/empresas/me/vacantes/{vacante}/pago` | Empresa propietaria |
| Búsqueda/detalle | GET | `/vacantes` / `/vacantes/{vacante}` | Candidato |
| Postular/listar propias | POST / GET | `/candidatos/me/postulaciones` | Candidato |
| Retirar | PATCH | `/candidatos/me/postulaciones/{postulacion}/retirar` | Candidato propietario |
| Kanban / historial | GET | `/empresas/me/vacantes/{vacante}/postulaciones` y su sufijo `/historial` | Empresa propietaria |
| Decisiones | PATCH | `/empresas/me/vacantes/{vacante}/postulaciones/{postulacion}/avanzar`, `/rechazar` o `/contratar` | Empresa propietaria |

El body de una postulación envía `vacante_id`; no se acepta la identidad del candidato elegida por el cliente. Las rutas vigentes están en `routes/api_v1/` y se cargan desde `routes/api.php`.


### Pruebas automatizadas existentes

El backend cuenta con suites de pruebas unitarias,
funcionales y de integración que complementan el plan de
automatización del curso.

#### Pruebas Unit y Feature

Las pruebas utilizan SQLite en memoria, según la
configuración de `phpunit.xml`.

```bash
docker compose exec app php artisan config:clear

docker compose exec app php artisan test --testsuite=Unit

docker compose exec app php artisan test --testsuite=Feature
```

#### Pruebas de integración MySQL

Las pruebas MySQL utilizan una configuración independiente
mediante `phpunit.mysql.xml`.

La base autorizada es `portalempleoshu04testing`.

Antes de ejecutar las pruebas, preparar y verificar la
base siguiendo la [guía del entorno QA](docs/qa/ENTORNO_QA.md).

```bash
docker compose exec app vendor/bin/phpunit \
  --configuration phpunit.mysql.xml \
  --testsuite=MySql
```

Se conservan las protecciones existentes contra conexiones
a bases incorrectas y configuraciones no autorizadas.

Las pruebas concurrentes utilizan procesos independientes.

#### Línea base de calidad — QA-02

Resultados obtenidos el 24 de noviembre de 2026:

| Suite | Ejecutadas | Aprobadas | Fallidas |
|---|---:|---:|---:|
| Unit | 14 | 14 | 0 |
| Feature | 205 | 205 | 0 |
| MySQL | 345 | 327 | 18 |
| Total | 564 | 546 | 18 |

- Tasa de aprobación: 96.81%.
- Aserciones ejecutadas: 3717.
- Fallos pendientes de investigación: 18.

Los hallazgos corresponden a la búsqueda de vacantes
y al contrato de respuesta del detalle de vacantes.

Su investigación y corrección se relacionan con QA-07 (#15).

Consultar las [evidencias de QA-02](docs/evidencias/QA-02/RESULTADOS.md).

#### Herramientas de automatización del curso

PHPUnit y Postman complementan las pruebas del proyecto,
pero no sustituyen las herramientas requeridas por el curso:

| Herramienta | Finalidad |
|---|---|
| Karate | Automatización de pruebas de API REST |
| JMeter | Pruebas de carga, rendimiento y concurrencia |
| Selenium | Automatización de flujos web |
| Appium | Automatización de flujos móviles Android y PWA |

Los casos, scripts, reportes y evidencias de estas
herramientas se desarrollarán en sus issues correspondientes.

Se conservan las colecciones Postman existentes como
referencia, sujetas a la validación del contrato vigente
de la API.

## Referencias técnicas

- [Angular: service worker y recursos PWA](https://angular.dev/ecosystem/service-workers/getting-started).
- [Appium UiAutomator2: Android y contextos web](https://github.com/appium/appium-uiautomator2-driver).
