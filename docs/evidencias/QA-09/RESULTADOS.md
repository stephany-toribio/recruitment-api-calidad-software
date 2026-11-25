# Resultados de QA-09 - Kanban, decisiones e historial

## 1. Identificacion

- Tarea: QA-09.
- Issue: stephany-toribio/web-app-calidad-software#5.
- Epica: E02.
- Hito: M1.
- Prioridad: P0.
- Responsable: stephany-toribio.
- Fecha: 2026-11-25.
- Rama: feature/QA-09-kanban-decisiones.
- Ambiente: WSL2, Docker, PHP 8.3.37.
- Framework de pruebas: PHPUnit 11.5.56.
- Base MySQL aislada: portalempleoshu04testing.

## 2. Objetivo

Verificar las transiciones de Kanban, decisiones
empresariales, limites de contratacion, concurrencia,
historial persistente y seguimiento del candidato.

## 3. Linea base

| Suite | Pruebas | Aserciones | Fallos |
| --- | ---: | ---: | ---: |
| KanbanTest SQLite | 16 | 190 | 0 |
| Retiro concurrente MySQL | 4 | 43 | 0 |

## 4. Nuevas pruebas

| Grupo | Pruebas | Aserciones |
| --- | ---: | ---: |
| Concurrencia HTTP Kernel | 3 | 59 |
| Integracion de historial | 4 | 45 |
| Total | 7 | 104 |

## 5. Concurrencia

| Caso | Respuestas | Resultado persistido |
| --- | --- | --- |
| CP-19-01 | 200 y 409 | Un contratado para un puesto |
| CP-19-02 | 200 y 200 | Dos contratados para dos puestos |
| Contratacion duplicada | 200 y 409 | Una unica contratacion |

Codigos de conflicto:

- HIRING_LIMIT_REACHED.
- INVALID_TRANSITION.

Se verifican conexiones MySQL independientes,
bloqueos de vacante y un movimiento de contratacion
por cada candidato contratado.

## 6. Historial e integracion

- CP-22-01: secuencia cronologica de contratacion.
- CP-22-02: conservacion del motivo de rechazo.
- CP-22-03: conservacion de movimientos tras retiro.
- Seguimiento: decision visible para el candidato
  mediante GET autenticado.

Las cuatro pruebas superaron el retest:
4 pruebas y 45 aserciones.

## 7. Incidencias tecnicas

- QA09-T01: error Unicode en script Python.
- QA09-T02: catalogo incompleto de estados MySQL.

Ambas incidencias de preparacion fueron resueltas.

No se modificaron reglas funcionales de Laravel.

## 8. Suite ampliada

RetirarPostulacionConcurrenciaMySqlTest:

- 11 pruebas.
- 147 aserciones.
- 0 fallos.
- Duracion: 4.485 segundos.

## 9. Regresion final

| Suite | Pruebas | Aserciones | Fallos | Duracion |
| --- | ---: | ---: | ---: | ---: |
| Unit/Feature | 259 | 1645 | 0 | 6.682 s |
| MySQL | 449 | 4103 | 0 | 75.287 s |
| Total | 708 | 5748 | 0 | 81.969 s |

Las siete pruebas nuevas estan incluidas en la
regresion MySQL y no se contabilizan nuevamente.

## 10. Trazabilidad

Familias canonicas:
CP-16, CP-17, CP-18, CP-19, CP-21 y CP-22.

Se mantienen los identificadores definidos en QA-04.

## 11. Evidencias

- backend/antes/
- backend/concurrencia/
- backend/historial/
- backend/final/
- DEFECTOS.md
- SHA256SUMS.txt

## 12. Limitaciones

La concurrencia HTTP se ejecuta mediante el Kernel
de Laravel y procesos independientes.

No corresponde a trafico HTTP real por red.

Las pruebas de historial incluyen llamadas directas
a servicios con persistencia MySQL real.

Las notificaciones se aislan mediante mocks en
las nuevas pruebas para evitar correos reales.

PHPUnit no sustituye Karate ni JMeter.
Las verificaciones visuales y E2E corresponden
a Selenium y Appium.

## 13. Conclusion

La regresion backend completa fue satisfactoria.
Las transiciones, cupos concurrentes, historial
y seguimiento evaluados mantienen su consistencia.

El cierre administrativo requiere integrar los PR
de ambos repositorios y verificar el issue y Project.
