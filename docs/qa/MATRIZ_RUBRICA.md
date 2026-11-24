# Matriz de cumplimiento de la rúbrica

## Objetivo

Relacionar los criterios de evaluación del curso con los
requisitos, casos de prueba, tareas, herramientas y evidencias.

Referencia: [Rúbrica de evaluación](../RUBRICA_EVALUACION.md).

## Matriz de controles académicos

| ID RUB | Criterio | Casos relacionados | Tareas | Evidencia esperada | Estado |
|---|---|---|---|---|---|
| RUB-K01 | Esquemas JSON exhaustivos | CP-01 a CP-12, CP-15 a CP-22 según contrato | QA-15, QA-16, QA-17 | Esquemas y reporte Karate | PENDIENTE |
| RUB-K02 | Códigos HTTP | CP-01 a CP-22 aplicables a API | QA-15, QA-16, QA-17 | Assertions HTTP | PENDIENTE |
| RUB-K03 | Tiempos de respuesta | CP-03, CP-11, CP-12 | QA-15, QA-16 | Assertions y umbrales documentados | PENDIENTE |
| RUB-K04 | Variables dinámicas | CP-01, CP-02, CP-03, CP-07, CP-12 | QA-14, QA-15, QA-16 | Datos, tokens e IDs dinámicos | PENDIENTE |
| RUB-K05 | Reutilización mediante call | CP-03, CP-07, CP-12, CP-16 | QA-14, QA-16 | Helpers reutilizables | PENDIENTE |
| RUB-J01 | Thread Groups realistas | CP-31, CP-32, CP-33 | QA-24 | Planes JMX | PENDIENTE |
| RUB-J02 | Listeners adecuados | CP-31, CP-32, CP-33 | QA-24, QA-25 | Configuración y métricas | PENDIENTE |
| RUB-J03 | Assertions | CP-13, CP-19, CP-31, CP-32, CP-33 | QA-25 | Response y Duration Assertions | PENDIENTE |
| RUB-J04 | CSV Data-Driven | CP-31, CP-32, CP-33 | QA-24 | CSV Data Set Config | PENDIENTE |
| RUB-J05 | Dashboard HTML | CP-31, CP-32, CP-33 | QA-25 | JTL y reporte HTML | PENDIENTE |
| RUB-S01 | Selectores robustos | CP-23, CP-25 | QA-12, QA-18 | Page Objects y selectores | PENDIENTE |
| RUB-S02 | Sin XPath absolutos | CP-23 | QA-18 | Revisión de código | PENDIENTE |
| RUB-S03 | Esperas explícitas o fluidas | CP-23 | QA-18 | Código y ejecuciones | PENDIENTE |
| RUB-S04 | Flujos críticos E2E | CP-23 | QA-19, QA-20 | Reportes Selenium | PENDIENTE |
| RUB-A01 | Capabilities | CP-24-04 | QA-21 | Configuración Appium | PENDIENTE |
| RUB-A02 | Android operativo | CP-24-03, CP-24-04 | QA-21 | Inventario y sesión | PENDIENTE |
| RUB-A03 | APK instalable | CP-24-03, CP-28-03 | QA-10, QA-21 | APK, hash y versión | PENDIENTE |
| RUB-A04 | Swipe, tap e interacción nativa | CP-25-03, CP-29-03, CP-29-04 | QA-22 | Scripts y jerarquía nativa | PENDIENTE |
| RUB-A05 | Sincronización estable | CP-24-04, CP-26-04, CP-27-03, CP-30-04 | QA-21, QA-23 | Esperas y resultados | PENDIENTE |
| RUB-A06 | Flujo end-to-end | CP-29-03 | QA-22 | Reporte Appium y persistencia | PENDIENTE |
| RUB-T01 | Tickets reales Jira | CP-34-03 | QA-04, QA-29 | Proyecto Jira y tickets | PENDIENTE |
| RUB-T02 | Casos automatizados vinculados | CP-34-03 | QA-29 | Matriz y enlaces | PENDIENTE |
| RUB-T03 | Reportes completos | CP-34-03 | QA-29, QA-31 | Reportes de las cuatro herramientas | PENDIENTE |
| RUB-T04 | Defectos y retests | CP-34-03 | QA-29, QA-30 | Historial de defectos y regresión | PENDIENTE |

Las asociaciones por familia son una planificación inicial.
Cada escenario automatizado deberá tener una relación exacta
con los controles RUB que realmente verifica.

## Registro de ejecución

| Caso | RUB | Jira | Issue GitHub | Commit/build | Ambiente | Resultado | Reporte | Defecto/retest |
|---|---|---|---|---|---|---|---|---|

La tabla se completará con datos reales de ejecución.

No utilizar claves Jira inventadas ni resultados PASS
sin pruebas ejecutadas y evidencia disponible.

## Control de calificación objetivo

| Criterio | Máximo | Evidencias aprobadas |
|---|---:|---|
| Karate DSL | 4 | Pendiente |
| Apache JMeter | 4 | Pendiente |
| Selenium | 4 | Pendiente |
| Appium | 4 | Pendiente |
| Jira y reportes | 4 | Pendiente |
| Total objetivo | 20 | Pendiente de evaluación |

Los 24 identificadores RUB son controles internos.
No constituyen una distribución oficial de puntuaciones.

La calificación definitiva corresponde al docente.
