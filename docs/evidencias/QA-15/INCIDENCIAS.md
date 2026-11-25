# Incidencias QA-15

## QA15-T01 - Generacion de password incompatible

- Tipo: defecto de datos de prueba.
- Escenarios afectados: CP-01-01 y CP-02-01.
- Resultado inicial: 0/2 escenarios aprobados.
- HTTP esperado: 201.
- HTTP obtenido: 422.
- Regla revisada: app/Rules/PasswordSegura.php.
- Hallazgo: el generador utiliza un simbolo
  que no pertenece al conjunto permitido.
- Correccion: utilizar el caracter # y conservar
  la generacion aleatoria de la password.
- Codigo productivo: sin modificaciones.
- Evidencia RED: registro/primera-ejecucion.txt.
- Evidencia del retest: registro/retest-01.txt.
- Retest: 2/2 escenarios aprobados.
- HTTP obtenido tras correccion: 201.
- Esquemas JSON completos: PASS.
- Persistencia MySQL: PASS.
- Escaneo de secretos: PASS.
- Fixtures eliminados: 2.
- Fixtures restantes: 0.
- Estado: resuelto.

## QA15-T02 - Asercion dinamica de duplicados

- Tipo: defecto del script de automatizacion.
- Casos afectados: CP-01-02 y CP-02-02.
- Variantes afectadas: 5.
- Resultado inicial: 24/29 escenarios aprobados.
- Causa confirmada: interpretacion literal de campo
  dentro del arreglo esperado por match.
- Comportamiento API: rechazo correcto de duplicados
  mediante HTTP 422.
- Correccion: construir dinamicamente el arreglo
  esperado antes de ejecutar match.
- Se conserva la comparacion estricta de campos.
- Codigo productivo Laravel: sin modificaciones.
- Escaneo preventivo de secretos: PASS.
- Fixtures eliminados en ejecucion RED: 7.
- Fixtures restantes: 0.
- Evidencia RED: registro/regresion-ampliada.txt.
- Retest: 29/29 escenarios aprobados.
- Candidatos: 17/17 PASS.
- Empresas: 12/12 PASS.
- Escaneo de secretos: PASS.
- Fixtures eliminados: 7.
- Fixtures restantes: 0.
- Evidencia GREEN: registro/retest-ampliado.txt.
- Estado: resuelto.
