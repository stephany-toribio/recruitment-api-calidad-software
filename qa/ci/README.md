# Integracion continua — QA-27

## Objetivo

Automatizar la validacion del backend CREO+ mediante
GitHub Actions, PHPUnit, MySQL y Karate Framework.

## Activacion

- Pull requests hacia develop y main.
- Pushes a develop y main.
- Ejecucion manual mediante workflow_dispatch.

## Entorno

- GitHub Actions Ubuntu 24.04.
- PHP 8.3 mediante Docker.
- MySQL 8.0.
- Java 21.
- Maven.
- Laravel 12.

## Bases de datos

La API Karate utiliza portal_empleos_calidad_qa.

PHPUnit Unit y Feature utilizan SQLite en memoria.

PHPUnit MySql utiliza portalempleoshu04testing.

El pipeline utiliza contenedores y volumenes efimeros.
No se conecta a la base publicada ni a la base local
del equipo.

## Suites Karate

| Suite | FULL | DEMO |
|---|---:|---:|
| QA-14 | 4 | 4 |
| QA-15 | 39 | 8 |
| QA-16 | 34 | 4 |
| QA-17 | 32 | 5 |
| Total | 109 | 21 |

DEMO reutiliza los escenarios existentes.
No sustituye la regresion completa.

## Ejecucion local equivalente

Con el entorno QA previamente preparado:

    qa/ci/run-phpunit.sh

    qa/ci/run-karate.sh full

    qa/ci/run-karate.sh demo

El script bootstrap.sh es exclusivo de GitHub Actions.

## Evidencias

El pipeline publica:

- Identificador del commit ejecutado.
- XML JUnit sanitizados de PHPUnit.
- XML JUnit sanitizados de Karate.
- Resultados JSON por suite Karate.
- Resumen consolidado Karate.

Los logs privados y archivos de configuracion no se
publican como artefactos.

## Seguridad

Las credenciales de CI se generan por ejecucion.
Los reportes se reconstruyen con campos permitidos.
Los mensajes de excepcion y propiedades de entorno
se excluyen de los XML publicados.

El escaneo cubre formatos conocidos de credenciales.
No constituye una auditoria integral de secretos.

## Criterio de aprobacion

El pipeline debe fallar cuando exista un error de
preparacion, compilacion, ejecucion, conteo de casos,
sanitizacion o limpieza.

La aprobacion de QA-27 requiere una ejecucion real
satisfactoria en GitHub Actions.

## Estado

Implementacion integrada mediante PR #45.

Ejecucion remota FULL verificada:

- PHPUnit: 708/708.
- Karate: 109/109.
- Run ID: 45710340550.
- Resultado: PASS.

Consultar docs/evidencias/QA-27/RESULTADOS.md
para resultados, incidencias y artefactos.
