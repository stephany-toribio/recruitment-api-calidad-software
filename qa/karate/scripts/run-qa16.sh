#!/usr/bin/env bash

set -euo pipefail

umask 077

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"

cd "$ROOT"

MODO="${1:-full}"

case "$MODO" in
    full|demo) ;;
    *)
        echo "Modo permitido: full o demo"
        exit 2
        ;;
esac

echo "===== PREFLIGHT QA-16 ====="

docker compose exec -T app php -l qa/karate/scripts/fixture-qa16.php

docker compose exec -T app php -l qa/karate/scripts/fixture-qa15.php

curl -fsS -o /dev/null http://127.0.0.1:8002/up

echo "PASS: Preflight QA-16"

PRIVADO="$(mktemp -d)"

export KARATE_RUN_ID="$(
    python3 -c 'import uuid; print(uuid.uuid4())'
)"

export KARATE_FIXTURE_PASSWORD="$(
    python3 -c 'import secrets; print("Qa1#" + secrets.token_hex(16))'
)"

unset KARATE_FIXTURE_EMAIL

limpiar() {
    python3 -c '
import json, os

print(json.dumps({
    "accion": "limpiar",
    "run_id": os.environ["KARATE_RUN_ID"]
}))
' | docker compose exec -T app \
        php qa/karate/scripts/fixture-qa16.php \
        > "$PRIVADO/limpieza.json" || return 1

    python3 - "$PRIVADO/limpieza.json" <<'PY' || return 1
import json, sys

datos = json.load(open(sys.argv[1]))

assert datos["success"] is True
assert datos["restantes"] == 0

assert all(
    cantidad == 0
    for campo, cantidad in datos["despues"].items()
    if campo != "success"
)

print("Entidades eliminadas:", datos["borrados"])
print("PASS: Sin residuos funcionales")

print("Fixtures eliminados:", datos["eliminados"])
print("Fixtures restantes:", datos["restantes"])
print("PASS: Limpieza QA-16")
PY
}

finalizar() {
    resultado=$?

    trap - EXIT

    if ! limpiar; then
        resultado=1
        echo "ERROR: Revisar limpieza de $KARATE_RUN_ID"
    fi

    unset KARATE_FIXTURE_PASSWORD

    echo "Reportes privados: $PRIVADO"

    exit "$resultado"
}

trap finalizar EXIT

echo "===== QA-16 ====="
echo "Modo: $MODO"
echo "Run ID: $KARATE_RUN_ID"

echo "===== EJECUCION KARATE ====="

set +e

mvn -B -ntp \
    -f qa/karate/pom.xml \
    -Dkarate.env=local \
    -Dqa.tags="${QA16_TAGS:-@QA-16}" \
    -Dqa.mode="$MODO" \
    -Dtest=BusinessTest \
    clean test \
    2>&1 | tee "$PRIVADO/maven.txt" | awk '
    /^feature: classpath:features\/business\// ||
    /^scenarios:/ ||
    /^\[INFO\] BUILD SUCCESS/ ||
    /^\[INFO\] BUILD FAILURE/ {
        print
        fflush()
    }
'

CODIGOS=("${PIPESTATUS[@]}")

RESULTADO="${CODIGOS[0]}"

for CODIGO in "${CODIGOS[@]}"; do
    if [ "$CODIGO" -ne 0 ]; then
        RESULTADO="$CODIGO"
        break
    fi
done

set -e

if [ -d qa/karate/target/karate-reports ]; then
    cp -a qa/karate/target/karate-reports \
        "$PRIVADO/"
elif [ "$RESULTADO" -eq 0 ]; then
    echo "ERROR: No se generaron reportes"
    exit 1
fi

if [ -d qa/karate/target/surefire-reports ]; then
    cp -a qa/karate/target/surefire-reports "$PRIVADO/"
elif [ "$RESULTADO" -eq 0 ]; then
    echo "ERROR: No se generaron reportes Surefire"
    exit 1
fi

echo "Exit code Maven: $RESULTADO"

python3 - "$PRIVADO" <<'PY'
import os, re, sys
from pathlib import Path

base = Path(sys.argv[1])

password = os.environ[
    "KARATE_FIXTURE_PASSWORD"
].encode()

patron = re.compile(
    rb'\b[0-9]+\|[A-Za-z0-9]{30,}\b'
)

hallazgos = []

for archivo in base.rglob("*"):
    if archivo.is_file():
        contenido = archivo.read_bytes()

        if password in contenido or patron.search(contenido):
            hallazgos.append(
                str(archivo.relative_to(base))
            )

if hallazgos:
    print("ERROR: Posibles secretos en reportes")

    for nombre in hallazgos:
        print(nombre)

    sys.exit(1)

print("PASS: Escaneo preventivo de secretos")
PY

grep -E \
    'scenarios:|BUILD SUCCESS|BUILD FAILURE|Tests run:' \
    "$PRIVADO/maven.txt" || true

if [ "$RESULTADO" -ne 0 ]; then
    echo "ERROR: Revisar ejecucion Karate privada"
    exit "$RESULTADO"
fi

echo "PASS: Ejecucion funcional QA-16"
