# QA-24 — Resumen de validación

## Implementación
- Modelo mixto de 25 VU: 15 consulta, 4 login y 6 postulación.
- Exploración diseñada para 10/25/50 VU, con condiciones de parada.
- CSV: 50 cuentas únicas por perfil; 150 en total.
- Correlación de tokens, usuarios, vacantes y postulaciones.
- Casos negativos separados de errores inesperados.
- Resultados por corrida y auditoría SQL posterior.

## Ejecución real
- Diagnóstico mixto de 3 VU: cero errores inesperados,
  integridad SQL verificada y latencias fuera del objetivo.
- Verificación funcional de postulación con 1 VU: PASA.
- Tres creaciones confirmadas por GET y auditoría SQL.
- Duplicada deliberada: 409; GET posterior conserva el ID.
- Login inexistente: 401 esperado comprobado en el diagnóstico mixto.

## Rendimiento y límites
Los objetivos iniciales NO se cumplieron.
No se ejecutó la línea base de 25 VU ni la exploración 10/25/50
porque el diagnóstico superó los límites de latencia.
La causa sigue pendiente de identificar.
No se relajaron los umbrales ni se declara capacidad sostenida.

## Trazabilidad
Diseño vigente: docs/qa/QA24_DISENO_REVISADO.md.
Evidencias reales: ejecucion-real/smoke-3vu y postulacion-1vu.
Las pruebas locales simuladas están identificadas por separado.
Corroborar dependencias QA-02/QA-04 y revisión R1 en el PR.
El cierre formal requiere revisión e integración.
