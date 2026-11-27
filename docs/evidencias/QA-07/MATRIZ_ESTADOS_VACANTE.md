# Matriz de disponibilidad de vacantes - QA-07

## Objetivo

Documentar las reglas de elegibilidad de una vacante
y relacionarlas con las pruebas auxiliares aprobadas.

## Condiciones

Para aparecer en la busqueda, una vacante debe:

1. No estar eliminada logicamente.
2. Tener estado_empleo activa.
3. Haber iniciado su vigencia.
4. No haber superado su fecha final de vigencia.
5. Tener puestos disponibles.
6. Contar con revision aprobada vigente y pago exitoso.
7. Pertenecer a una empresa activa.

## Matriz de escenarios

| Caso | Condicion | Disponible | Evidencia auxiliar |
|---|---|---|---|
| CP-11-01 | Cumple todas las condiciones | Si | DisponibilidadVacanteMySqlTest |
| CP-10-01 | Sin revision | No | DisponibilidadVacanteMySqlTest |
| CP-10-02 | No aprobada | No | DisponibilidadVacanteMySqlTest |
| CP-10-03 | Sin pago | No | DisponibilidadVacanteMySqlTest |
| CP-10-04 | Solo pago fallido | No | DisponibilidadVacanteMySqlTest |
| CP-10-05 | Pausada | No | DisponibilidadVacanteMySqlTest |
| CP-10-06 | Finalizada | No | DisponibilidadVacanteMySqlTest |
| CP-10-07 | Vigencia vencida | No | DisponibilidadVacanteMySqlTest |
| CP-10-08 | Vigencia futura | No | DisponibilidadVacanteMySqlTest |
| CP-10-09 | Cupos agotados | No | VacanteCompletaMySqlTest |
| CP-10-10 | Empresa suspendida | No | DisponibilidadVacanteMySqlTest |
| CP-10-11 | Eliminada | No | DisponibilidadVacanteMySqlTest |

## Condiciones complementarias

- Un pago cancelado sin pago exitoso no habilita la vacante.
- Un intento fallido posterior no anula un pago exitoso.
- Un candidato rechazado no ocupa un puesto.
- La vigencia se evalua en la zona America/Lima.
- Las fechas inicial y final incluyen sus dias completos.
- Una vacante con al menos un puesto libre puede continuar disponible.
- La consulta historica no equivale a permitir postulaciones.

## Resultados verificados

- DisponibilidadVacanteMySqlTest: 21/21 PASS.
- VacanteCompletaMySqlTest: incluida en regresion MySQL aprobada.
- BusquedaVacantesMySqlTest: 90/90 PASS.
- DetalleVacanteMySqlTest: 35/35 PASS.
- Regresion MySQL anterior a las 17 pruebas nuevas: 367/367 PASS.

## Evidencias

- backend/retest-01/DisponibilidadVacanteMySqlTest.txt
- backend/retest-01/BusquedaVacantesMySqlTest.txt
- backend/retest-01/DetalleVacanteMySqlTest.txt
- backend/regresion-completa/mysql.txt
- backend/gestion-pagos/complementarias.txt

## Limitaciones

Esta matriz acredita cobertura auxiliar PHPUnit/MySQL.

Los casos canonicos mantienen sus estados por herramienta.
La comprobacion HTTP de postulaciones se documentara
segun sus pruebas especificas y el alcance de QA-08.

La ejecucion formal mediante Karate, Selenium y Appium
permanece pendiente.

La vinculacion de evidencias a Jira se realizara en QA-29.
