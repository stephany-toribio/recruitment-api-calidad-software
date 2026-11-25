#!/usr/bin/env bash

set -euo pipefail

umask 077

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

cd "$ROOT"

SALIDA="${QA_CI_REPORT_DIR:-$ROOT/qa/karate/target-evidencias/CI}"

mkdir -p "$SALIDA/phpunit"

PRIVADO="$(mktemp -d)"

ESTADO=0

echo "===== VALIDACION PHP ====="

docker compose exec -T app sh -c '
    find app routes config database tests qa/karate/scripts \
        -type f -name "*.php" -print0 |
        xargs -0 -n1 php -l
' > "$PRIVADO/lint.txt" 2>&1

echo "PASS: Validacion sintactica PHP"

docker compose exec -T app php artisan config:clear

ejecutar() {

    local suite="$1"
    local configuracion="$2"
    local codigo=0

    echo "===== PHPUnit $suite ====="

    docker compose exec -T app         rm -f "/tmp/qa27-$suite.xml" || return 1


    docker compose exec -T app \
        vendor/bin/phpunit \
        --configuration "$configuracion" \
        --testsuite="$suite" \
        --log-junit "/tmp/qa27-$suite.xml" \
        > "$PRIVADO/$suite.log" 2>&1 || codigo=$?

    if docker compose exec -T app \
        cat "/tmp/qa27-$suite.xml" \
        > "$PRIVADO/$suite.xml"
    then

        python3 qa/ci/sanitize-junit.py \
            "$PRIVADO/$suite.xml" \
            "$SALIDA/phpunit/$suite.xml" || codigo=1

    else

        codigo=1

    fi

    if [[ "$codigo" -eq 0 ]]; then

        python3 - "$SALIDA/phpunit/$suite.xml" <<'PYTHON' || codigo=1
import sys
import xml.etree.ElementTree as ET

raiz = ET.parse(sys.argv[1]).getroot()

casos = list(raiz.iter("testcase"))

assert casos, "No se ejecutaron pruebas"

for caso in casos:

    assert caso.find("failure") is None
    assert caso.find("error") is None
    assert caso.find("skipped") is None

print("PASS: Casos aprobados:", len(casos))
PYTHON

    fi

    echo "Suite: $suite | Exit code: $codigo"

    if [[ "$codigo" -ne 0 ]]; then
        ESTADO=1
    fi
}

ejecutar Unit phpunit.xml

ejecutar Feature phpunit.xml

ejecutar MySql phpunit.mysql.xml

echo "===== RESULTADO PHPUNIT ====="

if [[ "$ESTADO" -eq 0 ]]; then
    echo "PASS: Suites PHPUnit"
else
    echo "FAIL: Revisar resultados PHPUnit"
fi

echo "Reportes privados: $PRIVADO"

exit "$ESTADO"
