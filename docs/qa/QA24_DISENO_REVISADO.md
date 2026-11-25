# QA-24 — Diseño revisado de carga JMeter

Fecha: 25/11/2026. Repositorio backend, rama temporal desde develop.
Este diseño sustituye las afirmaciones anteriores sobre porcentajes, aceptación general de 409, verificación textual y análisis de archivos vacíos de diseno_carga_jmeter.md. Las mediciones previas se conservan y no se presentan como ejecuciones del JMX corregido.

## Ambiente y alcance

Destino aprobado para este runner: HTTP 127.0.0.1:8002 (o localhost), servicio Docker app, PHP/Laravel y MySQL aislado portal_empleos_calidad_qa. El runner verifica entorno local, MySQL, host mysql, configuración sin caché y mapeo del puerto antes de enviar carga. No usa el túnel público.

Condiciones observadas por el ejecutor: php artisan serve en el contenedor; bind mount desde Windows/OneDrive. El diagnóstico de seis mediciones registró DNS 0–4 ms, PDO 2–5 ms y HTTP/404 217–2755 ms. No identifica aún una causa ni representa los endpoints de negocio. Los recursos Docker, límites del contenedor, mounts, versiones, commit y hash del JMX/CSV se registran por corrida. Anotar además CPU/RAM del anfitrión y otras cargas concurrentes antes de medir. Mantener el mismo ambiente para comparar.

Objetivo inicial a validar, sin modificarlo para aprobar una ejecución: 25 VU, p95 de lecturas <=2000 ms; login y escrituras <=3000 ms cada categoría; errores inesperados <1%; cero pérdida y duplicación. Un incumplimiento es resultado real y debe reportarse. Revisar objetivos después de la línea base con justificación y revisión R1; no relajarlos automáticamente.

## Modelo cerrado y mezcla

| Corrida | Consulta | Login | Postulación | Total |
| --- | ---: | ---: | ---: | ---: |
| Diagnóstico de cableado | 1 | 1 | 1 | 3 |
| Exploración 10 | 6 | 2 | 2 | 10 |
| Línea base 25 | 15 | 4 | 6 | 25 |
| Exploración 50 | 30 | 8 | 12 | 50 |

En 25 VU, los porcentajes son **60/16/24 de usuarios**, no de solicitudes. La mezcla HTTP se obtiene de las cantidades por etiqueta del JTL. El modelo cerrado no garantiza una tasa de llegada: depende de las respuestas y pausas.

- Consulta: una asignación CSV y un login inicial; ciclos de listado + detalle (dos lecturas por ciclo).
- Login: una cuenta independiente por hilo; ciclos de login válido. Se correlaciona el token nuevo en cada login.
- Postulación: un login inicial; listado + detalle previo; solo cuando no existe postulación y puede_postular=true se envía POST (201 obligatorio) y GET de verificación. Ciclo normal: tres lecturas + una escritura. Si ya existe una postulación de corridas anteriores, no se fuerza otra ni se cuenta un 409 como éxito normal; se continúa buscando.
- Los casos negativos están desactivados en la línea base positiva. Con -NegativeCases se habilita login inexistente 401 (sin castigar la cuenta válida) y una duplicada deliberada 409 por VU de postulación, seguida de GET que debe conservar el mismo ID. Se reportan por separado; cualquier otro resultado es inesperado.

Thread Groups concurrentes, 30 s de ramp-up, 300 s totales por nivel, incluyendo ramp-up. Timer uniforme 1000–3000 ms antes de cada solicitud; timeout de conexión 5 s y respuesta 15 s. Los percentiles actuales incluyen arranque/ramp-up y errores, sin descartarlos. Registrar N por etiqueta: pocos resultados no prueban estabilidad.

## CSV y cuentas

Tres CSV independientes existentes: performance/jmeter/data/usuarios_lectura.csv, usuarios_login.csv y usuarios_postulacion.csv. Cabecera exacta: correo,password. Se preparan 50 cuentas de cada perfil (150 totales), correos ficticios del dominio carga.qa.invalid, a través de los seeders de la rama. Los ejemplos *.example.csv contienen marcadores y no sirven para autenticarse.

CSV Data Set Config: UTF-8, encabezado omitido al declarar variables, recycle=false, stopThread=true, shareMode.all. Cada Thread Group tiene una única iteración exterior para asignar la cuenta; un Loop Controller interior mantiene la sesión durante la duración. No se consume una cuenta nueva por cada acción. La validación local con un solo registro por perfil y muchos ciclos comprobó este comportamiento en JMeter 5.6.3.

El runner valida suficientes filas, correos del perfil y ausencia de cuentas repetidas entre archivos; el JMX comprueba además asignaciones concurrentes independientes. Una misma cuenta se conserva dentro de su VU, no se comparte entre perfiles/hilos. Los seeders y las cuentas se revisan antes de ejecutarlos; el runner no los reejecuta ni elimina datos automáticamente.

Preparación de datos pendiente de evidencia real de la corrida: comprobar 50 cuentas por perfil, activas y capaces de autenticarse, y las vacantes de carga publicadas/vigentes. No desactivar throttling, autenticación o restricciones de negocio para ocultar fallos. Un 429 del escenario positivo es inesperado y se registra para analizar el modelo y los límites reales.

## Correlación y datos

