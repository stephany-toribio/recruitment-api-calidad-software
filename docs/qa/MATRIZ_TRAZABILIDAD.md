# Matriz de Trazabilidad de Requisitos, Casos y Riesgos — QA-04

- **Proyecto**: Portal de Empleos CREO+ (Web, PWA, APK Android y API REST) — Calidad de Software.
- **Responsable del documento**: R3 (@luisvalenzuela25 — Integración, Appium, JMeter y trazabilidad).
- **Validación**: Verificación técnica y evidencia.
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.4.0 (Trazabilidad posterior a QA-05).
- **Fecha de versión**: 25/11/2026.
- **Rama de actualización**: docs/QA-04-trazabilidad-post-QA-05.
- **Estado de ejecución global**: Regresiones QA-02 y QA-05 registradas; suites académicas pendientes.

---

## 1. Alcance y convención de trazabilidad

Esta matriz relaciona bidireccionalmente los **18 requisitos** del sistema (10 RF, 4 PWA, 4 RNF) con las **34 familias de casos de prueba (CP-01 a CP-34)**, sus **111 escenarios canónicos detallados**, su nivel de prioridad, las herramientas de automatización asignadas, las tareas e integrantes responsables, y los riesgos operacionales o técnicos asociados.

### 1.1 Cadena de trazabilidad obligatoria
```
Requisito (RF / PWA / RNF)
  └── Familia de Casos (CP-XX)
        └── Escenarios de Prueba (CP-XX-YY: Positivos [+] / Negativos [-])
              ├── Herramienta (Karate / Selenium / Appium / JMeter / PHPUnit)
              ├── Tarea del Backlog (QA-NN) & Rol Responsable (R1 / R2 / R3)
              ├── Riesgo Mitigado (RSK-ZZ)
              └── Estado de Ejecución (Pendiente / Ejecutado / Aprobado / Defectuoso)
```

### 1.2 Criterios de prioridad
- **P0 (Crítico / Bloqueante)**: Autenticación, autorización de servidor, unicidad de postulación, integridad transaccional, concurrencia en contrataciones, cupos límite, estados finales y pérdida de conexión PWA/APK.
- **P1 (Esencial para el flujo)**: Registro válido/inválido, publicación, pagos simulados, filtros de búsqueda, transiciones intermedias, retiros válidos, instalación standalone PWA y pruebas de carga planificadas.
- **P2 (Complementario / Calidad adicional)**: Accesibilidad responsiva no bloqueante, ordenación secundaria y validaciones visuales menores.

---

## 2. Matriz de trazabilidad general

