# CREO+ - Automatizacion API con Karate

## 1. Objetivo

Automatizar pruebas funcionales, contratos JSON,
autenticacion, autorizacion, flujos de negocio
y conflictos de la API Laravel de CREO+.

Issue inicial: #18 - QA-14.

## 2. Requisitos

- Java 21.
- Maven 3.9.
- Docker y Docker Compose.
- Python 3.
- Bash.
- API local CREO+ operativa.

Versiones verificadas:

| Herramienta | Version |
| --- | --- |
| Java | 21.0.13.0.3 |
| Maven | 3.9.12 |
| Karate | 1.5.2 |
| JUnit Jupiter | 5.11.4 |
| Maven Surefire | 3.5.2 |
| Maven Compiler | 3.13.0 |

## 3. Estructura

- pom.xml: dependencias y compilacion.
- src/test/java/creo/: runners y controles Java.
- src/test/resources/karate-config.js: ambientes.
- src/test/resources/features/: escenarios funcionales.
- src/test/resources/helpers/: features reutilizables.
- src/test/resources/schemas/: contratos JSON.
- src/test/resources/fixtures/: catalogos sinteticos.
- scripts/: preparacion, ejecucion y limpieza.

## 4. Ambiente local

Ejecutar desde la raiz del repositorio:

    docker compose ps
    curl -fsS http://127.0.0.1:8002/up

Configuracion:

- Base URL: http://127.0.0.1:8002
- API: /api/v1
- Base MySQL: portal_empleos_calidad_qa

No utilizar las bases del proyecto original.

## 5. Smoke sin autenticacion

    mvn -B -ntp -f qa/karate/pom.xml \
      -Dkarate.env=local -Dtest=SmokeTest,SensitiveLogModifierTest clean test

Ejecuta tres escenarios Karate y seis pruebas
unitarias Java del modificador de logs.

## 6. Smoke con autenticacion y fixture

    qa/karate/scripts/run-local.sh

El script:

1. Genera un UUID y una password temporal.
2. Verifica el ambiente local autorizado.
3. Crea un usuario sintetico independiente.
4. Ejecuta Maven y Karate.
5. Comprueba los resultados y reportes.
6. Escanea posibles secretos.
7. Elimina el usuario temporal y sus tokens.
8. Conserva un resumen publicable.

Resultado esperado:

- Cuatro escenarios Karate aprobados.
- Maven exit code 0.
- Escaneo de secretos PASS.
- Fixture temporal eliminado.

## 7. Ambiente QA remoto

La configuracion reconoce karate.env=qa.

Requiere QA_BASE_URL con HTTPS.

Su ejecucion y autorizacion dependen del
entorno desplegado mediante QA-13.

El administrador de fixtures PHP solamente
permite la base MySQL local autorizada.

No ejecutar fixtures locales contra servidores
remotos ni entornos productivos.

## 8. Reutilizacion

Helpers disponibles:

- health.feature.
- login.feature.
- logout.feature.

Se invocan mediante call.

Los tokens e identificadores se obtienen
dinamicamente durante la ejecucion.

No pasar contrasenas ni tokens como argumentos
explicitos de call.

## 9. Seguridad

- No versionar archivos .env.
- No incluir contrasenas fijas.
- Enmascarar cabeceras sensibles.
- Ocultar cuerpos HTTP en logs.
- Deshabilitar impresiones innecesarias.
- No publicar reportes sin revision.
- No publicar reportes de ejecuciones fallidas
  que puedan contener secretos.

La proteccion de logs no garantiza por si sola
la ausencia de secretos en todas las aserciones
o estructuras internas de Karate.

Los reportes completos de autenticacion deben
mantenerse privados hasta ser revisados.

## 10. Reportes

Directorio generado por Karate:

    qa/karate/target/karate-reports/

Reporte principal:

    karate-summary.html

El directorio target esta excluido de Git.

Las evidencias seleccionadas y revisadas se
conservan en docs/evidencias/QA-14/.

## 11. Fallos de ejecucion

Si un escenario falla, Maven debe devolver
un codigo de salida distinto de cero.

El runner tambien exige que se ejecute al
menos un escenario.

Se verifico una asercion deliberadamente
incorrecta, seguida de una recuperacion GREEN.

## 12. Limpieza

El ejecutor realiza limpieza mediante trap EXIT.

La limpieza se limita al UUID de la ejecucion.

No se eliminan las seis cuentas QA compartidas.

Ante una interrupcion forzada que impida
ejecutar el trap, revisar los fixtures residuales
antes de iniciar otra ejecucion.

## 13. Siguientes etapas

- QA-15: registro, autenticacion y sesiones.
- QA-16: vacantes, postulaciones y decisiones.
- QA-17: autorizacion, conflictos y errores.
- QA-27: integracion continua de API.
- QA-29: trazabilidad Jira.

QA-14 prepara la infraestructura y no acredita
por si sola todos los criterios de la rubrica.

---

## QA-15: Registro y autenticacion

### Prerrequisitos

