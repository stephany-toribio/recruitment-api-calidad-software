"""Analisis QA-24: solo mediciones reales del JTL, sin aprobar archivos vacios."""
import argparse
import csv
import json
import math
from collections import Counter
from pathlib import Path


def p95(values):
    ordered = sorted(values)
    return ordered[math.ceil(len(ordered) * .95) - 1] if ordered else None


def analyze(path, audit=None):
    with Path(path).open(encoding='utf-8-sig', newline='') as f:
        reader = csv.DictReader(f)
        required = {'timeStamp', 'elapsed', 'label', 'responseCode', 'success'}
        if not required.issubset(reader.fieldnames or []):
            raise ValueError('JTL sin columnas requeridas')
        rows = list(reader)
    if not rows:
        raise ValueError('JTL vacio: no hay resultados evaluables')
    for row in rows:
        row['elapsed'] = float(row['elapsed'])
        row['timeStamp'] = int(row['timeStamp'])
        row['ok'] = row['success'].lower() == 'true'
        if row['elapsed'] < 0:
            raise ValueError('Duracion negativa')
    http = [r for r in rows if not r['label'].startswith('SETUP_')]
    if not http:
        raise ValueError('JTL sin solicitudes HTTP')
    expected_codes = {'EXPECTED_401_login': '401', 'EXPECTED_409_duplicada': '409'}
    expected = [r for r in http if r['label'] in expected_codes]
    core = [r for r in http if r['label'] not in expected_codes]

    def good(row):
        label = row['label']
        code = expected_codes.get(label, '201' if label == 'WRITE_postular' else '200')
        return row['ok'] and row['responseCode'] == code

    unexpected = [r for r in http if not good(r)]
    setup_errors = [r for r in rows if r['label'].startswith('SETUP_') and not r['ok']]
    # Expected successful negatives never dilute the error denominator.
    denominator = len(core) + sum(not good(r) for r in expected)
    err = 100 * len(unexpected) / denominator if denominator else None
    groups = {}
    for category, prefix in [('read', 'READ_'), ('login', 'LOGIN_'), ('write', 'WRITE_')]:
        selected = [r for r in core if r['label'].startswith(prefix)]
        groups[category] = {'n': len(selected), 'p95_ms': p95([r['elapsed'] for r in selected])}
    labels = {}
    for label in sorted({r['label'] for r in http}):
        selected = [r for r in http if r['label'] == label]
        labels[label] = {'n': len(selected), 'errors': sum(not good(r) for r in selected),
                         'p95_ms': p95([r['elapsed'] for r in selected])}
    start = min(r['timeStamp'] for r in http)
    end = max(r['timeStamp'] + r['elapsed'] for r in http)
    expected_writes = []
    for row in core:
        if row['label'] == 'WRITE_postular' and good(row):
            entry = {}
            for key, column in [('user_id', 'qa_user_id'), ('job_id', 'qa_job_id'), ('app_id', 'qa_app_id')]:
                try:
                    entry[key] = int(row.get(column, ''))
                    assert entry[key] > 0
                except (ValueError, AssertionError):
                    entry[key] = None
            entry['vu_id'] = row.get('qa_vu', '')
            expected_writes.append(entry)
    pairs = Counter((r['user_id'], r['job_id']) for r in expected_writes)
    valid_audit = bool(expected_writes) and all(all(r[k] for k in ('user_id', 'job_id', 'app_id')) for r in expected_writes)
    duplicates = sum(n - 1 for n in pairs.values() if n > 1)
    verified = {(r.get('qa_user_id'), r.get('qa_job_id'), r.get('qa_app_id')) for r in core
                if r['label'].startswith('READ_verificar') and good(r)}
    without_get = sum(tuple(str(r[k]) for k in ('user_id', 'job_id', 'app_id')) not in verified for r in expected_writes)
    performance = (all(groups[k]['n'] for k in groups) and err is not None and err < 1
                   and groups['read']['p95_ms'] <= 2000 and groups['login']['p95_ms'] <= 3000
                   and groups['write']['p95_ms'] <= 3000 and not setup_errors)
    unconfirmed_writes = sum(r['label'] == 'WRITE_postular' and not good(r) for r in core)
    db_ok = audit is not None and audit.get('success') is True and audit.get('checked') == len(expected_writes) and len(expected_writes) > 0
    return {
        'schema': 1, 'http_samples': len(http), 'positive_samples': len(core),
        'expected_4xx': sum(good(r) for r in expected), 'expected_case_failures': sum(not good(r) for r in expected),
        'unexpected_errors': len(unexpected), 'setup_errors': len(setup_errors), 'error_pct': err,
        'duration_observed_s': (end - start) / 1000,
        'throughput_http_s': len(http) / ((end - start) / 1000) if end > start else None,
        'latency': groups, 'labels': labels,
        'active_peak_reported': max((int(r.get('allThreads') or 0) for r in rows), default=0),
        'successful_creates': len(expected_writes), 'unconfirmed_writes': unconfirmed_writes, 'duplicate_successful_pairs': duplicates,
        'creates_without_correlated_get': without_get,
        'client_ids_available': valid_audit,
        'performance': 'CUMPLE' if performance else 'NO_CUMPLE_O_INCOMPLETO',
        'backend_integrity': 'VERIFICADA' if db_ok and unconfirmed_writes == 0 else 'PENDIENTE_O_FALLIDA',
        'overall': 'CUMPLE' if performance and db_ok and valid_audit and duplicates == 0 and unconfirmed_writes == 0 else 'NO_APROBADO',
        'expected': expected_writes,
    }


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--jtl', required=True)
    p.add_argument('--audit')
    p.add_argument('--output')
    args = p.parse_args()
    source = Path(args.jtl)
    audit = json.loads(Path(args.audit).read_text(encoding='utf-8-sig')) if args.audit else None
    result = analyze(source, audit)
    output = Path(args.output) if args.output else source.parent / 'metrics.json'
    manifest_path = source.parent / 'manifest.json'
    if manifest_path.exists():
        manifest = json.loads(manifest_path.read_text(encoding='utf-8-sig'))
        result['requested_users'] = manifest.get('users')
        result['requested_duration_s'] = manifest.get('duration_s')
        result['run_conditions_met'] = (
            result['active_peak_reported'] >= int(manifest.get('users', 0))
            and result['duration_observed_s'] >= max(0, int(manifest.get('duration_s', 0)) - int(manifest.get('ramp_up_s', 0)) - 5)
            and manifest.get('jmeter_exit_code') == 0
        )
        if not result['run_conditions_met']:
            result['overall'] = 'INCOMPLETO'
    output.write_text(json.dumps(result, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')
    (output.parent / 'expected-integrity.json').write_text(json.dumps({'expected': result['expected']}, indent=2) + '\n', encoding='utf-8')
    for label, row in result['labels'].items():
        print(f"{label}: n={row['n']} p95={row['p95_ms']} ms errores={row['errors']}")
    print(f"HTTP={result['http_samples']} | 4xx esperados={result['expected_4xx']} | errores inesperados={result['error_pct']}%")
    print(f"Rendimiento={result['performance']} | Integridad BD={result['backend_integrity']} | Global={result['overall']}")
    print('Objetivos: p95 READ <=2000 ms; LOGIN y WRITE <=3000 ms; errores inesperados <1%.')
    print('Muestras insuficientes no acreditan estabilidad ni capacidad sostenida. Revisar duracion, VU y manifest.json.')


if __name__ == '__main__':
    main()