| Requisito | Familia CP | Escenarios asociados | Prioridad | Herramienta principal | Herramienta complementaria | Tarea responsable | Rol | Riesgos asociados |
|---|---|---|---|---|---|---|---|---|
| **RF-01** (Registro real y unicidad) | **CP-01** | CP-01-01 (+), CP-01-02 (-), CP-01-03 (-) | P1 | Karate | Selenium | QA-06, QA-15, QA-19 | R2, R1 | RSK-05, RSK-07 |
| **RF-01** (Registro real y unicidad) | **CP-02** | CP-02-01 (+), CP-02-02 (-), CP-02-03 (-) | P1 | Karate | Selenium | QA-06, QA-15 | R2, R1 | RSK-05, RSK-07 |
| **RF-02** (Autenticación y sesiones) | **CP-03** | CP-03-01 (+), CP-03-02 (+), CP-03-03 (+) | P0 | Karate | Selenium, Appium | QA-05, QA-15, QA-19, QA-22 | R1, R2, R3 | RSK-01, RSK-02 |
| **RF-02** (Autenticación y sesiones) | **CP-04** | CP-04-01 (-), CP-04-02 (-), CP-04-03 (-) | P0 | Karate | Selenium | QA-05, QA-15 | R1 | RSK-01, RSK-02 |
| **RF-02** (Autenticación y sesiones) | **CP-05** | CP-05-01 (+), CP-05-02 (-), CP-05-03 (-) | P0 | Karate | Selenium, Appium | QA-05, QA-15, QA-23 | R1, R2 | RSK-01, RSK-04 |
| **RF-03** (Perfil propio) | **CP-06** | CP-06-01 (+), CP-06-02 (+), CP-06-03 (-), CP-06-04 (-) | P1 | Karate | Selenium | QA-05, QA-06, QA-17 | R1, R2, R3 | RSK-01, RSK-04 |
| **RF-04** (Registro y gestión vacantes) | **CP-07** | CP-07-01 (+), CP-07-02 (-), CP-07-03 (-), CP-07-04 (-) | P1 | Karate | Selenium | QA-07, QA-16, QA-20 | R1, R2, R3 | RSK-06, RSK-07 |
| **RF-05** (Aprobación y pago simulado) | **CP-08** | CP-08-01 (+), CP-08-02 (+), CP-08-03 (-), CP-08-04 (-) | P0 | Karate | Selenium | QA-07, QA-16, QA-20 | R1, R3 | RSK-01, RSK-06 |
| **RF-05** (Aprobación y pago simulado) | **CP-09** | CP-09-01 (+), CP-09-02 (-), CP-09-03 (-), CP-09-04 (-), CP-09-05 (-) | P1 | Karate | Selenium | QA-07, QA-16, QA-20 | R1, R3 | RSK-06, RSK-07 |
| **RF-06** (Búsqueda y disponibilidad) | **CP-10** | CP-10-01 (-), CP-10-02 (-), CP-10-03 (-), CP-10-04 (-), CP-10-05 (-), CP-10-06 (-), CP-10-07 (-), CP-10-08 (-), CP-10-09 (-), CP-10-10 (-), CP-10-11 (-) | P0 | Karate | PHPUnit/MySQL | QA-07, QA-17 | R1, R3 | RSK-06, RSK-08 |
| **RF-06** (Búsqueda y disponibilidad) | **CP-11** | CP-11-01 (+), CP-11-02 (+), CP-11-03 (+) | P1 | Karate | Selenium, Appium | QA-07, QA-19, QA-22 | R1, R2, R3 | RSK-06, RSK-08 |
| **RF-07** (Postulación y unicidad) | **CP-12** | CP-12-01 (+), CP-12-02 (-) | P0 | Karate | Selenium, Appium | QA-08, QA-16, QA-19, QA-22 | R1, R2, R3 | RSK-05, RSK-07 |
| **RF-07** (Postulación y unicidad) | **CP-13** | CP-13-01 (-), CP-13-02 (-) | P0 | JMeter | Karate, PHPUnit/MySQL | QA-08, QA-17, QA-25 | R1, R3 | RSK-05, RSK-07 |
| **RF-07** (Postulación y unicidad) | **CP-14** | CP-14-01 (+), CP-14-02 (+) | P0 | JMeter | PHPUnit/MySQL | QA-08, QA-25 | R1, R3 | RSK-07, RSK-09 |
| **RF-08** (Selección, cupos y decisiones) | **CP-16** | CP-16-01 (+), CP-16-02 (+), CP-16-03 (+) | P0 | Karate | Selenium | QA-09, QA-16, QA-20 | R2, R1, R3 | RSK-06, RSK-07 |
| **RF-08** (Selección, cupos y decisiones) | **CP-17** | CP-17-01 (+), CP-17-02 (+), CP-17-03 (-) | P1 | Karate | Selenium | QA-09, QA-16, QA-20 | R2, R1, R3 | RSK-06, RSK-07 |
| **RF-08** (Selección, cupos y decisiones) | **CP-18** | CP-18-01 (-), CP-18-02 (-), CP-18-03 (-) | P0 | Karate | PHPUnit/MySQL | QA-09, QA-17 | R2, R3 | RSK-06, RSK-07 |
| **RF-08** (Selección, cupos y decisiones) | **CP-19** | CP-19-01 (-), CP-19-02 (+) | P0 | JMeter | PHPUnit/MySQL | QA-09, QA-25 | R2, R3 | RSK-07, RSK-09 |
| **RF-09** (Seguimiento y retiro) | **CP-20** | CP-20-01 (+), CP-20-02 (-), CP-20-03 (-), CP-20-04 (-) | P0 | Karate | Selenium | QA-08, QA-17, QA-19 | R1, R2, R3 | RSK-01, RSK-05, RSK-07 |
| **RF-09** (Seguimiento y retiro) | **CP-21** | CP-21-01 (+), CP-21-02 (+) | P1 | Karate | Selenium | QA-08, QA-09, QA-16 | R1, R2 | RSK-06, RSK-08 |
| **RF-10** (Historial de decisiones) | **CP-22** | CP-22-01 (+), CP-22-02 (+), CP-22-03 (+) | P1 | Karate | Selenium | QA-09, QA-16, QA-20 | R2, R1, R3 | RSK-06, RSK-07 |
| **RF-01..10** (Flujo integrado) | **CP-23** | CP-23-01 (+) | P0 | Selenium | Manual E2E | QA-19, QA-20, QA-30 | R2, R3 | RSK-02, RSK-07 |
| **PWA-01** (Instalación PWA y APK Android) | **CP-24** | CP-24-01 (+), CP-24-02 (+), CP-24-03 (+), CP-24-04 (+) | P1 | Appium | Android DevTools | QA-10, QA-21, QA-22 | R2, R3 | RSK-03, RSK-04, RSK-13 |
| **PWA-04** (Diseño móvil y accesibilidad) | **CP-25** | CP-25-01 (+), CP-25-02 (+), CP-25-03 (+) | P1 | Appium | Selenium | QA-12, QA-22 | R2, R3 | RSK-03, RSK-10, RSK-14 |
| **PWA-02** (Offline PWA/APK y privacidad) | **CP-26** | CP-26-01 (+), CP-26-02 (-), CP-26-03 (-), CP-26-04 (-) | P0 | Appium | Android DevTools | QA-11, QA-23 | R2, R3 | RSK-04, RSK-11 |
| **PWA-02** (Escritura con corte de red) | **CP-27** | CP-27-01 (-), CP-27-02 (+), CP-27-03 (-) | P0 | Appium | API REST | QA-11, QA-22, QA-23 | R2, R3 | RSK-04, RSK-05, RSK-11 |
| **PWA-03** (Actualización PWA y APK) | **CP-28** | CP-28-01 (+), CP-28-02 (+), CP-28-03 (+) | P1 | Appium | Navegador Android | QA-11, QA-23 | R2, R3 | RSK-04, RSK-10, RSK-13 |
| **PWA-01..04, RF-02,06,07** (Flujo móvil) | **CP-29** | CP-29-01 (+), CP-29-02 (+), CP-29-03 (+), CP-29-04 (-) | P0 | Appium | API REST | QA-21, QA-22 | R3 | RSK-03, RSK-04, RSK-05, RSK-13, RSK-14 |
| **PWA-02..03, RF-02** (Sesión móvil) | **CP-30** | CP-30-01 (+), CP-30-02 (-), CP-30-03 (-), CP-30-04 (+) | P0 | Appium | API REST | QA-23 | R2, R3 | RSK-01, RSK-04 |
| **RNF-01** (Autorización de servidor) | **CP-15** | CP-15-01 (-), CP-15-02 (-), CP-15-03 (-), CP-15-04 (-) | P0 | Karate | Selenium | QA-05, QA-17, QA-20 | R1, R3 | RSK-01, RSK-02 |
| **RNF-02** (Integridad y concurrencia) | *CP-13, CP-14, CP-19* | *Ver CP-13-02 (-), CP-14-01 (+), CP-19-01 (-)* | P0 | JMeter | PHPUnit/MySQL | QA-08, QA-09, QA-25 | R1, R2, R3 | RSK-05, RSK-07, RSK-09 |
| **RNF-03** (Carga y rendimiento) | **CP-31** | CP-31-01 (+), CP-31-02 (+) | P1 | JMeter | Monitor Docker | QA-24, QA-25, QA-26 | R3, R1 | RSK-09, RSK-12 |
| **RNF-03** (Carga y rendimiento) | **CP-32** | CP-32-01 (+), CP-32-02 (-) | P1 | JMeter | API REST | QA-24, QA-25, QA-26 | R3, R1 | RSK-01, RSK-09 |
| **RNF-03** (Carga y rendimiento) | **CP-33** | CP-33-01 (+), CP-33-02 (+) | P0 | JMeter | PHPUnit/MySQL | QA-24, QA-25, QA-26 | R3, R1 | RSK-07, RSK-09 |
| **RNF-04** (CI, reproducibilidad y Jira) | **CP-34** | CP-34-01 (+), CP-34-02 (+), CP-34-03 (+) | P0 | GitHub Actions | Docker / Shell | QA-27, QA-28, QA-29, QA-30, QA-31 | R1, R2, R3 | RSK-02, RSK-10, RSK-15 |

