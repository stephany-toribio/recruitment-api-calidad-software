# QA-13 — Entorno local reproducible

## Alcance

Infraestructura local de API, MySQL y frontend.
Sin despliegue público ni HTTPS público.
Conectividad Android no evaluada en esta ejecución.

Dependencias: QA-02 y QA-03.
Responsable por rol: R3. Revisión cruzada: R1.

## Versiones verificadas

Docker 29.7.2, Compose 5.4.0, PHP 8.3.37,
Laravel 12.69.2, Node 22.23.5 y npm 10.9.9.

## Direcciones y aislamiento

| Componente | Dirección |
| --- | --- |
| API | http://127.0.0.1:8002/api/v1 |
| Health check | http://127.0.0.1:8002/up |
| Frontend QA | http://127.0.0.1:4201 |
| MySQL desde laptop | 127.0.0.1:3307 |
| MySQL desde app | mysql:3306 |

Cada integrante usa su propia instancia y credenciales.
El proyecto Compose es calidad_software.
No iniciar dos copias con ese mismo nombre en una máquina.

Bases utilizadas:
- portal_empleos_calidad_qa: aplicación y pruebas externas.
- portalempleoshu04testing: integración PHPUnit MySQL.
- SQLite :memory:: Unit y Feature.

## Variables requeridas

Crear .env desde .env.example solo cuando no exista.
Configurar los secretos localmente; nunca incluirlos en Git.

| Variable | Configuración |
| --- | --- |
| APP_ENV | local |
| APP_KEY | Generada para esta instancia |
| DB_CONNECTION | mysql |
| DB_HOST | mysql |
| DB_PORT | 3306 |
| DB_DATABASE | portal_empleos_calidad_qa |
| DB_USERNAME | Usuario local independiente |
| DB_PASSWORD | Secreto local |
| MYSQL_ROOT_PASSWORD | Secreto local |
| QA_FIXTURE_PASSWORD | Contraseña de pruebas de al menos 12 caracteres |
| APP_PORT | 8002 por defecto |
| MYSQL_PORT | 3307 por defecto |
| CORS_ALLOWED_ORIGINS | Lista opcional de orígenes separados por comas |

Sin CORS_ALLOWED_ORIGINS, el entorno local utiliza la lista
explícita de config/cors.php. Un valor configurado la reemplaza.
Ejemplo sin secretos: http://127.0.0.1:4201,http://localhost:4201

No publicar dumps, tokens ni archivos .env.

## Inicialización del backend

Crear .env desde .env.example si no existe y configurar los
secretos localmente. Ejecutar los comandos desde el backend,
continuando únicamente si cada uno termina correctamente.

```powershell
docker compose config --quiet
docker compose build app
docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
```

Generar APP_KEY únicamente si está vacía:

```powershell
docker compose run --rm --no-deps app php artisan key:generate
```

Iniciar los servicios:

```powershell
docker compose up -d
docker compose ps
docker compose exec app php artisan config:clear
```

Confirmar mediante Laravel que la base efectiva sea
portal_empleos_calidad_qa antes de ejecutar migraciones.

Después de confirmar el destino:

```powershell
docker compose exec app php artisan migrate --no-interaction
docker compose exec app php artisan db:seed --class=QaEntornoSeeder --no-interaction
docker compose exec app php scripts/qa13/verificar-datos.php
```

La carga restablece las seis cuentas QA y revoca sus tokens.
No elimina otros datos ni equivale a restaurar toda la base.
No ejecutar durante pruebas en curso.

## Inicialización del frontend

En otra terminal, desde la raíz del frontend:

```powershell
npm.cmd ci
npm.cmd run start:qa
```

Abrir http://127.0.0.1:4201.
El proxy dirige /api/v1 a http://127.0.0.1:8002.
El artefacto compilado requiere el servidor descrito en QA-03.

## Conexiones para las herramientas

