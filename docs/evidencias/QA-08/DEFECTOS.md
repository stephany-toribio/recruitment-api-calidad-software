# Registro de hallazgos - QA-08

## Linea base

- Postulacion: 66 pruebas, 297 aserciones.
- Concurrencia de postulacion: 4 pruebas, 38 aserciones.
- Retiro: 46 pruebas, 232 aserciones.
- Concurrencia de retiro: 4 pruebas, 43 aserciones.
- Total: 120 pruebas y 610 aserciones.
- Fallos: 0.

## Brechas de cobertura

### QA08-C01

El worker original ejecuta directamente PostulacionService.
Demuestra concurrencia real en MySQL, pero los codigos
de resultado se construyen dentro del worker.

Accion: complementar con solicitudes procesadas por
el HTTP Kernel y tokens Sanctum.

### QA08-C02

Verificar postulaciones concurrentes de dos candidatos
diferentes sobre una vacante con un solo puesto.

### QA08-C03

La concurrencia de retiro existente fue ejecutada
correctamente: 4/4 PASS.

## Hallazgos frontend

| ID | Descripcion | Estado |
| --- | --- | --- |
| QA08-F01 | Cierre del modal libera bloqueo de postulacion | Reproduccion pendiente |
| QA08-F02 | Confirmacion no revalida elegibilidad | Reproduccion pendiente |
| QA08-F03 | Respuesta anterior puede modificar otra vacante | Reproduccion pendiente |
| QA08-F04 | Cierre del modal libera bloqueo de retiro | Reproduccion pendiente |
| QA08-F05 | Retiro no comprueba puede_retirar internamente | Reproduccion pendiente |
| QA08-F06 | Retiro no aplica etapa devuelta por API | Reproduccion pendiente |
| QA08-F07 | Refresco antiguo puede sobrescribir retiro | Reproduccion pendiente |
| QA08-F08 | Conflicto de retiro deja informacion desactualizada | Reproduccion pendiente |

## Consideraciones

Las pruebas auxiliares frontend no sustituyen Selenium.

Los procesos concurrentes con HTTP Kernel no equivalen
a pruebas de carga HTTP por red mediante JMeter.

No se utilizan cuentas ni credenciales reales.

---

## Resultados consolidados

| Hallazgo | Evidencia | Resultado |
| --- | --- | --- |
| QA08-C01 | CP-13-02 mediante HTTP Kernel | PASS |
| QA08-C02 | CP-14-01 mediante HTTP Kernel | PASS |
| QA08-C03 | RetirarPostulacionConcurrenciaMySqlTest | PASS |
| QA08-F01 | F02 | RED-GREEN PASS |
| QA08-F02 | F03, F04, F06 | RED-GREEN PASS |
| QA08-F03 | F07, F16 | Retest auxiliar PASS |
| QA08-F04 | F09 | RED-GREEN PASS |
| QA08-F05 | F10, F17 | Retest auxiliar PASS |
| QA08-F06 | F11 | RED-GREEN PASS |
| QA08-F07 | F21, F22, F23 | Verificacion auxiliar PASS |
| QA08-F08 | F18, F19, F25 | Verificacion auxiliar PASS |

## Regresion

- RED: 4 aprobadas y 8 fallidas.
- GREEN: 12 aprobadas y 0 fallidas.
- Suite ampliada frontend: 28/28.
- Backend: 701/701.
- Frontend: 103/103.
- Total: 804/804.
- Nuevas pruebas QA-08: 37.

## Riesgos residuales

El cierre tecnico de los hallazgos se limita
a las pruebas ejecutadas.

La validacion visual y E2E se mantiene pendiente
en Selenium y Appium.

La concurrencia por red y el rendimiento se
mantienen pendientes en JMeter.