---

## 3. Resumen exacto de cobertura de diseño vs. ejecución

### 3.1 Cobertura de diseño
- **Requisitos en el alcance**: 18 de 18 con familias asociadas (**100.0% de trazabilidad de diseño**; no acredita ejecución ni contratos confirmados).
- **Familias de casos de prueba (CP)**: 34 de 34 planificadas (**100.0%**).
- **Escenarios de prueba canónicos desarrollados**: **111 escenarios únicos descritos y referenciados en el catálogo; las decisiones contractuales pendientes se identifican en su sección 1.1**.
  - **Clasificación por comportamiento esperado**:
    - **Positivos (+)**: 56 escenarios (48 clasificados como Positivo, 5 de Rendimiento y 3 de CI; estos últimos se agrupan como verificaciones de comportamiento esperado).
    - **Negativos (-)**: 55 escenarios (rechazos de validación, violaciones de permisos, conflictos de concurrencia e indisponibilidad).
  - **Clasificación verificable por encabezado del catálogo**:
    - Positivo: 48 escenarios.
    - Negativo: 55 escenarios.
    - Rendimiento: 5 escenarios.
    - CI y trazabilidad: 3 escenarios.
    - Total: **111 escenarios**. Estas categorías son excluyentes para el recuento; no sustituyen el campo Tipo de cada escenario.
