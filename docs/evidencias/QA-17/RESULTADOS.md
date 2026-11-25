# QA-17 - Autorización y conflictos

## Identificación

- Issue: #21.
- Fecha: 25 de noviembre de 2026.
- Herramienta: Karate 1.5.2.
- Ambiente: QA local, Laravel y MySQL.
- API: http://127.0.0.1:8002/api/v1.

## Objetivo

Verificar autorización, pertenencia de recursos,
disponibilidad, duplicidad, transiciones inválidas
y restricciones de retiro.

## Cobertura

| Familia | Feature | Casos canónicos | Escenarios | Estado |
| --- | --- | ---: | ---: | --- |
| CP-06 | perfiles.feature | 4 | 5 | PASS |
| CP-10 | disponibilidad.feature | 11 | 11 | PASS |
| CP-13 | duplicados.feature | 1 | 1 | PASS |
| CP-15 | autorizacion.feature | 4 | 6 | PASS |
| CP-18 | transiciones-invalidas.feature | 3 | 4 | PASS |
| CP-20 | retiros.feature | 4 | 5 | PASS |

## Resultados

| Ejecución | Aprobados | Tiempo JUnit |
| --- | --- | ---: |
| retest | 1/1 | 16.23 s |
| full-01 | 32/32 | 432.1 s |
| full-02 | 32/32 | 448.4 s |
| demo-01 | 5/5 | 71.81 s |
| demo-02 | 5/5 | 68.65 s |
| qa16 | 34/34 | 374.0 s |

## Controles de calidad

- Validación de esquemas JSON.
- Estados HTTP 200, 401, 403, 404, 409 y 422.
- Aserciones de tiempo de respuesta.
- Variables dinámicas y helpers reutilizables.
- Comprobación de persistencia antes y después.
- Escaneo preventivo de secretos.
- Limpieza transaccional por UUID.
- Cero residuos de cada ejecución final.

Cada regresión completa eliminó 87 usuarios
temporales, 87 tokens, 25 vacantes y 14 postulaciones.

## Incidencia QA17-T01

CP-10-02 falló inicialmente porque el helper
no-aprobar-vacante.feature no definía habilidadSchema.

Se reutilizó el esquema de QA-16.
El retest aprobó 1/1 y ambas regresiones 32/32.

Estado: RESUELTO.

No se modificó código productivo Laravel.

## Ejecución

Suite completa:

    unset QA17_TAGS
    qa/karate/scripts/run-qa17.sh full

Demostración:

    unset QA17_TAGS
    qa/karate/scripts/run-qa17.sh demo

## Limitaciones

- CP-13-02 de concurrencia no queda acreditado
  mediante solicitudes secuenciales Karate.
- Cinco condiciones especiales se preparan mediante
  fixtures MySQL restringidos al entorno QA.
- Los tiempos locales no certifican rendimiento bajo carga.
- El escaneo no garantiza ausencia absoluta de secretos.
- Selenium, Appium, JMeter y Jira requieren sus
  propias evidencias.

## Conclusión

QA-17: 27 casos canónicos y 32 escenarios aprobados.

Acumulado Karate:

- QA-14: 4 escenarios.
- QA-15: 39 escenarios.
- QA-16: 34 escenarios.
- QA-17: 32 escenarios.

Total: 109 escenarios diferentes aprobados.

Las repeticiones y demos no constituyen casos nuevos.
