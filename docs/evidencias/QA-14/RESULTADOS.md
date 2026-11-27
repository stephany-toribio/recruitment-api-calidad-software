# Resultados QA-14 - Infraestructura Karate

## 1. Identificacion

- Tarea: QA-14.
- Issue: #18.
- Epica: E04.
- Hito: M3.
- Responsable: stephany-toribio.
- Fecha: 2026-11-25.
- Rama: feature/QA-14-karate-runner.
- Ambiente: WSL2 y Docker local.
- Framework: Karate 1.5.2.
- Java: 21.
- Maven: 3.9.12.

## 2. Objetivo

Implementar infraestructura reproducible para
las pruebas automatizadas de API mediante Karate.

## 3. Implementacion

- Proyecto Java/Maven versionado.
- Runner JUnit.
- Configuracion por ambiente.
- Contratos JSON reutilizables.
- Variables dinamicas.
- Helpers mediante call.
- Fixtures independientes.
- Preparacion y limpieza automatizadas.
- Generacion de reportes.
- Proteccion de logs y control de secretos.

## 4. Escenarios ejecutados

| ID | Escenario | Resultado |
| --- | --- | --- |
| QA14-S01 | Disponibilidad y helper reutilizable | PASS |
| QA14-S02 | Recurso protegido sin token, 401 | PASS |
| QA14-S03 | Login sin campos obligatorios, 422 | PASS |
| QA14-S04 | Login, consulta, logout y revocacion | PASS |

Total: cuatro escenarios Karate aprobados.

## 5. Controles Java

Seis pruebas unitarias del modificador HTTP:

- Authorization.
- Cookies.
- Cabeceras no sensibles.
- Password del request.
- Token del response.
- Parametros de consulta.

Resultado: 6/6 PASS.

El runner JUnit no se suma nuevamente como
escenario funcional Karate.

## 6. Verificacion del runner

| Ejecucion | Aprobados | Fallidos | Maven |
| --- | ---: | ---: | --- |
| Inicial | 3 | 0 | SUCCESS |
| Fallo controlado | 3 | 1 | FAILURE |
| Recuperacion | 3 | 0 | SUCCESS |
| Ciclo autenticado final | 4 | 0 | SUCCESS |

El fallo controlado devuelve exit code 1.

## 7. Incidencia QA14-SEC-01

Se detecto un token dentro de callArg.token
en un reporte JSON interno de Karate.

Se elimino el argumento explicito sensible,
se configuro showCallArg=false y se mantuvo
el modificador de logs HTTP.

Retest: escaneo de secretos PASS.

Los reportes del intento con exposicion
no fueron incorporados a las evidencias.

## 8. Fixtures

- Usuario sintetico identificado por UUID.
- Password temporal aleatoria.
- Token Sanctum obtenido mediante API.
- Logout y comprobacion posterior HTTP 401.
- Limpieza del usuario temporal y tokens.

Verificacion independiente:

- Base: portal_empleos_calidad_qa.
- Usuarios temporales restantes: 0.
- Cuentas compartidas conservadas: 6.

## 9. Controles de rubrica

| Control | Evidencia QA-14 |
| --- | --- |
| RUB-K01 | Contratos JSON completos de smoke |
| RUB-K02 | HTTP 200, 401 y 422 |
| RUB-K03 | Aserciones de tiempo de respuesta |
| RUB-K04 | Ambiente, UUID, token e ID dinamicos |
| RUB-K05 | Helpers health, login y logout mediante call |

La cobertura exhaustiva corresponde a
QA-15, QA-16 y QA-17.

## 10. Evidencias

- dependencias.txt
- smoke/
- runner/
- seguridad/
- autenticacion/
- SHA256SUMS.txt

## 11. Limitaciones

Las pruebas se ejecutaron contra la API local.

El ambiente HTTPS remoto depende de QA-13.

Los fixtures actuales cubren autenticacion;
los datos de negocio se ampliaran en QA-16.

El escaneo de secretos es preventivo y no
garantiza detectar todos los formatos posibles.

El umbral inicial de tiempo de respuesta
no constituye una medicion de carga ni un SLA.

## 12. Conclusion

La infraestructura Karate fue implementada
y verificada satisfactoriamente.

La ejecucion es reproducible, detecta fallos
y realiza preparacion y limpieza de fixtures.

No se modificaron reglas funcionales de Laravel.
