# Matriz de Trazabilidad de Requisitos, Casos y Riesgos — QA-04

- **Proyecto**: Portal de Empleos CREO+ (PWA y API REST) — Calidad de Software.
- **Responsable del documento**: R3 (@luisvalenzuela25 — Integración, Appium, JMeter y trazabilidad).
- **Revisor asignado**: R1 (@stephany-toribio — Backend, API y Karate).
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.1.0 (Corregida tras revisión cruzada).
- **Fecha de versión**: 24/11/2026.
- **Rama**: `feature/QA-04-matriz-pruebas`.
- **Estado de ejecución global**: **Pendiente de ejecución** (salvo la línea base de pruebas unitarias/MySQL documentada en QA-02).

---

## 1. Alcance y convención de trazabilidad

Esta matriz relaciona bidireccionalmente los **18 requisitos** del sistema (10 RF, 4 PWA, 4 RNF) con las **34 familias de casos de prueba (CP-01 a CP-34)**, sus **94 escenarios detallados** (50 positivos y 44 negativos), su nivel de prioridad, las herramientas de automatización asignadas, las tareas e integrantes responsables, y los riesgos operacionales o técnicos asociados.

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
- **P0 (Crítico / Bloqueante)**: Autenticación, autorización de servidor, unicidad de postulación, integridad transaccional, concurrencia en contrataciones, cupos límite, estados finales y pérdida de conexión PWA.
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
| **RF-05** (Aprobación y pago simulado) | **CP-09** | CP-09-01 (+), CP-09-02 (-), CP-09-03 (-), CP-09-04 (-) | P1 | Karate | Selenium | QA-07, QA-16, QA-20 | R1, R3 | RSK-06, RSK-07 |
| **RF-06** (Búsqueda y disponibilidad) | **CP-10** | CP-10-01 (-), CP-10-02 (-), CP-10-03 (-), CP-10-04 (-) | P0 | Karate | PHPUnit/MySQL | QA-07, QA-17 | R1, R3 | RSK-06, RSK-08 |
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
| **PWA-01** (Instalación y apertura Android) | **CP-24** | CP-24-01 (+), CP-24-02 (+) | P1 | Appium | Android DevTools | QA-10, QA-21, QA-22 | R2, R3 | RSK-03, RSK-04 |
| **PWA-04** (Diseño móvil y accesibilidad) | **CP-25** | CP-25-01 (+), CP-25-02 (+) | P1 | Appium | Selenium | QA-12, QA-22 | R2, R3 | RSK-03, RSK-10 |
| **PWA-02** (Offline y privacidad caché) | **CP-26** | CP-26-01 (+), CP-26-02 (-), CP-26-03 (-) | P0 | Appium | Android DevTools | QA-11, QA-23 | R2, R3 | RSK-04, RSK-11 |
| **PWA-02** (Escritura con corte de red) | **CP-27** | CP-27-01 (-), CP-27-02 (+) | P0 | Appium | API REST | QA-11, QA-22, QA-23 | R2, R3 | RSK-04, RSK-05, RSK-11 |
| **PWA-03** (Actualización entre builds) | **CP-28** | CP-28-01 (+), CP-28-02 (+) | P1 | Appium | Navegador Android | QA-11, QA-23 | R2, R3 | RSK-04, RSK-10 |
| **PWA-01..04, RF-02,06,07** (Flujo móvil) | **CP-29** | CP-29-01 (+), CP-29-02 (+) | P0 | Appium | API REST | QA-21, QA-22 | R3 | RSK-03, RSK-04, RSK-05 |
| **PWA-02..03, RF-02** (Sesión móvil) | **CP-30** | CP-30-01 (+), CP-30-02 (-), CP-30-03 (-) | P0 | Appium | API REST | QA-23 | R2, R3 | RSK-01, RSK-04 |
| **RNF-01** (Autorización de servidor) | **CP-15** | CP-15-01 (-), CP-15-02 (-), CP-15-03 (-), CP-15-04 (-) | P0 | Karate | Selenium | QA-05, QA-17, QA-20 | R1, R3 | RSK-01, RSK-02 |
| **RNF-02** (Integridad y concurrencia) | *CP-13, CP-14, CP-19* | *Ver CP-13-02 (-), CP-14-01 (+), CP-19-01 (-)* | P0 | JMeter | PHPUnit/MySQL | QA-08, QA-09, QA-25 | R1, R2, R3 | RSK-05, RSK-07, RSK-09 |
| **RNF-03** (Carga y rendimiento) | **CP-31** | CP-31-01 (+), CP-31-02 (+) | P1 | JMeter | Monitor Docker | QA-24, QA-25, QA-26 | R3, R1 | RSK-09, RSK-12 |
| **RNF-03** (Carga y rendimiento) | **CP-32** | CP-32-01 (+), CP-32-02 (-) | P1 | JMeter | API REST | QA-24, QA-25, QA-26 | R3, R1 | RSK-01, RSK-09 |
| **RNF-03** (Carga y rendimiento) | **CP-33** | CP-33-01 (+), CP-33-02 (+) | P0 | JMeter | PHPUnit/MySQL | QA-24, QA-25, QA-26 | R3, R1 | RSK-07, RSK-09 |
| **RNF-04** (CI y reproducibilidad) | **CP-34** | CP-34-01 (+), CP-34-02 (+) | P0 | GitHub Actions | Docker / Shell | QA-27, QA-28, QA-30, QA-31 | R1, R2, R3 | RSK-02, RSK-10 |

