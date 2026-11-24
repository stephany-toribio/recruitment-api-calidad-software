# QA-02 — Entorno aislado para API

Este archivo documenta **la preparación**, no constituye evidencia de una ejecución exitosa. El entorno conserva intacto `docker-compose.yml` del portal original.

## Requisitos

Docker Engine con Compose v2, Git y PHP/Composer ejecutables mediante la imagen Docker. Usar un repositorio clonado exclusivamente para Calidad de Software. Los valores de contraseña por defecto son **solo para desarrollo local**; no desplegar en Internet con ellos.

## Inicio reproducible (desde la raíz del repositorio)

```bash
git rev-parse HEAD
docker --version
docker compose version
cp .env.example .env               # solo si todavía no existe
# Editar .env y establecer APP_KEY; nunca subirlo a GitHub.
docker compose -p calidad_qa -f compose.qa.yml up -d --build
docker compose -p calidad_qa -f compose.qa.yml exec app composer install --no-interaction
docker compose -p calidad_qa -f compose.qa.yml exec app php artisan key:generate
docker compose -p calidad_qa -f compose.qa.yml exec app php artisan config:clear
docker compose -p calidad_qa -f compose.qa.yml exec app php artisan migrate --force
curl -i http://localhost:8002/up
```

Configuración local de esta copia: API `localhost:8002`, MySQL `127.0.0.1:3307`, esquema `portal_empleos_calidad_qa`; red y volumen del proyecto Compose `calidad_qa`. Para evitar colisiones con otros proyectos, cambiar `QA_APP_PORT`/`QA_MYSQL_PORT` antes del arranque. No mezclar `-f docker-compose.yml` con `-f compose.qa.yml`: este último es un archivo Compose completo e independiente.

## Pruebas

```bash
# Suite existente con SQLite en memoria, definida en phpunit.xml
docker compose -p calidad_qa -f compose.qa.yml exec app php artisan test
# Validar migraciones de la base MySQL exclusiva de QA
docker compose -p calidad_qa -f compose.qa.yml exec app php artisan migrate:status
# Comprobar sintaxis Compose antes de levantar:
docker compose -p calidad_qa -f compose.qa.yml config --quiet
```

El `phpunit.xml` actual define SQLite en memoria. No sustituir esa configuración por MySQL de producción. La matriz de pruebas sobre MySQL aislado, datos de administrador/dos empresas/candidatos y sus operaciones de limpieza requieren implementación y ejecución verificadas antes de cerrar QA-02.

## Seguridad y limpieza

- No ejecutar `migrate:fresh`, `db:wipe` o `down -v` contra la configuración original ni contra una base ajena.
- El único esquema de este Compose es `portal_empleos_calidad_qa`. El contenedor MySQL no se publica fuera de `127.0.0.1`.
- `docker compose -p calidad_qa -f compose.qa.yml down` detiene el entorno sin eliminar el volumen.
- Eliminar volúmenes **solo después de verificar explícitamente el proyecto y realizar respaldo si procede**. No automatizar su eliminación.
- Guardar como evidencia: hash del commit, versiones, salida de `config`, `up`, `migrate:status`, `artisan test`, códigos HTTP y defectos detectados; retirar secretos.

## Estado de aceptación

- [x] Configuración Compose separada y nombres/puertos/esquema independientes, **pendiente de ejecución**.
- [ ] Instalación y migraciones ejecutadas satisfactoriamente.
- [ ] Datos ficticios repetibles y limpieza segura implementados.
- [ ] Pruebas Unit/Feature (SQLite) y pruebas MySQL realmente ejecutadas.
- [ ] Evidencia y revisión cruzada adjuntas en issue/PR.
