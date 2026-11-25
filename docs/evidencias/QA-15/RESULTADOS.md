# QA-15 - Automatizacion de registros y autenticacion

## 1. Identificacion

- Proyecto: Portal de Empleos CREO+.
- Issue: #19.
- Herramienta: Karate 1.5.2.
- Rama: feature/QA-15-karate-autenticacion.
- Ambiente: QA local, WSL2, Docker, Laravel y MySQL.
- Base de datos: portal_empleos_calidad_qa.

## 2. Objetivo

Automatizar los casos funcionales de registro, autenticacion,
bloqueo, suspension y revocacion de sesiones, mediante
solicitudes HTTP reales y comprobaciones de persistencia.

## 3. Cobertura funcional

| Caso | Descripcion | Escenarios | Resultado |
| --- | --- | ---: | --- |
| CP-01-01 | Registro valido candidato | 1 | PASS |
| CP-01-02 | Duplicados candidato | 3 | PASS |
| CP-01-03 | Datos invalidos candidato | 13 | PASS |
| CP-02-01 | Registro valido empresa | 1 | PASS |
| CP-02-02 | Duplicados empresa | 2 | PASS |
| CP-02-03 | Datos invalidos empresa | 9 | PASS |
| CP-03-01 | Login candidato | 1 | PASS |
| CP-03-02 | Login empresa | 1 | PASS |
| CP-03-03 | Login administrador | 1 | PASS |
| CP-04-01 | Password incorrecta | 1 | PASS |
| CP-04-02 | Bloqueo y recuperacion | 1 | PASS |
| CP-04-03 | Cuenta suspendida | 1 | PASS |
| CP-05-01 | Logout | 1 | PASS |
| CP-05-02 | Token revocado | 1 | PASS |
| CP-05-03 | Token malformado | 1 | PASS |
| QA15-EXT-01 | Independencia entre sesiones | 1 | PASS |
| Total | 15 casos canonicos y un complemento | 39 | PASS |

## 4. Resultados de ejecucion

| Ejecucion | Aprobados | Fallidos | Tiempo runner |
| --- | ---: | ---: | ---: |
| Registro inicial | 0 | 2 | No consolidado |
| Retest registros validos | 2 | 0 | 6.953 s |
| Registro ampliado | 24 | 5 | 48.03 s |
| Retest registro ampliado | 29 | 0 | 47.54 s |
| Primera suite completa | 39 | 0 | 75.19 s |
| Full 01 | 39 | 0 | 77.71 s |
| Full 02 | 39 | 0 | 74.39 s |
| Demo 01 | 8 | 0 | 23.42 s |

Los tiempos corresponden al runner JUnit cuando
se dispone de dicha medicion.

Las ejecuciones repetidas no representan nuevos
casos funcionales.

## 5. Persistencia y aislamiento

- UUID independiente por ejecucion.
- Correos sinteticos dinamicos.
- DNI, celular y RUC generados dinamicamente.
- Catalogos consultados desde la base QA.
- Verificacion de contrasenas almacenadas mediante hash.
- Verificacion de relaciones de perfiles.
- Verificacion del contador de intentos fallidos.
- Verificacion de creacion y revocacion de tokens.
- Comprobacion de ausencia de altas parciales dentro
  del conjunto de fixtures de la ejecucion.
- Limpieza automatizada mediante UUID.
- Cero usuarios temporales Karate tras las ejecuciones.
- Seis cuentas QA compartidas presentes al finalizar.

## 6. Incidencias

Se identificaron y resolvieron dos defectos de
la automatizacion:

- QA15-T01: password temporal incompatible.
- QA15-T02: interpretacion literal del campo esperado.

Consultar INCIDENCIAS.md.

No fue necesario modificar las reglas funcionales
de Laravel para resolver estas incidencias.

## 7. Controles de rubrica

| Control | Implementacion |
| --- | --- |
| RUB-K01 | Esquemas JSON completos y validaciones por campo |
| RUB-K02 | HTTP 200, 201, 401, 403, 422 y 429 |
| RUB-K03 | Aserciones de tiempo de respuesta |
| RUB-K04 | UUID, identificadores, payloads y tokens dinamicos |
| RUB-K05 | Reutilizacion de helpers mediante call |

Los controles se encuentran demostrados para el
alcance funcional de QA-15.

La cobertura de vacantes, postulaciones, decisiones
y permisos sera completada en QA-16 y QA-17.

## 8. Demostracion en vivo

Suite completa:

    qa/karate/scripts/run-qa15.sh full

Suite reducida:

    qa/karate/scripts/run-qa15.sh demo

El modo demo selecciona ocho escenarios de la misma
suite completa mediante etiquetas @demo.

El runner exige exactamente 39 escenarios en modo
full y ocho en modo demo.

## 9. Evidencias

- registro/
- autenticacion/
- repetibilidad/
- reportes/full-02/
- reportes/demo-01/
- regresion-qa14/
- INCIDENCIAS.md
- SHA256SUMS.txt

## 10. Limitaciones

- Validacion realizada en ambiente QA local.
- No se ha validado conectividad HTTPS remota.
- El vencimiento del bloqueo se simula modificando
  exclusivamente el fixture sintetico, despues de
  verificar la duracion contractual de quince minutos.
- La comprobacion de tiempos de respuesta no
  sustituye las pruebas de carga con JMeter.
- El escaneo de secretos es preventivo y no garantiza
  detectar todos los formatos posibles.
- El conteo por UUID no constituye una auditoria
  global de todas las tablas.
- Se requiere entorno Docker y Maven disponible.

## 11. Conclusion

Los 39 escenarios funcionales implementados para
QA-15 aprobaron, incluyendo dos ejecuciones completas
consecutivas y una ejecucion reducida de ocho casos.

Se verifico la limpieza de fixtures y la
repetibilidad de la automatizacion.
