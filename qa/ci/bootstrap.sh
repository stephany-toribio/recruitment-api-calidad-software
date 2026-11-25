#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

cd "$ROOT"

if [[ "${GITHUB_ACTIONS:-false}" != "true" ]]; then
    echo "ERROR: Bootstrap exclusivo de GitHub Actions"
    exit 1
fi

if [[ -e .env ]]; then
    echo "ERROR: Ya existe un archivo .env"
    exit 1
fi

umask 077

python3 <<'PY'
from pathlib import Path
import secrets
import re

s = Path(".env.example").read_text()

variables = {
    "APP_ENV": "local",
    "APP_DEBUG": "false",
    "APP_URL": "http://127.0.0.1:8002",
    "APP_PORT": "8002",
    "DB_CONNECTION": "mysql",
    "DB_HOST": "mysql",
    "DB_PORT": "3306",
    "DB_DATABASE": "portal_empleos_calidad_qa",
    "DB_USERNAME": "calidad_qa",
    "DB_PASSWORD": secrets.token_hex(24),
    "MYSQL_ROOT_PASSWORD": secrets.token_hex(24),
    "QA_FIXTURE_PASSWORD": "Qa1#" + secrets.token_hex(16),
    "MYSQL_PORT": "3307",
    "BCRYPT_ROUNDS": "4",
    "MAIL_MAILER": "log",
    "LOG_LEVEL": "warning"
}

for clave, valor in variables.items():

    patron = rf"(?m)^{re.escape(clave)}=.*$"

    if re.search(patron, s):
        s = re.sub(
            patron,
            lambda _: f"{clave}={valor}",
            s
        )
    else:
        s += f"\n{clave}={valor}\n"

Path(".env").write_text(s)

print("PASS: Configuracion efimera generada")
PY

echo "===== CONSTRUIR ENTORNO ====="

docker compose build app

docker compose up -d --wait mysql

echo "===== INSTALAR DEPENDENCIAS ====="

docker compose run --rm --no-deps app \
    composer install \
    --no-interaction \
    --prefer-dist \
    --no-progress

docker compose run --rm --no-deps app \
    php artisan key:generate --force

echo "===== PREPARAR BASE QA ====="

docker compose run --rm --no-deps app \
    php artisan config:clear

docker compose run --rm --no-deps app \
    php artisan migrate --force

docker compose run --rm --no-deps app \
    php artisan db:seed --force

docker compose run --rm --no-deps app \
    php artisan db:seed --class=QaSeeder --force

echo "===== PREPARAR BASE MYSQL DE PRUEBAS ====="

docker compose run --rm --no-deps \
    -e APP_ENV=testing \
    -e DB_CONNECTION=mysql \
    -e DB_HOST=mysql \
    -e DB_PORT=3306 \
    -e DB_DATABASE=portalempleoshu04testing \
    -e DB_URL= \
    app php artisan migrate --force

docker compose up -d app

curl --retry 30 \
    --retry-all-errors \
    --retry-delay 2 \
    -fsS http://127.0.0.1:8002/up \
    -o /dev/null

echo "PASS: API QA disponible"

docker compose exec -T app php artisan --version

docker compose ps