Groovy/JSR223 analiza JSON: data.token y data.usuario_id del login, IDs de vacantes de data[] y paginación meta.last_page; data.id y data.vacante.id del POST; data.postulacion.id del GET. Se comparan los IDs, no cadenas sensibles a espacios. El header Bearer se limita a solicitudes protegidas. No se reintenta un POST automáticamente; el runner fija httpclient4.retrycount=0. Un POST fallido o sin confirmación se registra como escritura no confirmada y bloquea la aprobación global, aunque el error porcentual sea menor a 1%. Requiere reconciliación de datos; no se repite a ciegas.

El JTL guarda únicamente run_id, perfil, VU e IDs numéricos. No guarda passwords, tokens, cuerpos o headers. expected-integrity.json contiene las creaciones exitosas con usuario/vacante/postulación. verificar_integridad.php realiza una lectura posterior en la BD aislada: cada par usuario-vacante debe tener exactamente una fila y el ID esperado. Comprueba además que las cuentas auditadas pertenezcan al perfil ficticio postulación. No borra ni corrige datos.

Un GET confirma lo observado por la API; no sustituye la comprobación SQL de duplicación. Si el scheduler termina entre POST y GET, se reporta la falta de GET y la auditoría SQL posterior puede comprobar la escritura pendiente. Un archivo vacío, perfiles sin muestras o auditoría ausente/fallida nunca producen aprobación global.

## Ejecución reproducible

Desde PowerShell en la raíz del backend, con Docker activo y Python disponible:

```powershell
# Primera validación real, no acredita capacidad de 25 VU:
& ./performance/jmeter/run_baseline.ps1 -Users 3 -RampUp 5 -Duration 45 -Name smoke-qa24 -NegativeCases -ConfirmoQAAislado

# Línea base, después de corregir errores del diagnóstico:
& ./performance/jmeter/run_baseline.ps1 -Users 25 -RampUp 30 -Duration 300 -Name baseline-25 -ConfirmoQAAislado

# Exploración, solo después de revisar la línea base:
& ./performance/jmeter/run_exploracion.ps1 -ConfirmoQAAislado
```

Si JMeter no está en PATH, agregar -JMeter 'C:\ruta\apache-jmeter-5.6.3\bin\jmeter.bat'. Se conserva config.ps1 existente. Cada corrida usa carpeta nueva con fecha/UUID; no se borran resultados anteriores. -SinAnalisis queda reservado por compatibilidad: no desactiva la auditoría.

Abrir plan_carga.jmx desde Archivo > Abrir en JMeter permite inspeccionar Thread Groups, CSV, timers y assertions. La carga medida se ejecuta por CLI. Summary/Aggregate Report están desactivados; se pueden activar exclusivamente para depurar en GUI. El dashboard HTML se genera después de la ejecución, en results/<corrida>/html/index.html, junto al JTL y metrics.json. Los listeners no deben guardar respuestas autenticadas.

## Parada

Exploración 10/25/50 secuencial, nunca simultánea con otras suites. El primer error inesperado detiene la etapa mediante stoptest; los requests en curso pueden finalizar. Entre etapas no se escala si hay errores, faltan categorías, falla integridad, errores >5%, p95 de lectura >4000 ms o login/escritura >6000 ms. Los dos límites estadísticos son controles entre etapas, no monitores de ventana móvil en tiempo real. Detener manualmente si los recursos del anfitrión se saturan; registrar causa y duración observada.

Con la política conservadora de primer error, el umbral de 5% normalmente no llega a gobernar la parada: se conserva como comprobación adicional posterior. La línea base usa stopthread ante errores: un resultado con VU que terminaron anticipadamente requiere revisión y no demuestra carga sostenida por tener código de salida cero. Las condiciones no son los criterios de aprobación: que una exploración no se detenga no implica cumplir los SLO.

## Modelo de resultados y cierre

| Corrida | VU | p95 READ | p95 LOGIN | p95 WRITE | Errores inesperados | 4xx esperados | Throughput | Integridad SQL | Commit/JMX | Estado |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- | --- | --- |
| Diagnóstico real | 3 | pendiente | pendiente | pendiente | pendiente | pendiente | pendiente | pendiente | pendiente | pendiente |
| Línea base | 25 | pendiente | pendiente | pendiente | pendiente | separado | pendiente | pendiente | pendiente | pendiente |
| Exploración | 10/25/50 | pendiente | pendiente | pendiente | pendiente | separado | pendiente | pendiente | pendiente | pendiente | pendiente |

CP-31/CP-32/CP-33: registrar resultados y correspondencia de consultas/login/postulación contra el catálogo vigente. Enlazar QA-02 (casos/datos), QA-04 (entorno), RUB aplicables y ticket Jira cuando corresponda. Confirmar dependencias, recursos y datos sin inferirlos de un commit. PR revisado por R1 e integrado antes de cerrar.

Las pruebas locales incluidas en docs/evidencias/QA-24/validacion-local validan el plan/análisis con una API simulada; no son la línea base ni resultados de rendimiento de Laravel/MySQL del ejecutor. El auditor PHP y la integración real con el backend se comprueban en el entorno del ejecutor. QA-24 permanece abierto hasta consolidar evidencia real y revisión.

Referencias: https://jmeter.apache.org/usermanual/component_reference.html y https://jmeter.apache.org/usermanual/best-practices.html.
