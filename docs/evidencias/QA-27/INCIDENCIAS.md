# Registro de incidencias — QA-27

## INC-QA27-01 — Credencial de fixtures CI

- Fecha: 25 de noviembre de 2026.
- PR: #45.
- Commit: 66c2933.
- Run ID: 45709600851.
- Job ID: 139540277359.
- Duracion: 2 minutos y 12 segundos.
- Resultado: FAIL.
- Etapa: Preparar API y MySQL.
- Componente: QaSeeder.

### Error observado

RuntimeException:
Define QA_FIXTURE_PASSWORD con al menos 12 caracteres.

### Diagnostico

La credencial generada contiene un numeral sin comillas
en el archivo .env, lo que puede provocar su interpretacion
incorrecta por el lector de variables de entorno.

### Correccion propuesta

- Generar una credencial aleatoria sin numeral.
- Verificar su longitud efectiva desde Laravel.
- No imprimir credenciales.
- Conservar las restricciones de QaSeeder.

### Resultados de la ejecucion inicial

- Construccion Docker: completada.
- Migraciones de la base QA: completadas.
- Catalogos principales: completados.
- QaSeeder: FAIL.
- PHPUnit: no ejecutado.
- Karate: no ejecutado.
- Publicacion de artefactos: PASS.
- Limpieza del entorno: PASS.

Retest: pendiente.

## OBS-QA27-01 — Dependencias de GitHub Actions

GitHub informa advertencias de obsolescencia relacionadas
con Node.js 20 y setup-java v4.

Estas advertencias no fueron la causa del fallo observado.

Pendiente: actualizar las acciones a versiones compatibles
y verificar el pipeline.

### Retest INC-QA27-01

- Run ID: 45710340550.
- Resultado: PASS.
- Validacion preventiva de credencial: PASS.
- Preparacion completa del entorno: PASS.
- PHPUnit: 708/708.
- Karate FULL: 109/109.

Estado de la incidencia: corregida y verificada.
