# Registro y Matriz de Riesgos — QA-04

- **Proyecto**: Portal de Empleos CREO+ (PWA y API REST) — Calidad de Software.
- **Responsable**: R3 (@luisvalenzuela25).
- **Revisor asignado**: R1 (@stephany-toribio).
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.0.0.
- **Fecha**: 24/11/2026.
- **Rama**: `feature/QA-04-matriz-pruebas`.

---

## 1. Definición de escalas de evaluación

### 1.1 Escala de Probabilidad (P)
| Nivel | Valor | Criterio |
|---|---|---|
| **Baja** | 1 | Evento poco probable bajo condiciones normales de ejecución y uso. |
| **Media** | 2 | Evento factible en situaciones de concurrencia moderada, cortes de red o ambientes heterogéneos. |
| **Alta** | 3 | Evento altamente probable debido a la naturaleza distribuida, móvil o concurrente del sistema. |

### 1.2 Escala de Impacto (I)
| Nivel | Valor | Criterio |
|---|---|---|
| **Menor** | 1 | Detalle estético o editorial que no compromete la lógica de negocio ni la integridad. |
| **Moderado**| 2 | Función degradada pero con alternativa operativa; sin corrupción de datos. |
| **Mayor** | 3 | Bloqueo del flujo principal de un rol, fallo de una herramienta obligatoria o retraso crítico. |
| **Crítico** | 4 | Pérdida o corrupción de datos, sobrecontratación, bypass de autorización o caída del backend. |

### 1.3 Severidad del Riesgo y Priorización
$$\text{Severidad} = \text{Probabilidad (P)} \times \text{Impacto (I)}$$

| Puntuación (P × I) | Nivel de Riesgo | Prioridad de Tratamiento |
|---|---|---|
| **9 a 12** | **Crítico** | **P0** (Acción inmediata; bloquea paso de hito). |
| **6 a 8** | **Alto** | **P0 / P1** (Mitigación obligatoria antes de release). |
| **3 a 4** | **Medio** | **P1 / P2** (Monitoreo activo y pruebas de regresión). |
| **1 a 2** | **Bajo** | **P2** (Aceptado con seguimiento documental). |

---

## 2. Registro detallado de riesgos

