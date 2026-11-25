# Validación local de herramientas — 25/11/2026

- Java 21, JMeter 5.6.3, Python estándar, PowerShell 7.4.6 en Linux.
- 11 pruebas del analizador aprobadas: vacío, p95 numérico, categorías ausentes, 4xx deliberados, 409 inesperado, verificaciones fallidas, duplicados y límite estricto del 1%.
- JMeter ejecutó el JMX real contra una API simulada local: escenario normal y regresiones 409 inesperado, pérdida de confirmación y token ausente. Ver jmeter-mock-summary.json. CSV de un registro por perfil mantuvo la identidad durante muchos ciclos.
- Los cuatro scripts PowerShell pasaron el parser; analizar_resultados.ps1 se ejecutó contra un JTL real de esa simulación.
- run_baseline.ps1 también se ejecutó con JMeter real, generó JTL, métricas y HTML; Docker, la guarda de entorno y la auditoría de base fueron sustitutos explícitos en esa comprobación de orquestación. No se ejecutó PHP ni se conectó al backend/MySQL del usuario aquí.

Estos resultados no acreditan carga real, 25 VU sostenidos, SLO de la aplicación ni cero duplicación en su BD. La primera corrida real es smoke-qa24 y debe conservar su manifest/JTL/metrics/backend-integrity y resultado, incluso si falla.
