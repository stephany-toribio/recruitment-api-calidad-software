# Resultados de integracion continua — QA-27

## Identificacion

- Fecha: 25 de noviembre de 2026.
- Herramientas: GitHub Actions, PHPUnit y Karate Framework.
- PR: #45.
- Issue: #25.
- Run ID: 45710340550.
- Job ID: 139542806610.
- Artifact ID: 15066624816.
- Duracion reportada: 9 minutos y 59 segundos.
- Resultado: PASS.

## Trazabilidad de versiones

- Commit de implementacion: 712bb8425cd50d09ac9441bd1b12aaccba458b68.
- Commit ejecutado en CI: daa07becde7f483e25b9b1dbad3236d45e5b5be3.
- Merge del PR #45 en develop: b6d13b5.

## Auditoria de artefactos

- Archivos descargados y auditados: 14.
- Validacion JSON: PASS.
- Validacion XML: PASS.
- Escaneo de formatos conocidos de credenciales: PASS.
- Integridad SHA256: PASS.

## PHPUnit

| Suite | Ejecutadas | Aprobadas | Fallidas |
|---|---:|---:|---:|
| Unit | 14 | 14 | 0 |
| Feature | 245 | 245 | 0 |
| MySql | 449 | 449 | 0 |
| Total | 708 | 708 | 0 |

## Karate Framework

| Suite | Ejecutadas | Aprobadas | Fallidas |
|---|---:|---:|---:|
| QA-14 | 4 | 4 | 0 |
| QA-15 | 39 | 39 | 0 |
| QA-16 | 34 | 34 | 0 |
| QA-17 | 32 | 32 | 0 |
| Total | 109 | 109 | 0 |

Adicionalmente aprobaron seis pruebas Java del
componente de sanitizacion de logs.

## Verificaciones

- Preparacion automatizada Docker: PASS.
- Migraciones MySQL: PASS.
- Catalogos y fixtures: PASS.
- Validacion de credenciales: PASS.
- PHPUnit: PASS.
- Karate FULL: PASS.
- Limpieza de fixtures: PASS.
- Escaneo preventivo: PASS.
- Publicacion de artefactos: PASS.
- Eliminacion del entorno efimero: PASS.

## Evidencia remota

https://github.com/stephany-toribio/recruitment-api-calidad-software/actions/runs/45710340550

## Evidencia local

Consultar reportes/ para los XML JUnit sanitizados,
los resultados JSON y el identificador del commit
ejecutado por GitHub Actions.

## Delimitaciones

La ejecucion FULL fue verificada en GitHub Actions.

El modo DEMO consolidado queda pendiente de ensayo
como parte de la preparacion de la exposicion.

Las advertencias de obsolescencia de las acciones
se conservan como observacion tecnica.

La sanitizacion no constituye una auditoria
integral de secretos.