| ID | Descripción del riesgo | Requisitos y Casos Afectados | P | I | P×I | Nivel | Prioridad | Rol Responsable | Estrategia de Mitigación | Condición de Seguimiento / Cierre |
|---|---|---|---|---|---|---|---|---|---|---|
| **RSK-01** | **Bypass de autorización o escalada de privilegios en API**: Un candidato o empresa accede a endpoints ajenos o muta recursos sin pertenecer a ellos (p. ej. otra empresa altera postulaciones en Kanban). | RF-02, RF-03, RF-05, RNF-01<br>`CP-04`, `CP-06`, `CP-08`, `CP-15` | 2 | 4 | **8** | **Alto** | **P0** | **R1** (@stephany-toribio) | Validación estricta en servidor mediante Sanctum, Policies de Laravel y verificación de pertenencia en servicios (`vacantePropia`). Cobertura de suites negativas en Karate. | Ejecución aprobada de CP-15 en Karate (QA-17) con HTTP 403 en todos los intentos de acceso cruzado. |
| **RSK-02** | **Confusión de entorno o ejecución sobre base incorrecta**: Correr migraciones, seeders destructivos o pruebas contra bases de producción o del portal original en lugar de las bases aisladas de QA. | RNF-04<br>`CP-34` | 2 | 4 | **8** | **Alto** | **P0** | **R1** (@stephany-toribio) | Verificaciones de seguridad en `QaSeeder.php` y `QaCleanupSeeder.php` que abortan si `DATABASE() != 'portal_empleos_calidad_qa'`. Puertos externos Docker separados (8002/3307). | Verificación previa en cada script con `php artisan tinker` antes de ejecutar migraciones (QA-02, QA-13). |
| **RSK-03** | **PWA instalada en Android no expone contexto WebView para Appium**: Que la aplicación PWA instalada en modo standalone abra pestañas externas en Chrome en lugar de un WebView automatizable, bloqueando las pruebas móviles. | PWA-01, PWA-04<br>`CP-24`, `CP-25`, `CP-29` | 3 | 3 | **9** | **Crítico** | **P0** | **R3** (@luisvalenzuela25) | Definir `display: standalone`, `scope` y `start_url` rigurosos en `manifest.webmanifest`; validar `chromedriverExecutable` y capabilities de `UiAutomator2` tempranamente en QA-21. | Demostración formal de conmutación a contexto `WEBVIEW_*` en emulador/dispositivo Android en QA-21. |
| **RSK-04** | **Caché de PWA conserva datos privados de sesión o tokens**: Que el Service Worker almacene en Cache Storage respuestas de endpoints privados (`/api/v1/auth/me`, perfiles o postulaciones), exponiendo datos a terceros en modo sin conexión. | PWA-02, RNF-01<br>`CP-05`, `CP-26`, `CP-30` | 2 | 4 | **8** | **Alto** | **P0** | **R2** (@aquinoestoyxd) | Configurar `ngsw-config.json` para que el Service Worker maneje exclusivamente assets estáticos; denegar explícitamente en el interceptor el almacenamiento de respuestas `/api/v1/*`. | Inspección automatizada y manual de Cache Storage en DevTools / Appium certificando cero respuestas privadas en caché (QA-11, QA-23). |
| **RSK-05** | **Postulaciones duplicadas por concurrencia o doble click**: Que dos envíos simultáneos del mismo candidato generen registros dobles en la base de datos o estados inconsistentes. | RF-07, RNF-02<br>`CP-12`, `CP-13`, `CP-27` | 3 | 4 | **12** | **Crítico** | **P0** | **R1** / **R3** (@stephany-toribio, @luisvalenzuela25) | Restricción única en base de datos `unique(['candidato_id', 'empleo_id'])` y bloqueo transaccional `lockForUpdate()` en `PostulacionService`. JMeter valida carreras con respuesta 409 `DUPLICATE_APPLICATION`. | Ejecución exitosa de CP-13 en JMeter (QA-25) y verificación en base de datos de exactamente 1 registro. |
| **RSK-06** | **Publicación prematura o postulación en vacantes no disponibles**: Postular a vacantes no aprobadas por el admin, sin pago simulado exitoso, pausadas, vencidas o eliminadas lógicamente. | RF-04, RF-05, RF-06<br>`CP-08`, `CP-09`, `CP-10` | 2 | 3 | **6** | **Alto** | **P0** | **R1** (@stephany-toribio) | Servicio centralizado `DisponibilidadVacanteService` que evalúa las 7 condiciones obligatorias antes de listar o admitir postulación. | Suite Karate (QA-17) y pruebas MySQL de disponibilidad (QA-07) aprobadas al 100%. |
| **RSK-07** | **Sobrecontratación concurrente superando el número de vacantes**: Dos evaluadores contratan simultáneamente al último puesto vacante disponible, superando el cupo `numero_vacantes`. | RF-08, RNF-02<br>`CP-16`, `CP-19`, `CP-33` | 3 | 4 | **12** | **Crítico** | **P0** | **R3** (@luisvalenzuela25) | Bloqueo pesimista `lockForUpdate()` sobre la vacante y postulación en `KanbanService::mover()`, serializando las decisiones y lanzando 409 `HIRING_LIMIT_REACHED`. | Prueba de concurrencia en JMeter (CP-19 en QA-25) demostrando que para 1 cupo restante, 1 request obtiene 200 y el concurrente 409. |
| **RSK-08** | **Discrepancia contractual en búsqueda y detalle de vacantes (Defectos QA-02)**: Los 18 fallos reportados en la línea base de QA-02 (`QA02-H01` en filtros y `QA02-H02` en campos adicionales como requisitos/beneficios). | RF-06<br>`CP-10`, `CP-11` | 3 | 2 | **6** | **Alto** | **P1** | **R1** (@stephany-toribio) | Investigar la causa raíz en QA-07: contrastar contrato acordado de la API con los atributos de `DetalleVacanteMySqlTest` y los filtros de aproximación de `BusquedaVacantesMySqlTest`. | Cierre formal de QA-07 (#15) con retest aprobado en la suite `BusquedaVacantesMySqlTest` y `DetalleVacanteMySqlTest`. |
| **RSK-09** | **Degradación de rendimiento bajo carga de 25 usuarios concurrentes**: Tiempos de respuesta p95 superiores a 2 s en lecturas o a 3 s en escrituras, o errores superiores al 1% en JMeter. | RNF-03<br>`CP-31`, `CP-32`, `CP-33` | 2 | 3 | **6** | **Alto** | **P1** | **R3** (@luisvalenzuela25) | Diseño gradual de rampa de carga en QA-24, optimización de índices en MySQL (`idx_empleo_estado`), ejecución en entorno QA aislado y tuning si es necesario en QA-26. | Informe de métricas de JMeter en QA-25 cumpliendo p95 ≤ 2s (lecturas) y ≤ 3s (login/escrituras). |
| **RSK-10** | **Incompatibilidad de entorno local/CI (Node, Docker, JDK)**: Diferencias entre la máquina del desarrollador (Node 24, Windows) y el entorno estándar del proyecto (Node 22, Linux CI). | RNF-04<br>`CP-34` | 3 | 2 | **6** | **Alto** | **P1** | **R2** / **R3** (@aquinoestoyxd, @luisvalenzuela25) | Estandarización mediante Dockerfiles y fijación de versiones de engines en `package.json` y acciones de GitHub Actions. | Ejecución limpia del workflow de CI en develop sin fallos de compilación ni ejecución de suites (QA-27, QA-28). |
| **RSK-11** | **Pérdida de conexión durante postulación y estado incierto en PWA**: El usuario envía su postulación, se corta la red antes de recibir respuesta y el cliente simula éxito o reenvía automáticamente duplicando peticiones. | PWA-02, RF-07<br>`CP-27` | 3 | 3 | **9** | **Crítico** | **P0** | **R2** / **R3** (@aquinoestoyxd, @luisvalenzuela25) | Comportamiento honesto de la PWA: sin escrituras offline, banner de error de red y botón para verificar estado persistente en el servidor antes de reintentar. | Prueba en Appium (CP-27 en QA-22/QA-23) cortando la red y verificando que no se emiten éxitos ficticios. |
| **RSK-12** | **Desbordamiento de recursos del contenedor MySQL durante pruebas de carga**: Bloqueos de tablas prolongados o agotamiento de conexiones en `portal_empleos_calidad_qa`. | RNF-02, RNF-03<br>`CP-33` | 2 | 3 | **6** | **Alto** | **P1** | **R3** (@luisvalenzuela25) | Configuración de `max_connections`, timeouts de transacciones y reinicio de base previo a la ejecución masiva; pruebas con rampa de subida progresiva. | Verificación de métricas de Docker Compose sin reinicio de contenedores durante las ejecuciones de JMeter en QA-25. |

---

## 3. Protocolo de seguimiento y revisión de riesgos

1. **Revisión en Stand-ups / Jornadas**: En cada sesión de trabajo, el equipo evalúa si algún riesgo ha cambiado de estado o si un bloqueo en progreso eleva su probabilidad.
2. **Puerta de paso por Hito**:
   - Para cerrar **M1 (Flujo de negocio)**: Los riesgos `RSK-01`, `RSK-05`, `RSK-06`, `RSK-07` y `RSK-08` deben tener mitigaciones técnicas probadas con evidencia en API y pruebas unitarias/MySQL.
   - Para cerrar **M2 (PWA y entorno QA)**: Los riesgos `RSK-03`, `RSK-04` y `RSK-11` deben estar mitigados y demostrados en Android.
   - Para cerrar **M5 (Entrega final)**: Todos los riesgos P0 y P1 deben encontrarse en estado cerrado o controlado sin defectos Sev1/Sev2 pendientes.
