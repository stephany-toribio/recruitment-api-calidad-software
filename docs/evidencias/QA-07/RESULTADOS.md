# Resultados de QA-07

## 1. Identificacion

- Proyecto: Portal de Empleos CREO+.
- Tarea: QA-07.
- Issue: #15.
- Epica: E02.
- Hito: M1 - Flujo de negocio estable.
- Fecha: 25/11/2026.
- Responsable: stephany-toribio.
- Rama: feature/QA-07-vacantes-disponibilidad.
- Commit base: 813a4f9.

## 2. Alcance

Validar registro, moderacion, pago simulado,
publicacion, disponibilidad, busqueda y detalle.

Familias: CP-07, CP-08, CP-09, CP-10 y CP-11.

No se integra ninguna pasarela financiera real.

## 3. Ambiente

- WSL2 y Docker Compose.
- PHP 8.3.37.
- PHPUnit 11.5.56.
- MySQL 8.0.
- SQLite en memoria para Unit/Feature.
- Base MySQL aislada: portalempleoshu04testing.
- Backend QA: http://127.0.0.1:8002.
- Datos sinteticos y transacciones de prueba.

## 4. Hallazgos

| ID | Hallazgo | Resultado |
|---|---|---|
| QA02-H01 | Fixtures de busqueda incompatibles con cupos | Corregido |
| QA02-H02 | Contrato historico de detalle desactualizado | Corregido |
| QA07-B01 | Validacion Luhn no implementada | Corregido |

Consultar DEFECTOS.md para causas, correcciones y retests.

## 5. Regresion de hallazgos historicos

| Suite | Antes | Despues | Aserciones finales |
|---|---:|---:|---:|
| BusquedaVacantesMySqlTest | 73/90 | 90/90 | 920 |
| DetalleVacanteMySqlTest | 32/33 | 35/35 | 181 |
| DisponibilidadVacanteMySqlTest | 21/21 | 21/21 | 147 |
| Total | 126/144 | 146/146 | 1248 |

Se resolvieron los 18 fallos historicos.
Se incorporaron dos regresiones de detalle.

## 6. Defecto funcional de pago

Antes: HTTP 201 ante numero ficticio incompatible con Luhn.
Despues: HTTP 422, sin persistir el pago.

Evidencia RED y GREEN conservada.

## 7. Pruebas complementarias

- 17 pruebas nuevas de registro, moderacion y pago.
- 270 aserciones aprobadas.
- Clase completa de registro: 53/53 PASS.
- Aserciones de la clase: 787.

Cobertura:

- Aprobacion y no aprobacion.
- Motivo obligatorio de rechazo.
- Restricciones por rol.
- Revision duplicada.
- Datos de pago invalidos.
- Pago antes de aprobacion.
- Pago duplicado.
- Propiedad de pagos.
- Calculo del importe por servidor.
- Proteccion de datos de tarjeta en respuesta y BD.
- Invalidacion de aprobacion por edicion.
- Restricciones de edicion.
- Pausa, reanudacion y finalizacion.
- Eliminacion logica.

## 8. Regresion completa final

| Suite | Pruebas | Aserciones | Duracion | Resultado |
|---|---:|---:|---:|---|
| Unit/Feature | 259 | 1645 | 6.533 s | PASS |
| MySQL | 384 | 3148 | 41.527 s | PASS |
| Total | 643 | 4793 | 48.060 s | PASS |

Fallos: 0.
Nuevas pruebas backend de QA-07: 19.

No se contabilizan nuevamente los retests incluidos
en la regresion completa.

## 9. Evidencias

- DEFECTOS.md
- MATRIZ_ESTADOS_VACANTE.md
- backend/antes/
- backend/retest-01/
- backend/red/
- backend/green/
- backend/gestion-pagos/
- backend/regresion-final/

## 10. Integracion frontend

El repositorio frontend contiene cambios complementarios
en la rama feature/QA-07-vacantes-disponibilidad.

Resultados reportados:

- QA-05: 12/12 pruebas auxiliares.
- QA-07: 14/14 pruebas auxiliares.
- TypeScript: PASS.
- Build estandar: PASS.
- Build QA: PASS.
- Build Angular Android: PASS.

Los resultados frontend se documentan en su propio
repositorio y se vincularan mediante el PR correspondiente.

## 11. Limitaciones y riesgos residuales

- Las pruebas PHPUnit no sustituyen Karate.
- Las pruebas auxiliares frontend no sustituyen Selenium.
- La compilacion Angular Android no constituye un APK.
- No se han ejecutado pruebas visuales ni E2E de navegador
  para QA-07 en este bloque.
- No se han medido usuarios concurrentes mediante JMeter.
- La privacidad de pagos se verifico en respuestas y BD,
  no mediante una auditoria integral de infraestructura.
- El pago fallido se representa mediante fixtures;
  no se ha agregado una pasarela ni un simulador de rechazo.
- La vinculacion formal con Jira permanece pendiente de QA-29.

## 12. Conclusion

Las suites locales seleccionadas finalizaron sin fallos.

Se corrigieron los hallazgos historicos y el defecto
funcional de validacion Luhn.

La integracion en develop y la vinculacion de commits
y PR se verificaran antes del cierre del issue.

La acreditacion de la rubrica se completara mediante
las herramientas obligatorias en sus tareas respectivas.
