# Resultados de QA-08 - Postulaciones, retiro y concurrencia

## 1. Identificacion

- Tarea: QA-08.
- Issue: stephany-toribio/recruitment-api-calidad-software#16.
- Epica: E02.
- Hito: M1.
- Prioridad: P0.
- Responsable: stephany-toribio.
- Fecha: 2026-11-25.
- Rama: feature/QA-08-postulaciones-retiro.
- Ambiente: WSL2, Docker, PHP 8.3.37, PHPUnit 11.5.56.
- Base aislada: portalempleoshu04testing.

## 2. Objetivo

Verificar la creacion, duplicidad, concurrencia,
disponibilidad, retiro, seguimiento e integridad
de las postulaciones.

## 3. Linea base

| Suite | Pruebas | Aserciones | Fallos |
| --- | ---: | ---: | ---: |
| PostularVacanteMySqlTest | 66 | 297 | 0 |
| PostularVacanteConcurrenciaMySqlTest | 4 | 38 | 0 |
| RetirarPostulacionMySqlTest | 46 | 232 | 0 |
| RetirarPostulacionConcurrenciaMySqlTest | 4 | 43 | 0 |
| Total | 120 | 610 | 0 |

## 4. Pruebas incorporadas

| Grupo | Pruebas | Aserciones |
| --- | ---: | ---: |
| Concurrencia mediante HTTP Kernel | 2 | 33 |
| Seguimiento CP-21 | 6 | 37 |
| Multiples postulantes CP-14-02 | 1 | 12 |
| Total | 9 | 82 |

No se modificaron las reglas funcionales de Laravel.

## 5. Concurrencia

### CP-13-02

Dos procesos independientes ejecutan solicitudes
autenticadas del mismo candidato.

Resultado:
- HTTP 201.
- HTTP 409.
- DUPLICATE_APPLICATION.
- Una unica postulacion persistida.

### CP-14-01

Dos candidatos diferentes postulan concurrentemente
a una vacante con un puesto disponible.

Resultado:
- HTTP 201.
- HTTP 201.
- Dos postulaciones persistidas.

### CP-14-02

Cinco candidatos previos y un sexto candidato
postulan a una vacante con un puesto.

Resultado:
- Seis postulaciones en total.
- El numero de puestos permanece en uno.

## 6. Seguimiento

Se verifica:

- Listado vacio.
- Aislamiento de postulaciones por candidato.
- Conservacion de postulaciones retiradas.
- Conservacion de vacantes pausadas.
- Conservacion de vacantes eliminadas.
- Rechazo sin autenticacion.
- Rechazo de roles incompatibles.

## 7. Regresion final

| Suite | Pruebas | Aserciones | Fallos | Duracion |
| --- | ---: | ---: | ---: | ---: |
| Unit/Feature | 259 | 1645 | 0 | 7.180 s |
| MySQL | 442 | 3999 | 0 | 61.871 s |
| Total | 701 | 5644 | 0 | 69.051 s |

Las nueve pruebas nuevas se incluyen en la regresion
MySQL y no deben contabilizarse nuevamente.

## 8. Trazabilidad

Familias canonicas:
CP-12, CP-13, CP-14, CP-20 y CP-21.

Se conservan los identificadores de QA-04.

## 9. Evidencias

- backend/antes/
- backend/concurrencia/
- backend/seguimiento/
- backend/postulacion/
- backend/final/
- DEFECTOS.md
- SHA256SUMS.txt

## 10. Limitaciones

La concurrencia utiliza procesos PHP y conexiones
MySQL independientes, atravesando el HTTP Kernel
de Laravel con tokens Sanctum.

No corresponde a trafico HTTP por red ni sustituye
las pruebas de carga de JMeter.

Las pruebas PHPUnit no sustituyen Karate.

La validacion E2E permanece pendiente de Selenium
y Appium.

## 11. Conclusion

Las nueve pruebas nuevas y la regresion completa
finalizaron satisfactoriamente.

La integracion de ambos PR y la actualizacion
del issue y Project completaran el cierre administrativo.
