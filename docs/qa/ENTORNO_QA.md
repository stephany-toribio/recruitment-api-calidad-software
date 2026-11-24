
# Entorno local de Calidad de Software

## 1. Objetivo

Establecer un entorno reproducible y aislado para el desarrollo,
la ejecución de pruebas automatizadas y la validación funcional
del Portal de Empleos.

Issue asociado: #12 — QA-02.

## 2. Requisitos

- Git.
- Docker y Docker Compose.
- WSL2 recomendado para Windows.
- Acceso al repositorio.

Versiones utilizadas durante la validación inicial:

| Herramienta | Versión |
|---|---|
| Git | 2.53.0 |
| Docker | 29.4.3 |
| Docker Compose | 5.1.3 |
| PHP | 8.3.37 |
| Laravel | 12.69.2 |
| Composer | 2.11.2 |
| MySQL | 8.0.46 |
| PHPUnit | 11.5.56 |

## 3. Arquitectura del entorno

Proyecto Docker Compose: calidad_software.

| Componente | Puerto externo | Puerto interno |
|---|---|---|
| API Laravel | 8002 | 8000 |
| MySQL | 3307 | 3306 |

Los contenedores utilizan una red y un volumen independientes.

## 4. Instalación desde una copia limpia

Clonar el repositorio y posicionarse en la rama correspondiente.

```bash
git clone https://github.com/stephany-toribio/recruitment-api-calidad-software.git
cd recruitment-api-calidad-software
git switch develop
```

Estas instrucciones aplican cuando QA-02 esté integrado en develop.

Crear el archivo local de configuración sin sobrescribir uno existente:

```bash
test -f .env || cp .env.example .env
```

Configurar QA_FIXTURE_PASSWORD en .env con una contraseña
exclusivamente de pruebas de al menos 12 caracteres.

Construir la imagen e instalar las dependencias:

```bash
docker compose config --quiet
docker compose build app
docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
```

Generar APP_KEY si todavía no existe:

```bash
docker compose run --rm --no-deps app php artisan key:generate
```

Iniciar los servicios:

```bash
docker compose up -d
docker compose ps
```

Verificar el endpoint de salud:

```bash
curl -i http://localhost:8002/up
```

## 5. Bases de datos

| Base de datos | Finalidad |
|---|---|
| portal_empleos_calidad_qa | Aplicación y pruebas funcionales externas |
| portalempleoshu04testing | Pruebas de integración MySQL |
| SQLite :memory: | Pruebas Unit y Feature |

La base del Portal de Empleos original queda fuera del entorno.

## 6. Migraciones y catálogos

Confirmar la base de aplicación antes de ejecutar migraciones:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan tinker --execute='dump(DB::selectOne("SELECT DATABASE() AS nombre"));'
```

El resultado debe ser portal_empleos_calidad_qa.

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

## 7. Datos ficticios

```bash
docker compose exec app php artisan db:seed --class=QaSeeder
```

| Correo | Rol | Estado |
|---|---|---|
| admin@qa.example.test | admin | activo |
| empresa1@qa.example.test | empresa | activo |
| empresa2@qa.example.test | empresa | activo |
| candidato1@qa.example.test | candidato | activo |
| candidato2@qa.example.test | candidato | activo |
| suspendido@qa.example.test | candidato | suspendido |

Las credenciales se configuran localmente mediante QA_FIXTURE_PASSWORD.

No se deben asumir identificadores numéricos fijos.

La ejecución repetida de QaSeeder restablece los atributos
de las cuentas y revoca sus tokens existentes.

## 8. Limpieza controlada

```bash
docker compose exec app php artisan db:seed --class=QaCleanupSeeder
```

La limpieza elimina exclusivamente las cuentas ficticias
autorizadas, sus perfiles básicos, tokens y solicitudes
de recuperación de contraseña.

Si existen dependencias funcionales, la operación se cancela.

No ejecutar durante pruebas concurrentes ni mientras otros
usuarios estén utilizando los fixtures.

Para restaurar las cuentas:

```bash
docker compose exec app php artisan db:seed --class=QaSeeder
```

## 9. Pruebas Unit y Feature

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan test --testsuite=Unit
docker compose exec app php artisan test --testsuite=Feature
```

Estas suites utilizan SQLite en memoria.

## 10. Pruebas MySQL

El script de inicialización crea la base exclusiva durante
la primera inicialización del volumen.

Si el volumen ya existe, ejecutar:

```bash
docker compose exec mysql sh /docker-entrypoint-initdb.d/01-create-testing-database.sh
```

Verificar la conexión:

```bash
docker compose exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=mysql \
  -e DB_PORT=3306 \
  -e DB_DATABASE=portalempleoshu04testing \
  -e DB_URL= \
  app php artisan tinker --execute='dump(DB::selectOne("SELECT DATABASE() AS nombre"));'
```

La base devuelta debe ser portalempleoshu04testing.

Ejecutar las migraciones:

```bash
docker compose exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=mysql \
  -e DB_PORT=3306 \
  -e DB_DATABASE=portalempleoshu04testing \
  -e DB_URL= \
  app php artisan migrate
```

Ejecutar PHPUnit:

```bash
docker compose exec app vendor/bin/phpunit \
  --configuration phpunit.mysql.xml \
  --testsuite=MySql
```

## 11. Seguridad y restricciones

- No publicar archivos .env.
- No reutilizar credenciales de producción.
- No eliminar las protecciones existentes en los tests.
- No utilizar migrate:fresh o db:wipe sobre bases ajenas.
- No ejecutar fixtures contra bases no autorizadas.
- Los servicios están expuestos inicialmente en localhost.
- Las credenciales de ejemplo son exclusivamente locales.
- No deshabilitar restricciones de integridad referencial.

## 12. Evidencias

Consultar:

[Resultados de QA-02](../evidencias/QA-02/RESULTADOS.md)