---

## 3. Resumen exacto de cobertura de diseño vs. ejecución

### 3.1 Cobertura de diseño
- **Requisitos en el alcance**: 18 de 18 cubiertos (**100.0%**).
- **Familias de casos de prueba (CP)**: 34 de 34 planificadas (**100.0%**).
- **Escenarios de prueba canónicos detallados**: **94 escenarios únicos desarrollados al 100% en el catálogo**.
  - **Positivos (+)**: 50 escenarios.
  - **Negativos (-)**: 44 escenarios.
- **Distribución de familias por herramienta primaria (34 familias)**:
  - **Karate**: 19 familias (CP-01, CP-02, CP-03, CP-04, CP-05, CP-06, CP-07, CP-08, CP-09, CP-10, CP-11, CP-12, CP-15, CP-16, CP-17, CP-18, CP-20, CP-21, CP-22) — *55.9%*.
  - **Appium**: 7 familias (CP-24, CP-25, CP-26, CP-27, CP-28, CP-29, CP-30) — *20.6%*.
  - **JMeter**: 6 familias (CP-13, CP-14, CP-19, CP-31, CP-32, CP-33) — *17.6%*.
  - **Selenium**: 1 familia primaria (CP-23 E2E integrado; actúa además como soporte complementario en 15 familias) — *2.9%*.
  - **GitHub Actions (CI transversal)**: 1 familia (CP-34) — *2.9%*.
  - **Total**: 19 + 7 + 6 + 1 + 1 = **34 familias (100.0%)**.

### 3.2 Cobertura de ejecución (Estado actual M0)
- **Ejecutado con evidencia formal previa**: Pruebas Unitarias y MySQL base (QA-02: 564 pruebas, 546 aprobadas, 18 fallidas en búsqueda/contrato).
- **Suites obligatorias del curso (Karate, Selenium, Appium, JMeter)**: **0 ejecutados (Todos los 94 escenarios se encuentran formalmente en estado `PENDIENTE`)**.
- **Defectos abiertos conocidos**: 2 hallazgos en QA-02 (`QA02-H01` en `BusquedaVacantesMySqlTest` y `QA02-H02` en `DetalleVacanteMySqlTest`, asignados para resolución en QA-07).