- Java 21 y Maven disponibles.
- Docker Compose operativo.
- API QA accesible en http://127.0.0.1:8002.
- MySQL QA inicializado con los catalogos necesarios.
- Python 3 disponible.
- Ejecutar desde la raiz del repositorio.

### Comprobar disponibilidad

    docker compose ps

    curl -fsS http://127.0.0.1:8002/up

### Ejecutar suite completa

    qa/karate/scripts/run-qa15.sh full

Cobertura esperada: 39 escenarios.

### Ejecutar demostracion en vivo

    qa/karate/scripts/run-qa15.sh demo

Cobertura esperada: 8 escenarios representativos.

El modo demo utiliza los mismos features de la suite
completa. No modifica las reglas de negocio.

### Ejecutar QA-14 independientemente

    qa/karate/scripts/run-local.sh

El runner de QA-14 se ejecuta de manera independiente
y escribe sus nuevos resultados fuera de las evidencias
historicas por defecto.

### Consultar los reportes

Cada ejecucion QA-15 informa al finalizar el directorio
privado que contiene los reportes.

Una vez confirmado el escaneo de secretos, abrir:

    karate-reports/karate-summary.html

La carpeta generada por Maven tambien se encuentra en:

    qa/karate/target/karate-reports/

Una ejecucion posterior puede reemplazar el contenido
de target. Los reportes seleccionados se conservan en
docs/evidencias/QA-15/reportes/.

### Aislamiento y limpieza

El ejecutor genera un UUID y una password temporal
independientes. No requiere introducir credenciales
manualmente.

Los registros funcionales se crean mediante la API.

El puente Java-PHP permite comprobar persistencia y
preparar estados exclusivos de los fixtures QA.

El fixture de administrador se crea directamente
porque no existe registro publico para ese rol.

La expiracion del bloqueo se simula exclusivamente
despues de comprobar el bloqueo real.

No ejecutar contra bases de datos productivas.

### Evidencias y trazabilidad

- docs/evidencias/QA-15/RESULTADOS.md
- docs/evidencias/QA-15/INCIDENCIAS.md
- docs/evidencias/QA-15/reportes/
- docs/qa/MATRIZ_TRAZABILIDAD.md
- docs/qa/MATRIZ_RUBRICA.md

---

## QA-16: Flujo funcional de negocio

Issue GitHub: #20.

### Suite completa

    unset QA16_TAGS
    qa/karate/scripts/run-qa16.sh full

Resultado obligatorio: 34 escenarios aprobados.

### Demostracion en vivo

    unset QA16_TAGS
    qa/karate/scripts/run-qa16.sh demo

Resultado obligatorio: cuatro escenarios aprobados.

Escenarios seleccionados:

- CP-07-02: rango salarial invalido.
- CP-09-05: pago duplicado.
- CP-12-02: candidato suspendido.
- QA16-E2E-01: flujo integral de negocio.

### Diagnostico por feature

    QA16_TAGS="@postulaciones" \
      qa/karate/scripts/run-qa16.sh full

    QA16_TAGS="@contratacion,@rechazo" \
      qa/karate/scripts/run-qa16.sh full

Los filtros sirven para desarrollo y diagnostico.
No equivalen a una regresion completa.

### Preparacion

- Docker Compose operativo.
- API local disponible en el puerto 8002.
- MySQL QA con catalogos inicializados.
- Java 21 y Maven 3.9.
- Python 3.
- No ejecutar suites simultaneamente sobre
  el mismo directorio target de Maven.

### Fixtures de negocio

Cada ejecucion genera un UUID y una contraseña
temporal.

Los registros, vacantes, revisiones, pagos y
postulaciones se crean mediante operaciones HTTP.

Las consultas PHP verifican persistencia y
preparan estados exclusivos del entorno QA.

La limpieza elimina las entidades de la ejecucion
en orden de dependencias y dentro de una transaccion.

No pasar tokens o contraseñas como argumentos
explicitos de call.

### Resultados

- 26 casos canonicos.
- 34 escenarios Karate.
- Tres regresiones completas aprobadas.
- Dos ensayos demo aprobados.
- Seis incidencias corregidas.

### Reportes

El runner informa el directorio privado de reportes.

Una vez aprobado el escaneo de secretos, consultar:

    karate-reports/karate-summary.html

Las evidencias revisadas se encuentran en:

    docs/evidencias/QA-16/

QA-17 completa la cobertura de autorizacion,
conflictos y restricciones adicionales.

<!-- QA17-CIERRE -->

## QA-17 - Autorización y conflictos

Suite completa: 32 escenarios.

    unset QA17_TAGS
    qa/karate/scripts/run-qa17.sh full

Demostración: cinco escenarios.

    unset QA17_TAGS
    qa/karate/scripts/run-qa17.sh demo

Diagnóstico:

    QA17_TAGS="@disponibilidad" \
      qa/karate/scripts/run-qa17.sh full

Evidencias: docs/evidencias/QA-17/.

Total Karate QA-14 a QA-17: 109 escenarios.

Ejecutar los runners secuencialmente.