| Herramienta | Configuración de referencia |
| --- | --- |
| Karate | Origen http://127.0.0.1:8002 y prefijo /api/v1 |
| JMeter | Protocolo http, host 127.0.0.1 y puerto 8002 |
| Selenium | URL inicial http://127.0.0.1:4201 |
| Appium | Servidor Appium y URL API de la aplicación se configuran por separado |

Usar los parámetros reales de cada suite.
No se ejecutaron estas herramientas dentro de QA-13.
Las pruebas de carga deben realizarse aisladamente.


## Respaldo y recuperación

Pausar las pruebas y escrituras antes de respaldar o comparar datos.
Los respaldos contienen datos y tokens: conservarlos fuera de Git.

### Crear respaldo

Desde el backend, ejecutar cada comando y continuar solo si
termina correctamente:

```powershell
$qaCarpeta = Join-Path $env:LOCALAPPDATA "ProyectoCalidad\backups"
New-Item -ItemType Directory -Force $qaCarpeta | Out-Null
$qaArchivo = Join-Path $qaCarpeta ("qa13-" + [guid]::NewGuid().ToString("N") + ".sql")

docker compose cp scripts/qa13/mantenimiento.sh mysql:/tmp/qa13-mantenimiento.sh
docker compose exec -T mysql sh /tmp/qa13-mantenimiento.sh backup
docker compose cp mysql:/tmp/qa13-respaldo.sql $qaArchivo

Get-Item $qaArchivo | Select-Object FullName, Length
Get-FileHash -Algorithm SHA256 $qaArchivo
```

Conservar la ruta y el hash. Después de verificar la copia local:

```powershell
docker compose exec -T mysql rm -f /tmp/qa13-respaldo.sql /tmp/qa13-mantenimiento.sh
```

No ejecutar dos respaldos simultáneos con este procedimiento.

### Comprobar restauración aislada

Definir $qaArchivo con la ruta del respaldo y comprobar su hash.
Crear un contenedor nuevo, sin red ni puertos publicados:

```powershell
$qaTemporal = "qa13-recovery-" + [guid]::NewGuid().ToString("N")

docker run --detach --name $qaTemporal --network none `
    --tmpfs "/var/lib/mysql:rw,size=1g" `
    --env MYSQL_ALLOW_EMPTY_PASSWORD=yes `
    --env MYSQL_DATABASE=qa13_restore mysql:8.0 --skip-log-bin

docker cp scripts/qa13/mantenimiento.sh "${qaTemporal}:/tmp/qa13-mantenimiento.sh"
docker exec $qaTemporal sh /tmp/qa13-mantenimiento.sh ready
```

Si ready devuelve un código distinto de cero, esperar unos
segundos y repetirlo. No continuar hasta que devuelva cero.

```powershell
docker cp "$qaArchivo" "${qaTemporal}:/tmp/qa13-respaldo.sql"
docker exec $qaTemporal sh /tmp/qa13-mantenimiento.sh restore
```

Continuar únicamente si la restauración terminó correctamente:

```powershell
docker exec $qaTemporal sh /tmp/qa13-mantenimiento.sh counts
```

Comparar con los conteos registrados al crear el respaldo.
Finalmente, eliminar exclusivamente el contenedor temporal:

```powershell
docker rm --force --volumes $qaTemporal
```

La contraseña vacía solo se utiliza en esa instancia desechable
sin red ni puertos. La base funcional no se sobrescribe.

La restauración manual equivalente fue comprobada con:
31 tablas, 61 migraciones, 3 roles, 6 usuarios,
3 candidatos y 2 empresas.

La coincidencia de conteos no demuestra igualdad de todos los valores.
El script versionado debe verificarse antes de declararlo ejecutado.
No se ha probado un reemplazo destructivo de la base activa.

## Evidencias y pendientes

Consultar ../evidencias/QA-13/RESULTADOS.md.

Pendientes: reproducción independiente, revisión cruzada,
confirmación del alcance actualizado del issue e integración del PR.

No se acredita conectividad Android ni ejecución desde un APK.