- **Distribución de familias por herramienta primaria (34 familias)**:
  - **Karate**: 19 familias (CP-01, CP-02, CP-03, CP-04, CP-05, CP-06, CP-07, CP-08, CP-09, CP-10, CP-11, CP-12, CP-15, CP-16, CP-17, CP-18, CP-20, CP-21, CP-22) — *55.9%*.
  - **Appium**: 7 familias (CP-24, CP-25, CP-26, CP-27, CP-28, CP-29, CP-30) — *20.6%*.
  - **JMeter**: 6 familias (CP-13, CP-14, CP-19, CP-31, CP-32, CP-33) — *17.6%*.
  - **Selenium**: 1 familia primaria (CP-23 E2E integrado; también actúa como herramienta complementaria según las filas de esta matriz) — *2.9%*.
  - **GitHub Actions (CI transversal)**: 1 familia (CP-34) — *2.9%*.
  - **Total**: 19 + 7 + 6 + 1 + 1 = **34 familias (100.0%)**.

### 3.2 Cobertura de ejecución (Estado actual M0)
- **Ejecutado con evidencia formal previa**: Pruebas Unitarias y MySQL base (QA-02: 564 pruebas, 546 aprobadas, 18 fallidas en búsqueda/contrato).
- **Suites obligatorias del curso (Karate, Selenium, Appium, JMeter)**: **0 ejecutados (Todos los 111 escenarios se encuentran formalmente en estado `PENDIENTE`)**.
- **Defectos abiertos conocidos**: 2 hallazgos en QA-02 (`QA02-H01` en `BusquedaVacantesMySqlTest` y `QA02-H02` en `DetalleVacanteMySqlTest`, asignados para resolución en QA-07).

