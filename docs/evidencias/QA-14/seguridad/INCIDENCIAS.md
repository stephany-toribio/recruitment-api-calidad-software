# Incidencias de seguridad QA-14

## QA14-SEC-01 - Posible secreto en reporte interno

- Fecha: 2026-11-25.
- Componente: Karate 1.5.2.
- Severidad propuesta: alta.
- Maven: exit code 0.
- Escaneo preventivo: FAIL.
- Archivo detectado:
  features.smoke.auth-smoke.karate-json.txt.
- Causa confirmada: argumento sensible de call.
- Ubicacion:
  $.scenarioResults[0].stepResults[11].callResults[0].callArg.token
- Limpieza del fixture temporal: PASS.
- Reporte sensible: no publicado.

## Correccion

- Evitar tokens en argumentos explicitos de call.
- Reutilizar variables del contexto del escenario.
- Configurar showCallArg=false.
- Conservar el modificador de logs HTTP.
- Mantener el escaneo de secretos como condicion
  obligatoria antes de publicar evidencias.
- Fortalecer la comprobacion de limpieza.

## Estado

Resuelto.

- Retest autenticado: 4/4 PASS.
- Maven: exit code 0.
- Escaneo preventivo: PASS.
- Fixture eliminado: PASS.
- Usuarios temporales restantes: 0.
- Cuentas compartidas conservadas: 6.

No se incorporan valores de tokens ni contrasenas
en esta evidencia.
