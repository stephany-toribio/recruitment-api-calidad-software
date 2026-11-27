#!/usr/bin/env bash

set -euo pipefail

umask 077

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"

cd "$ROOT"

EVIDENCIA="${QA14_EVIDENCIA_DIR:-$ROOT/qa/karate/target-evidencias/QA-14}"

mkdir -p "$EVIDENCIA"

PRIVADO="$(mktemp -d)"

RUN_ID="$(python3 -c 'import uuid; print(uuid.uuid4())')"

export KARATE_FIXTURE_EMAIL="karate-${RUN_ID}@qa.example.test"

export KARATE_FIXTURE_PASSWORD="$(
    python3 -c 'import secrets; print("Qa1#" + secrets.token_hex(16))'
)"

export KARATE_RUN_ID="$RUN_ID"

CREADO=0

limpiar() {
    if [ "$CREADO" -eq 1 ]; then
        python3 -c '
import json, os
print(json.dumps({
    "accion": "limpiar",
    "run_id": os.environ["KARATE_RUN_ID"]
}))
' | docker compose exec -T app \
        php qa/karate/scripts/fixture-auth.php \
        > "$PRIVADO/limpieza.json" || return 1

        python3 - "$PRIVADO/limpieza.json" <<'PY' || return 1
import json, sys

datos = json.load(open(sys.argv[1]))

assert datos["success"] is True
assert datos["fixture_existente"] is False

print("PASS: Fixture temporal eliminado")
PY

        CREADO=0
    fi
}

finalizar() {
    resultado=$?

    trap - EXIT

    if ! limpiar; then
        resultado=1
        echo "ERROR: Revisar limpieza del fixture $RUN_ID"
    fi

    unset KARATE_FIXTURE_PASSWORD
    unset KARATE_FIXTURE_EMAIL

    echo "Reportes privados: $PRIVADO"

    exit "$resultado"
}

trap finalizar EXIT

echo "===== PREPARAR FIXTURE ====="

# Marcar el intento antes de crear para limpiar
# tambien ante una interrupcion posterior.
CREADO=1

python3 -c '
import json, os

print(json.dumps({
    "accion": "crear",
    "run_id": os.environ["KARATE_RUN_ID"],
    "password": os.environ["KARATE_FIXTURE_PASSWORD"]
}))
' | docker compose exec -T app \
    php qa/karate/scripts/fixture-auth.php \
    > "$PRIVADO/creacion.json"

python3 - "$PRIVADO/creacion.json" <<'PY'
import json, sys

datos = json.load(open(sys.argv[1]))

assert datos["success"] is True

print("PASS: Usuario temporal creado")
PY

echo "===== KARATE ====="

set +e

mvn -B -ntp \
    -f qa/karate/pom.xml \
    -Dkarate.env=local \
    -Dtest=SmokeTest,SensitiveLogModifierTest \
    clean test \
    > "$PRIVADO/maven.txt" 2>&1

RESULTADO=$?

set -e

if [ -d qa/karate/target/karate-reports ]; then
    cp -a qa/karate/target/karate-reports "$PRIVADO/"
elif [ "$RESULTADO" -eq 0 ]; then
    echo "ERROR: Reportes Karate no generados"
    exit 1
fi

if [ -d qa/karate/target/surefire-reports ]; then
    cp -a qa/karate/target/surefire-reports "$PRIVADO/"
elif [ "$RESULTADO" -eq 0 ]; then
    echo "ERROR: Reportes Surefire no generados"
    exit 1
fi

echo "Exit code Maven: $RESULTADO"

if [ "$RESULTADO" -ne 0 ]; then
    echo "ERROR: Ejecucion Karate fallida."
    echo "Revisar localmente el log privado."
    exit "$RESULTADO"
fi

echo "===== COMPROBAR EXPOSICION DE SECRETOS ====="

python3 - "$PRIVADO" <<'PY'
import os, re, sys
from pathlib import Path

base = Path(sys.argv[1])

password = os.environ[
    "KARATE_FIXTURE_PASSWORD"
].encode()

token_pattern = re.compile(
    rb'\b[0-9]+\|[A-Za-z0-9]{30,}\b'
)

hallazgos = []

for archivo in base.rglob("*"):
    if not archivo.is_file():
        continue

    contenido = archivo.read_bytes()

    if password in contenido or token_pattern.search(contenido):
        hallazgos.append(str(archivo.relative_to(base)))

if hallazgos:
    print("ERROR: Posible secreto detectado en:")

    for archivo in hallazgos:
        print(archivo)

    sys.exit(1)

print("PASS: Sin password temporal ni tokens reconocibles en reportes privados")
PY

echo "===== VERIFICAR ESCENARIOS ====="

python3 - "$PRIVADO/maven.txt" "$EVIDENCIA" <<'PY'
import re, sys
from pathlib import Path

texto = Path(sys.argv[1]).read_text()

resultados = re.findall(
    r"scenarios:\s+(\d+)\s+\|\s+passed:\s+(\d+)\s+\|\s+failed:\s+(\d+)",
    texto
)

assert resultados, "No existe resumen Karate"

total, aprobados, fallidos = map(int, resultados[-1])

assert total == 4
assert aprobados == 4
assert fallidos == 0
assert "BUILD SUCCESS" in texto

resumen = (
    f"Escenarios Karate: {total}\n"
    f"Aprobados: {aprobados}\n"
    f"Fallidos: {fallidos}\n"
    "Maven: BUILD SUCCESS\n"
    "Revision automatica de secretos: PASS\n"
)

print(resumen)

Path(sys.argv[2], "resultados.txt").write_text(resumen)
PY

echo "===== LIMPIEZA ====="

limpiar

cp "$PRIVADO/limpieza.json" \
    "$EVIDENCIA/limpieza.json"

echo "PASS: Ciclo completo de preparacion, prueba y limpieza"
