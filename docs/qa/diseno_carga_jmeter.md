# QA-24 - Diseno de cargas, CSV y objetivos de rendimiento (JMeter)

## Modelo de carga y mezcla (25 VU)
| Perfil | Thread Group | VU | % | Operaciones |
|---|---|---|---|---|
| Consulta | TG_Consulta | 15 | 60% | login unico, listar ofertas, detalle |
| Login | TG_Login | 4 | 15% | login valido + login invalido (4xx esperado) |
| Postulacion | TG_Postulacion | 6 | 25% | login unico, listar, postular, verificar por GET |

Ramp-up 30 s, duracion 300 s, think time uniforme 1-3 s (Uniform Random Timer). Mezcla ajustable con `-Users`.

## Datos y correlacion
- `data/usuarios_{lectura,login,postulacion}.csv`: 50 cuentas ficticias cada uno (dominio `carga.qa.invalid`), un perfil por archivo, `shareMode.all`, `recycle=false`, `stopThread=true`: ninguna cuenta se comparte entre hilos.
- Correlacion: JSON Extractor de `token`, `jobId`, `appId`; header `Authorization: Bearer ${token}`.
- Creacion de cuentas en QA: `crear_cuentas.ps1`.

## Objetivos iniciales (a revisar tras medir la linea base)
- p95 lecturas <= 2 s; p95 escrituras/login <= 3 s
- Errores inesperados < 1%; cero perdida/duplicacion de datos
- 4xx esperados (login invalido, 409 por postulacion duplicada) se marcan como exito con Response Assertion; solo 5xx, timeouts o codigos no previstos cuentan como error.

## Recursos y ejecucion
- Herramienta: Apache JMeter 5.6.x + JDK 17, modo CLI (listeners GUI desactivados).
- Linea base: `performance\jmeter\run_baseline.ps1`
- Exploracion 10/25/50 VU (solo QA aislado): `run_exploracion.ps1 -ConfirmoQAAislado`
- Parada: primer error inesperado (`stoptest`), errores > 5% o p95 > 2x objetivo.

## Modelo de resultados (PENDIENTE: completar con ejecucion real)
| Escenario | VU | p95 lecturas | p95 escr./login | % err. inesperados | Throughput | Commit | Ambiente | Veredicto |
|---|---|---|---|---|---|---|---|---|
| Linea base | 25 | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE |
| Exploracion | 10 | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE |
| Exploracion | 50 | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE | PENDIENTE |

Casos: CP-31, CP-32, CP-33 (estado: PENDIENTE hasta tener evidencia real).
## Actualización QA-24
El diseño vigente y las correcciones están en QA24_DISENO_REVISADO.md.
Los resultados reales y las limitaciones están en
../evidencias/QA-24/RESUMEN.md.
Esta actualización sustituye las afirmaciones anteriores incompatibles
sobre porcentajes, aceptación general de 409 y resultados pendientes.
