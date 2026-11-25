# Registro de incidencias QA-16

## QA16-T01 - Error de sintaxis en fixture de negocio

- Tipo: defecto de infraestructura de automatizacion.
- Componente: fixture-qa16.php.
- Linea afectada: 436.
- Causa: barras invertidas duplicadas en la referencia
  al namespace del modelo Usuario.
- Resultado inicial: 0/4 escenarios aprobados.
- Maven: BUILD FAILURE.
- Escaneo preventivo de secretos: PASS.
- Limpieza automatica inicial: FAIL.
- Correccion: importar Usuario y utilizar el nombre
  simple del modelo.
- Recuperacion: limpieza restringida al UUID afectado.
- Evidencia RED: vacantes/primera-ejecucion.txt.
- Evidencia de recuperacion: recuperacion/.
- Codigo productivo Laravel: sin modificaciones.
- Retest funcional: 4/4 escenarios aprobados.
- Tiempo runner JUnit: 21.52 segundos.
- Maven: BUILD SUCCESS.
- Limpieza transaccional: PASS.
- Escaneo preventivo de secretos: PASS.
- Recuperacion del UUID anterior: PASS.
- Control preventivo: validacion de sintaxis PHP
  antes de iniciar Karate.
- Evidencia GREEN: vacantes/retest-01.txt.
- Estado: RESUELTO.

## QA16-T02 - Interpretacion incorrecta de longitud de arreglos

- Tipo: defecto de infraestructura de automatizacion.
- Componente: mover-postulacion.feature.
- Linea afectada: 43.
- Causa: uso de match para evaluar la propiedad length
  de un arreglo como expresion JavaScript.
- Error: actual path does not exist (#notpresent).
- Resultado RED contratacion: 0/3 escenarios.
- Resultado RED rechazo: 0/4 escenarios.
- Resultado acumulado RED: 0/7 escenarios.
- Las transiciones iniciales generaron movimientos
  persistidos en MySQL.
- Limpieza transaccional RED: PASS.
- Escaneo preventivo de secretos RED: PASS.
- Correccion: utilizar assert para evaluar la longitud.
- Correccion preventiva: dos aserciones adicionales
  en contratacion.feature.
- Codigo productivo Laravel: sin modificaciones.
- Evidencias RED:
  - contratacion/primera-ejecucion.txt
  - rechazo/primera-ejecucion.txt
- Evidencia de retest: kanban/retest-01.txt.
- Retest GREEN contratacion: 3/3 escenarios.
- Retest GREEN rechazo: 4/4 escenarios.
- Resultado acumulado: 7/7 escenarios aprobados.
- Tiempo runner JUnit: 118.5 segundos.
- Maven: BUILD SUCCESS.
- Movimientos historicos eliminados: 13.
- Notificaciones eliminadas: 3.
- Usuarios temporales eliminados: 21.
- Usuarios residuales: 0.
- Limpieza transaccional: PASS.
- Escaneo preventivo de secretos: PASS.
- Estado: RESUELTO.

## QA16-D01 - Diferencia documental del motivo de rechazo

- Tipo: discrepancia de contrato documentado.
- Caso afectado: CP-22-02.
- Catalogo: motivo_rechazo como objeto con ID y texto.
- Implementacion: motivo_rechazo como texto en la API.
- Persistencia: motivo_rechazo_id como clave foranea.
- Estrategia: verificar el texto mediante Karate y el
  identificador relacionado mediante consulta MySQL.
- No se modifica el contrato productivo.
- Retest de historial: 3/3 escenarios aprobados.
- Texto del motivo verificado mediante API.
- Identificador del motivo verificado en MySQL.
- Catalogo actualizado segun contrato implementado.
- Estado: CONCILIADO.

## QA16-T03 - Tokens en argumentos callArg

- Tipo: defecto de seguridad de la automatizacion.
- Componente: historial.feature.
- Hallazgo: cuatro ubicaciones con tokens en reporte JSON.
- Causa: transferencia de objetos de autenticacion
  y tokens como argumentos explicitos de helpers.
- Correccion: utilizar variables heredadas y
  argumentos sin credenciales.
- Retest funcional: 3/3 escenarios aprobados.
- Escaneo preventivo de secretos: PASS.
- Limpieza transaccional: PASS.
- Tokens temporales eliminados: 10.
- Usuarios residuales: 0.
- Tiempo runner JUnit: 66.95 segundos.
- Evidencia GREEN:
  seguimiento-historial/retest-historial-01.txt.
- Reportes RED sensibles: privados y no publicables.
- Estado: RESUELTO.

## QA16-T04 - Conflicto de contexto entre helpers

- Tipo: defecto de automatizacion.
- Caso afectado: CP-21-01.
- Error: identificadores de empresa diferentes.
- Correccion: reutilizar empresa y administrador
  para crear una segunda vacante independiente.
- Retest definitivo: 3/3 escenarios aprobados.
- Escaneo preventivo de secretos: PASS.
- Limpieza transaccional: PASS.
- Estado: RESUELTO.

## QA16-T05 - Metodo HTTP dinamico incorrecto

- Tipo: probable defecto de automatizacion.
- Caso afectado: CP-21-02.
- Resultado RED: HTTP 501 en pausa y eliminacion.
- Resultado esperado: HTTP 200.
- Correccion propuesta: helpers con PATCH y DELETE
  explicitos, verificando persistencia en MySQL.
- Retest definitivo: 3/3 escenarios aprobados.
- Escaneo preventivo de secretos: PASS.
- Limpieza transaccional: PASS.
- Estado: RESUELTO.

## QA16-T06 - Variables interpretadas como cadenas

- Tipo: defecto de automatizacion.
- Caso afectado: CP-21-01.
- Componente: seguimiento.feature.
- Error: contains only compara numeros con cadenas.
- Causa: construccion de arreglo sin evaluacion
  de los identificadores dinamicos.
- Resultado previo: 2/3 escenarios aprobados.
- Correccion: construir arreglo mediante eval y push.
- Control adicional: verificar tipos numericos
  e identificadores diferentes.
- Retest GREEN: 3/3 escenarios aprobados.
- Escaneo preventivo de secretos: PASS.
- Limpieza transaccional: PASS.
- Evidencia:
  seguimiento-historial/retest-seguimiento-02.txt.
- Codigo productivo Laravel: sin modificaciones.
- Estado: RESUELTO.
