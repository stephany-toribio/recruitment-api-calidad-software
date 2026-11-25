#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

cd "$ROOT"

MODO="${1:-full}"

case "$MODO" in
    full|demo) ;;
    *)
        echo "Uso: run-karate.sh full|demo"
        exit 2
        ;;
esac

unset QA16_TAGS QA17_TAGS

SALIDA="${QA_CI_REPORT_DIR:-$ROOT/qa/karate/target-evidencias/CI}"

mkdir -p "$SALIDA"

PRIVADO="$(mktemp -d)"

chmod 700 "$PRIVADO"

ESTADO=0

ejecutar() {

    local nombre="$1"
    local cantidad="$2"

    shift 2

    echo "===== $nombre ====="

    local log="$PRIVADO/$nombre.txt"
    local codigo=0

    "$@" > "$log" 2>&1 || codigo=$?

    local carpeta_reportes

    carpeta_reportes="$(
        sed -n 's/^Reportes privados: //p' "$log" |
        tail -1
    )"

    mkdir -p "$SALIDA/karate/$nombre"

    if [[ -n "$carpeta_reportes" ]] &&
       [[ -d "$carpeta_reportes/surefire-reports" ]]
    then

        local archivo
        local cantidad_xml=0

        for archivo in "$carpeta_reportes"/surefire-reports/TEST-*.xml
        do

            [[ -f "$archivo" ]] || continue

            cantidad_xml=$((cantidad_xml + 1))

            python3 qa/ci/sanitize-junit.py \
                "$archivo" \
                "$SALIDA/karate/$nombre/$(basename "$archivo")" \
                || codigo=1

        done

        if [[ "$cantidad_xml" -eq 0 ]]; then
            codigo=1
        fi

    else

        codigo=1

    fi


    python3 - \
        "$log" \
        "$SALIDA/$nombre.json" \
        "$nombre" \
        "$cantidad" \
        "$codigo" \
        "$MODO" <<'PY'
import json
import re
import sys
from pathlib import Path

log, destino, suite, esperado, codigo, modo = sys.argv[1:]

s = Path(log).read_text(errors="replace")

esperado = int(esperado)
codigo = int(codigo)

if suite == "QA-14":

    total = re.findall(
        r"Escenarios Karate:\s*(\d+)", s
    )

    aprobados = re.findall(
        r"Aprobados:\s*(\d+)", s
    )

    fallidos = re.findall(
        r"Fallidos:\s*(\d+)", s
    )

    resultado = (
        tuple(map(int, (
            total[-1],
            aprobados[-1],
            fallidos[-1]
        )))
        if total and aprobados and fallidos
        else None
    )

    limpieza = (
        "PASS: Fixture temporal eliminado" in s
    )

    escaneo = (
        "PASS: Sin password temporal" in s
    )

else:

    coincidencias = re.findall(
        r"scenarios:\s*(\d+)\s*\|\s*passed:\s*(\d+)\s*\|\s*failed:\s*(\d+)",
        s
    )

    resultado = (
        tuple(map(int, coincidencias[-1]))
        if coincidencias
        else None
    )

    limpieza = (
        "Fixtures restantes: 0" in s
        and f"PASS: Limpieza {suite}" in s
    )

    escaneo = (
        "PASS: Escaneo preventivo de secretos" in s
    )

correcto = (
    codigo == 0
    and resultado == (esperado, esperado, 0)
    and limpieza
    and escaneo
)

datos = {
    "suite": suite,
    "modo": modo,
    "escenarios_esperados": esperado,
    "escenarios": resultado[0] if resultado else None,
    "aprobados": resultado[1] if resultado else None,
    "fallidos": resultado[2] if resultado else None,
    "codigo_salida": codigo,
    "limpieza": limpieza,
    "escaneo": escaneo,
    "resultado": "PASS" if correcto else "FAIL"
}

Path(destino).write_text(
    json.dumps(datos, indent=2) + "\n"
)

print(json.dumps(datos))

sys.exit(0 if correcto else 1)
PY
}

if [[ "$MODO" == "full" ]]; then

    N15=39
    N16=34
    N17=32

else

    N15=8
    N16=4
    N17=5

fi

ejecutar QA-14 4 \
    qa/karate/scripts/run-local.sh || ESTADO=1

ejecutar QA-15 "$N15" \
    qa/karate/scripts/run-qa15.sh "$MODO" || ESTADO=1

ejecutar QA-16 "$N16" \
    qa/karate/scripts/run-qa16.sh "$MODO" || ESTADO=1

ejecutar QA-17 "$N17" \
    qa/karate/scripts/run-qa17.sh "$MODO" || ESTADO=1

echo "===== RESUMEN KARATE CI ====="

python3 - "$SALIDA" "$MODO" <<'PY'
from pathlib import Path
import json
import sys

base = Path(sys.argv[1])
modo = sys.argv[2]

suites = [
    json.loads((base / f"QA-{n}.json").read_text())
    for n in range(14, 18)
]

total = sum(
    r["escenarios"] or 0 for r in suites
)

aprobados = sum(
    r["aprobados"] or 0 for r in suites
)

esperado = 109 if modo == "full" else 21

correcto = (
    total == esperado
    and aprobados == esperado
    and all(
        r["resultado"] == "PASS"
        for r in suites
    )
)

resumen = {
    "modo": modo,
    "esperados": esperado,
    "ejecutados": total,
    "aprobados": aprobados,
    "resultado": "PASS" if correcto else "FAIL",
    "suites": suites
}

(base / "resumen.json").write_text(
    json.dumps(resumen, indent=2) + "\n"
)

print(json.dumps(resumen, indent=2))

sys.exit(0 if correcto else 1)
PY

exit "$ESTADO"
