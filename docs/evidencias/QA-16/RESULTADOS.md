# QA-16 - Automatizacion Karate del flujo de negocio

## Identificacion

- Issue GitHub: #20.
- Rama: feature/QA-16-karate-flujo-negocio.
- Fecha de ejecucion: 25 de noviembre de 2026.
- Ambiente: QA local aislado.
- API: http://127.0.0.1:8002/api/v1.
- Base de datos: portal_empleos_calidad_qa.
- Java: 21.
- Maven: 3.9.12.
- Karate: 1.5.2.
- Ejecucion: secuencial.

## Objetivo

Verificar mediante Karate los contratos HTTP y JSON,
las reglas funcionales y la persistencia del flujo
de vacantes, pagos simulados, postulaciones,
contratacion, rechazo, seguimiento e historial.

## Cobertura

| Familia | Casos canonicos | Escenarios | Resultado |
| --- | ---: | ---: | --- |
| CP-07 | 4 | 4 | PASS |
| CP-08 | 4 | 5 | PASS |
| CP-09 | 5 | 9 | PASS |
| CP-12 | 2 | 2 | PASS |
| CP-16 | 3 | 3 | PASS |
| CP-17 | 3 | 4 | PASS |
| CP-21 | 2 | 3 | PASS |
| CP-22 | 3 | 3 | PASS |
| QA16-E2E-01 | Complementario | 1 | PASS |
| Total | 26 | 34 | PASS |

## Regresiones finales

| Ejecucion | Aprobados | Tiempo JUnit |
| --- | --- | ---: |
| Full inicial | 34/34 | 388.6 s |
| Full 01 | 34/34 | 308.1 s |
| Full 02 | 34/34 | 251.6 s |
| Demo 01 | 4/4 | 32.96 s |
| Demo 02 | 4/4 | 33.80 s |
| Regresion QA-14 | 4/4 | No registrado en resumen |
| Regresion QA-15 | 39/39 | 71.53 s |

Las ejecuciones utilizan identificadores independientes.

Los tiempos corresponden al runner JUnit y no al
tiempo total de preparacion, compilacion y limpieza.

## Flujo integral

1. Registro y autenticacion de empresa.
2. Creacion de vacante.
3. Aprobacion administrativa.
4. Pago simulado.
5. Registro y autenticacion del candidato.
6. Busqueda de vacante publicada.
7. Consulta del detalle.
8. Postulacion.
9. Avance a En Revision.
10. Avance a Entrevista.
11. Contratacion.
12. Consulta del resultado por el candidato.
13. Consulta del historial por la empresa.
14. Comprobacion de persistencia MySQL.

Las operaciones funcionales se realizan mediante HTTP.

## Rubrica Karate

- RUB-K01: contratos y esquemas JSON.
- RUB-K02: validacion de estados HTTP.
- RUB-K03: aserciones de tiempo de respuesta.
- RUB-K04: UUID, datos, tokens e identificadores dinamicos.
- RUB-K05: reutilizacion de features mediante call.

El umbral local de respuesta es provisional y
no constituye una certificacion de rendimiento.

## Seguridad de reportes

- Enmascaramiento de cabeceras sensibles.
- Redaccion de cuerpos HTTP.
- Eliminacion de tokens de los argumentos callArg.
- Escaneo preventivo de reportes.
- Reportes RED sensibles excluidos de Git.
- Contraseñas temporales aleatorias.
- Revocacion mediante eliminacion de tokens de fixtures.

El escaneo no garantiza detectar todos los
formatos posibles de informacion sensible.

## Limpieza y aislamiento

En cada regresion completa se eliminaron:

- 82 usuarios temporales.
- 35 empresas.
- 17 candidatos.
- 32 vacantes.
- 160 relaciones de habilidades de vacantes.
- 27 revisiones administrativas.
- 19 pagos simulados.
- 17 postulaciones.
- 23 movimientos historicos.
- 5 notificaciones.
- 82 tokens.

Tambien se eliminaron las relaciones de perfiles
de candidatos.

Resultado: cero residuos del UUID de la ejecucion.

La limpieza se realiza transaccionalmente y
rechaza dependencias externas inesperadas.

## Incidencias

- QA16-T01: sintaxis PHP.
- QA16-T02: longitud de arreglos.
- QA16-T03: tokens en argumentos callArg.
- QA16-T04: conflicto de contexto.
- QA16-T05: metodo HTTP dinamico.
- QA16-T06: variables interpretadas como cadenas.

Las seis incidencias fueron corregidas y
verificadas mediante retests GREEN.

QA16-D01: discrepancia documental CP-22-02
conciliada con el contrato implementado.

Consultar INCIDENCIAS.md.

## Ejecucion reproducible

Desde la raiz del repositorio:

    unset QA16_TAGS
    qa/karate/scripts/run-qa16.sh full

Demostracion en vivo:

    unset QA16_TAGS
    qa/karate/scripts/run-qa16.sh demo

El modo full exige 34 escenarios.
El modo demo exige cuatro escenarios.

## Evidencias

- regresion/: resumen de ejecuciones.
- reportes/: archivos Karate y Surefire.
- fixtures/: generadores y catalogos.
- INCIDENCIAS.md: diagnosticos y retests.
- SHA256SUMS.txt: integridad de evidencias.

## Limitaciones

- No se verifica rendimiento bajo carga.
- No se certifica entrega efectiva de correos.
- No se ejecuta contra un ambiente HTTPS remoto.
- No se validan interfaces web ni Android.
- No se certifica ausencia absoluta de secretos.
- La ejecucion requiere ambiente QA autorizado.
- Las pruebas concurrentes requieren coordinacion.
- La autorizacion exhaustiva corresponde a QA-17.
- La vinculacion Jira corresponde a QA-29.

## Conclusion

QA-16 cumple su cobertura funcional planificada
en el ambiente local verificado.

Se aprobaron 34 escenarios Karate correspondientes
a 26 casos canonicos y un flujo integral adicional.

Se verificaron tres ejecuciones completas y
dos ensayos independientes del modo demo.

El acumulado de QA-14, QA-15 y QA-16 es de
77 escenarios Karate diferentes aprobados.

No se declara completada la totalidad de la
rubrica del curso ni se asigna una calificacion docente.
