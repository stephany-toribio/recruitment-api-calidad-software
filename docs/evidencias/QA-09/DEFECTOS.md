# Registro de hallazgos - QA-09

## Identificacion

- Issue: stephany-toribio/web-app-calidad-software#5.
- Responsable: stephany-toribio.
- Prioridad: P0.
- Rama: feature/QA-09-kanban-decisiones.

## Brechas de cobertura backend

| ID | Hallazgo | Estado |
| --- | --- | --- |
| QA09-C01 | Suite Kanban principal utiliza SQLite | Cobertura ampliada con MySQL |
| QA09-C02 | Contrataciones concurrentes con un puesto | PASS CP-19-01 |
| QA09-C03 | Contrataciones concurrentes con dos puestos | PASS CP-19-02 |
| QA09-C04 | Historial y decisiones en MySQL real | PASS cuatro pruebas de integracion |
## Hallazgos frontend

| ID | Hallazgo | Estado |
| --- | --- | --- |
| QA09-F01 | Doble avance | RED-GREEN PASS F01 |
| QA09-F02 | Doble rechazo | RED-GREEN PASS F02 |
| QA09-F03 | Guarda de solo lectura insuficiente | RED-GREEN PASS F03 |
| QA09-F04 | Respuestas antiguas del tablero | RED-GREEN PASS F04 |
| QA09-F05 | Respuestas antiguas del perfil | RED-GREEN PASS F05 y F16 |
| QA09-F06 | Cierre durante rechazo | RED-GREEN PASS F06 |
| QA09-F07 | Conflictos sin reconciliacion | RED-GREEN PASS F07 |
| QA09-F08 | Perdida de solo lectura de ruta | RED-GREEN PASS F08 |
| QA09-F09 | Motivo seleccionado automaticamente | RED-GREEN PASS F09 y F14 |
| QA09-F10 | Suscripciones sin control de ciclo de vida | Control incorporado; E2E pendiente |
| QA09-F11 | Mensajeria simulada | Mensajeria simulada eliminada |
## Consideraciones

No se modificaran reglas funcionales sin evidencia.

La concurrencia MySQL no se acreditara mediante SQLite.

Las pruebas auxiliares no sustituyen Selenium.

## Incidencia tecnica QA09-T01

- Tipo: error en script de preparacion.
- Causa: interpretacion Unicode de namespaces PHP en Python.
- Resultado inicial: No tests executed.
- Correccion: uso de cadenas raw.
- Impacto funcional sobre el producto: ninguno identificado.
- Evidencia del retest: backend/concurrencia/http-retest-01.txt.
- Resultado del retest: PASS, 3 pruebas y 59 aserciones.
- Regresion de concurrencia: PASS, 7 pruebas y 102 aserciones.
- Estado: resuelto.


## Incidencia tecnica QA09-T02

- Tipo: preparacion incompleta de fixtures MySQL.
- Resultado inicial: 4 errores de ejecucion.
- Excepcion: ModelNotFoundException.
- Causa: catalogo incompleto de estados de postulacion.
- Correccion: inicializar EstadoPostulacion::ORDEN.
- Limpieza: reutilizar el mecanismo catalogoId y tearDown.
- Evidencia inicial: backend/historial/integracion.txt.
- Evidencia del retest: backend/historial/integracion-retest-01.txt.
- Resultado: PASS, 4 pruebas y 45 aserciones.
- Suite ampliada: PASS, 11 pruebas y 147 aserciones.
- Estado: resuelto.


## Resultados consolidados QA-09

- RED frontend: 4 aprobadas y 12 fallidas.
- GREEN frontend: 16 aprobadas.
- QA-09 frontend final: 32 aprobadas.
- Backend: 708 pruebas y 5748 aserciones.
- Frontend: 135 pruebas.
- Total: 843 pruebas aprobadas.
- Nuevas pruebas: 39.

## Controles complementarios

- F15: pertenencia a la vacante.
- F17-F22: decisiones, errores y arrastre.
- H01-H10: consulta y consistencia del historial.

## Riesgos residuales

La validacion E2E visual, accesibilidad,
notificaciones reales y carga por red se
mantienen fuera de la evidencia ejecutada.
