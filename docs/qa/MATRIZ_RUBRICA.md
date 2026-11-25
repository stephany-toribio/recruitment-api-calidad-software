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


<!-- QA04-POST-QA05 -->

## Seguimiento complementario QA-05

| Herramienta auxiliar | Resultado | Utilidad |
|---|---|---|
| PHPUnit | 279 pruebas, 1811 aserciones PASS | Contratos API |
| Node Test | 12/12 PASS | Política de sesiones |
| Python HTTP | 24/24 PASS | Integración HTTP |
| Playwright | 12/12 PASS | Regresión funcional |
| Angular | 3/3 PASS | Compilación |

### Preparación de controles académicos

| Control relacionado | Aporte de QA-05 | Evidencia formal pendiente |
|---|---|---|
| K01 | Contratos JSON | Esquemas Karate |
| K02 | Estados HTTP | Assertions Karate |
| K04 | Tokens e IDs dinámicos | Variables Karate |
| S01 | Identificación de elementos | Selectores Selenium |
| S03 | Esperas auxiliares | Esperas Selenium |
| S04 | Flujos de autenticación | Flujos E2E Selenium |
| T01-T04 | Resultados versionados | Vinculación Jira |

Los códigos abreviados anteriores corresponden a
los controles RUB existentes.

Los 24 controles académicos permanecen pendientes.
Ninguna prueba auxiliar sustituye Karate, Selenium,
JMeter o Appium.

La compilación Angular Android no acredita
la generación ni instalación de un APK.

Consultar MATRIZ_TRAZABILIDAD.md para conocer
la correspondencia de las regresiones QA-05.

## Evidencias preparatorias de QA-07

<!-- QA07-RUBRICA -->

- 643 pruebas PHPUnit aprobadas.
- 26 pruebas auxiliares frontend aprobadas.
- 18 fallos historicos resueltos.
- Defecto Luhn reproducido mediante RED y corregido mediante GREEN.
- Contratos de API y matriz de disponibilidad documentados.
- Compilaciones Angular estandar, QA y Android aprobadas.
- Evidencias verificadas mediante SHA256.

Estas evidencias son complementarias.

No se modifican los estados de acreditacion de los
24 controles de la rubrica. Su cumplimiento formal
requiere las herramientas obligatorias y Jira.

## Seguimiento QA-06

<!-- QA06-RUBRICA -->

QA-06 aporta una base funcional verificada para
la automatizacion posterior de registro y perfiles.

- PHPUnit: 49 pruebas nuevas.
- Node Test Runner: 49 pruebas nuevas.
- Angular: tres configuraciones compiladas.

No se declaran satisfechos los controles de la rubrica
Karate, JMeter, Selenium, Appium o Jira a partir de estas
ejecuciones auxiliares.

## Seguimiento QA-08

<!-- QA08-RUBRICA -->

Evidencia incorporada:

- Nueve pruebas nuevas de integracion MySQL.
- Veintiocho pruebas auxiliares frontend.
- Ocho fallos iniciales superados mediante RED-GREEN.
- Dos escenarios concurrentes mediante HTTP Kernel.
- Selectores data-testid para automatizacion posterior.
- Regresion completa: 804 pruebas aprobadas.

Esta evidencia no acredita por si sola los controles
obligatorios de Karate, JMeter, Selenium, Appium y Jira.