---

## 4. Trazabilidad académica y Jira

La cobertura de los requisitos funcionales no sustituye la
verificación de los criterios académicos.

Consultar:

- [Rúbrica de evaluación](../RUBRICA_EVALUACION.md).
- [Matriz de cumplimiento académico](MATRIZ_RUBRICA.md).
- [Catálogo de casos](CATALOGO_CASOS_PRUEBA.md).

### Cadena de trazabilidad ampliada

Requisito -> Familia CP -> Escenario CP -> Control RUB ->
Herramienta -> Ticket Jira -> Issue GitHub -> Script ->
Commit/build -> Ejecución -> Reporte -> Resultado.

Cuando corresponda, se incorpora el defecto, la corrección,
el retest y la regresión.

### Estado actual

- 18 requisitos definidos.
- 34 familias de casos de prueba.
- 111 escenarios diseñados.
- 24 controles académicos RUB.
- Tickets Jira pendientes de vinculación real.
- Ejecuciones de las cuatro suites pendientes de acreditación.

El APK Android se verificará mediante Capacitor y Appium.
La PWA conservará sus comprobaciones independientes de
instalación, actualización y comportamiento offline.

Los porcentajes de diseño no representan una calificación
obtenida ni resultados de automatización aprobados.


<!-- QA04-POST-QA05 -->

## Seguimiento de ejecuciones QA-05

### Versiones verificadas

| Repositorio | PR | Commit probado | Merge commit |
|---|---|---|---|
| Backend | 33 | 4bb1b1c | 6d7d0a9 |
| Frontend | 19 | 738210c | 9e43699 |

### Resultados

| Herramienta | Resultado |
|---|---|
| PHPUnit Auth | 55 pruebas, 583 aserciones PASS |
| PHPUnit Unit/Feature | 259 pruebas, 1645 aserciones PASS |
| PHPUnit MySQL Propiedad | 20 pruebas, 166 aserciones PASS |
| PHPUnit total sin duplicar | 279 pruebas, 1811 aserciones PASS |
| Node Test | 12/12 PASS |
| Python HTTP | 24/24 PASS |
| Playwright | 12/12 PASS |
| Compilaciones Angular | 3/3 PASS |

Auth está incluida en Unit/Feature.
No se declara ejecutada toda la suite MySQL.

### Trazabilidad de regresiones auxiliares

| ID | Requisito | Caso relacionado | Cobertura | Resultado |
|---|---|---|---|---|
| W01 | RF-02, RF-03 | CP-03-01, CP-06-01 | Login y perfil candidato | PASS |
| W02 | RF-02, RF-03 | CP-03-02, CP-06 | Login y perfil empresa | PASS |
| W03 | RF-02 | CP-03-03 | Login administrador | PASS |
| W04 | RF-02 | CP-03 | Persistencia tras recarga | PASS |
| W05 | RF-02 | CP-05-03 | Token inválido | PASS |
| W06 | RF-02 | CP-04-03 | Cuenta suspendida | PASS |
| W07 | RNF-01 | CP-15 | Navegación restringida | PASS |
| W08 | RF-02 | CP-05-01, CP-05-02 | Logout candidato | PASS |
| W09 | RF-02 | CP-05 | Logout empresa | PASS |
| W10 | RF-02 | CP-05 | Logout admin | PASS |
| W11 | RF-02, RNF-01 | CP-04-03, CP-05-02 | Suspensión y aislamiento | PASS |
| W12 | RF-02 | CP-03-01, CP-04 | Reactivación y login | PASS |

Las relaciones anteriores indican cobertura complementaria,
no aprobación completa del caso canónico.

### Contratos adicionales verificados

| Caso | Herramienta | Resultado auxiliar |
|---|---|---|
| CP-04-01 | PHPUnit | PASS |
| CP-04-02 | PHPUnit | PASS |
| CP-06-03 | PHPUnit/MySQL | PASS |
| CP-06-04 | PHPUnit/MySQL | PASS |
| CP-15-01 | PHPUnit | PASS |
| CP-15-02 | PHPUnit/MySQL (GET y PATCH); HTTP (GET) | PASS |
| CP-15-04 | PHPUnit/MySQL | PASS |

### Evidencias versionadas

- [Backend QA-05](../evidencias/QA-05/RESULTADOS.md).
- [Regresión backend](../evidencias/QA-05/regresion-sesiones/).
- [Frontend QA-05](https://github.com/stephany-toribio/web-app-calidad-software/blob/9e43699/docs/evidencias/QA-05/REGRESION_FINAL.md).
- [Reporte navegador](https://github.com/stephany-toribio/web-app-calidad-software/blob/9e43699/docs/evidencias/QA-05/regresion-wsl/browser/resultados-browser.json).

### Ejecución académica pendiente

Karate, Selenium, JMeter, Appium y vinculación real
en Jira mantienen sus propios estados de ejecución.

Las pruebas auxiliares no sustituyen la rúbrica.

## Anexo - Cobertura auxiliar QA-07

<!-- QA07-TRAZABILIDAD -->

| Familia | Evidencia auxiliar | Resultado |
|---|---|---|
| CP-07 | RegistrarVacanteHttpMySqlTest y validaciones | PASS |
| CP-08 | Registro y moderacion HTTP | PASS |
| CP-09 | Pago simulado y validaciones HTTP | PASS |
| CP-10 | DisponibilidadVacanteMySqlTest y VacanteCompletaMySqlTest | PASS |
| CP-11 | BusquedaVacantesMySqlTest y DetalleVacanteMySqlTest | PASS |

Resultados de regresion backend: 643/643 pruebas,
4793 aserciones, cero fallos.

Resultados auxiliares frontend: 26/26 pruebas,
incluyendo 14 pruebas nuevas de QA-07.

Referencia: docs/evidencias/QA-07/RESULTADOS.md.

Los resultados corresponden a cobertura auxiliar
de las familias, no a una certificacion individual
de todos los casos canonicos mediante cada herramienta.

Se conservan los estados de ejecucion formal de Karate,
Selenium, Appium y JMeter.

## Anexo QA-06 - Evidencia de implementacion

<!-- QA06-TRAZABILIDAD -->

| Familia | Backend | Frontend | Automatizacion formal |
| --- | --- | --- | --- |
| CP-01 | Integracion PASS | Metodos auxiliares PASS | Pendiente |
| CP-02 | Integracion PASS | Metodos auxiliares PASS | Pendiente |
| CP-06 | Integracion PASS | Metodos auxiliares PASS | Pendiente |

Evidencia complementaria:
docs/evidencias/QA-06/RESULTADOS.md.

Se conserva la evidencia de propiedad obtenida en QA-05.

La validacion E2E, visual y de accesibilidad permanece
pendiente en las tareas correspondientes.

## Anexo QA-08 - Evidencia de implementacion

<!-- QA08-TRAZABILIDAD -->

| Caso | Evidencia de integracion |
| --- | --- |
| CP-12-01 | Creacion en recibido y persistencia |
| CP-12-02 | Candidato suspendido |
| CP-13-01 | Duplicado secuencial |
| CP-13-02 | HTTP Kernel concurrente: 201 y 409 |
| CP-14-01 | HTTP Kernel concurrente: 201 y 201 |
| CP-14-02 | Seis postulantes y un puesto |
| CP-20-01 | Retiro propio y conservacion de etapa |
| CP-20-02 | Retiro ajeno rechazado |
| CP-20-03 | Repostulacion rechazada |
| CP-20-04 | Retiro desde etapa terminal rechazado |
| CP-21-01 | Listado propio, privacidad y retiro |
| CP-21-02 | Vacantes pausadas y eliminadas |

Resultado de integracion: PASS para los escenarios
evaluados.

Evidencia: docs/evidencias/QA-08/RESULTADOS.md.

Las ejecuciones formales Karate, JMeter, Selenium
y Appium permanecen pendientes.

## Anexo QA-09 - Evidencias de implementacion

<!-- QA09-TRAZABILIDAD -->

| Familia | Cobertura |
| --- | --- |
| CP-16 | Avance y contratacion |
| CP-17 | Rechazo y motivo obligatorio |
| CP-18 | Transiciones invalidas y retiradas |
| CP-19 | Contrataciones concurrentes |
| CP-21 | Seguimiento del candidato |
| CP-22 | Historial, motivos y retiros |

Evidencias backend:

- docs/evidencias/QA-09/RESULTADOS.md.
- tests/Feature/Postulaciones/KanbanTest.php.
- tests/MySql/RetirarPostulacionConcurrenciaMySqlTest.php.
- tests/Support/hu16_kanban_worker.php.

Evidencias frontend:

- tests/qa09/kanban.test.mjs.
- tests/qa09/historial.test.mjs.
- docs/evidencias/QA-09/RESULTADOS.md.

Resultado de regresion: 843 pruebas aprobadas.

Las pruebas formales de Karate, Selenium, Appium
y JMeter continuan pendientes.

## Anexo QA-14 - Infraestructura Karate

<!-- QA14-TRAZABILIDAD -->

| Identificador | Evidencia | Resultado |
| --- | --- | --- |
| QA14-S01 | Health y call | PASS |
| QA14-S02 | Recurso protegido sin token | PASS |
| QA14-S03 | Validacion de login | PASS |
| QA14-S04 | Login, consulta, logout y revocacion | PASS |

Estos identificadores son comprobaciones de
infraestructura y no reemplazan los casos
canonicos del catalogo QA-04.

RUB-K01, RUB-K02, RUB-K03, RUB-K04 y RUB-K05
cuentan con evidencia inicial.

Evidencias: docs/evidencias/QA-14/RESULTADOS.md.

## Resultados Karate - QA-15

| Caso | Feature | Escenarios | Karate |
| --- | --- | ---: | --- |
| CP-01-01 | registro-candidato.feature | 1 | PASS |
| CP-01-02 | registro-candidato.feature | 3 | PASS |
| CP-01-03 | registro-candidato.feature | 13 | PASS |
| CP-02-01 | registro-empresa.feature | 1 | PASS |
| CP-02-02 | registro-empresa.feature | 2 | PASS |
| CP-02-03 | registro-empresa.feature | 9 | PASS |
| CP-03-01 | login.feature | 1 | PASS |
| CP-03-02 | login.feature | 1 | PASS |
| CP-03-03 | login.feature | 1 | PASS |
| CP-04-01 | bloqueo-suspension.feature | 1 | PASS |
| CP-04-02 | bloqueo-suspension.feature | 1 | PASS |
| CP-04-03 | bloqueo-suspension.feature | 1 | PASS |
| CP-05-01 | sesiones.feature | 1 | PASS |
| CP-05-02 | sesiones.feature | 1 | PASS |
| CP-05-03 | sesiones.feature | 1 | PASS |
| QA15-EXT-01 | sesiones.feature | 1 | PASS |

Evidencia: [Resultados QA-15](../evidencias/QA-15/RESULTADOS.md).

Issue: #19.

Los estados corresponden exclusivamente a Karate.
No sustituyen las validaciones mediante otras herramientas.

## Resultados Karate - QA-16

<!-- QA16-TRAZABILIDAD -->

| Caso | Feature | Escenarios | Karate |
| --- | --- | ---: | --- |
| CP-07-01 | vacantes.feature | 1 | PASS |
| CP-07-02 | vacantes.feature | 1 | PASS |
| CP-07-03 | vacantes.feature | 1 | PASS |
| CP-07-04 | vacantes.feature | 1 | PASS |
| CP-08-01 | moderacion.feature | 1 | PASS |
| CP-08-02 | moderacion.feature | 1 | PASS |
| CP-08-03 | moderacion.feature | 2 | PASS |
| CP-08-04 | moderacion.feature | 1 | PASS |
| CP-09-01 | pagos.feature | 1 | PASS |
| CP-09-02 | pagos.feature | 2 | PASS |
| CP-09-03 | pagos.feature | 4 | PASS |
| CP-09-04 | pagos.feature | 1 | PASS |
| CP-09-05 | pagos.feature | 1 | PASS |
| CP-12-01 | postulaciones.feature | 1 | PASS |
| CP-12-02 | postulaciones.feature | 1 | PASS |
| CP-16-01 | contratacion.feature | 1 | PASS |
| CP-16-02 | contratacion.feature | 1 | PASS |
| CP-16-03 | contratacion.feature | 1 | PASS |
| CP-17-01 | rechazo.feature | 1 | PASS |
| CP-17-02 | rechazo.feature | 1 | PASS |
| CP-17-03 | rechazo.feature | 2 | PASS |
| CP-21-01 | seguimiento.feature | 1 | PASS |
| CP-21-02 | seguimiento.feature | 2 | PASS |
| CP-22-01 | historial.feature | 1 | PASS |
| CP-22-02 | historial.feature | 1 | PASS |
| CP-22-03 | historial.feature | 1 | PASS |
| QA16-E2E-01 | flujo-integral.feature | 1 | PASS |

Issue GitHub: #20.

Evidencia: [Resultados QA-16](../evidencias/QA-16/RESULTADOS.md).

Cobertura: 26 casos canonicos y 34 escenarios.

Los estados corresponden exclusivamente a Karate.
No sustituyen Selenium, Appium, JMeter ni Jira.

<!-- QA17-CIERRE -->

## Resultados Karate QA-17

| Caso | Feature | Resultado Karate |
| --- | --- | --- |
| CP-06-01 | perfiles.feature | PASS |
| CP-06-02 | perfiles.feature | PASS |
| CP-06-03 | perfiles.feature | PASS |
| CP-06-04 | perfiles.feature | PASS |
| CP-10-01 | disponibilidad.feature | PASS |
| CP-10-02 | disponibilidad.feature | PASS |
| CP-10-03 | disponibilidad.feature | PASS |
| CP-10-04 | disponibilidad.feature | PASS |
| CP-10-05 | disponibilidad.feature | PASS |
| CP-10-06 | disponibilidad.feature | PASS |
| CP-10-07 | disponibilidad.feature | PASS |
| CP-10-08 | disponibilidad.feature | PASS |
| CP-10-09 | disponibilidad.feature | PASS |
| CP-10-10 | disponibilidad.feature | PASS |
| CP-10-11 | disponibilidad.feature | PASS |
| CP-13-01 | duplicados.feature | PASS |
| CP-15-01 | autorizacion.feature | PASS |
| CP-15-02 | autorizacion.feature | PASS |
| CP-15-03 | autorizacion.feature | PASS |
| CP-15-04 | autorizacion.feature | PASS |
| CP-18-01 | transiciones-invalidas.feature | PASS |
| CP-18-02 | transiciones-invalidas.feature | PASS |
| CP-18-03 | transiciones-invalidas.feature | PASS |
| CP-20-01 | retiros.feature | PASS |
| CP-20-02 | retiros.feature | PASS |
| CP-20-03 | retiros.feature | PASS |
| CP-20-04 | retiros.feature | PASS |

Evidencia: [QA-17](../evidencias/QA-17/RESULTADOS.md).

CP-13-02: concurrencia no acreditada por Karate.
