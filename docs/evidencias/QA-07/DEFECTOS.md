# Registro de defectos - QA-07

## Informacion general

- Proyecto: Portal de Empleos CREO+.
- Issue: #15.
- Epica: E02.
- Rama: feature/QA-07-vacantes-disponibilidad.
- Commit base: 813a4f9.
- Ambiente: WSL2, Docker, MySQL 8.
- PHP: 8.3.37.
- PHPUnit: 11.5.56.

## QA02-H01 - Fixtures de busqueda incompatibles con disponibilidad

### Trazabilidad

- Requisito: RF-06.
- Familias: CP-10, CP-11.
- Riesgo: RSK-08.
- Componente: BusquedaVacantesMySqlTest.
- Tipo: defecto de datos de prueba.

### Resultado observado

17 pruebas fallidas.

Las vacantes esperadas no aparecian en el listado.
Una prueba HTTP recibia dos registros en lugar de tres.

### Causa raiz

Las vacantes de prueba se creaban con dos puestos.

El generador de postulaciones distribuia registros entre
cinco estados, incluyendo Contratado.

Al alcanzar dos contratados, la vacante dejaba de cumplir
la condicion de disponibilidad exigida por el negocio.

### Correccion

Ajustar exclusivamente los cupos de los tres metodos
de prueba afectados.

Conservar las aserciones de conteo, rangos, paginacion
y privacidad.

No modificar DisponibilidadVacanteService.

### Retest

- Antes: 73/90 pruebas aprobadas.
- Despues: 90/90 pruebas aprobadas.
- Aserciones finales: 920.
- Duracion del retest: 15.748 segundos.
- Resultado: PASS.

Evidencias:

- backend/antes/BusquedaVacantesMySqlTest.txt
- backend/retest-01/BusquedaVacantesMySqlTest.txt

Estado: corregido y retest aprobado en las suites seleccionadas.

Jira: pendiente de vinculacion real mediante QA-29.

---

## QA02-H02 - Contrato de prueba de detalle desactualizado

### Trazabilidad

- Requisito: RF-06.
- Familia: CP-11.
- Riesgo: RSK-08.
- Componente: DetalleVacanteMySqlTest.
- Tipo: discrepancia contractual de prueba.

### Resultado observado

La prueba esperaba exactamente veinte campos publicos.

El recurso devolvia veintidos campos.

Campos adicionales:

- requisitos.
- beneficios.

### Causa raiz

La expectativa historica de PHPUnit no contemplaba
dos campos presentes en el contrato actual del backend
y consumidos por los componentes Angular.

### Correccion

Actualizar la lista explicita de campos permitidos.

Conservar la comparacion estricta de claves y las
restricciones sobre datos privados.

Incorporar comprobaciones de arrays vacios.

Agregar dos regresiones para verificar valores
registrados y su exposicion mediante HTTP.

### Retest

- Antes: 32/33 pruebas aprobadas.
- Despues: 35/35 pruebas aprobadas.
- Nuevas pruebas: 2.
- Aserciones finales: 181.
- Duracion del retest: 3.170 segundos.
- Resultado: PASS.

Evidencias:

- backend/antes/DetalleVacanteMySqlTest.txt
- backend/retest-01/DetalleVacanteMySqlTest.txt

Estado: corregido y retest aprobado en las suites seleccionadas.

Jira: pendiente de vinculacion real mediante QA-29.

---

## QA07-F01 - Nulabilidad de ubicacion en frontend

### Trazabilidad

- Requisito: RF-06.
- Familia: CP-11.
- Componente: vacante-detalle.model.ts.
- Tipo: posible discrepancia de contrato TypeScript.

### Hallazgo

El modelo Angular declara ubicacion como string.

El backend admite ubicacion nula para vacantes remotas.

### Seguimiento

Verificar consumidores y comportamiento visual.

Ajustar el contrato TypeScript si corresponde.

Estado: pendiente de verificacion funcional.

---

## QA07-B01 - Validacion Luhn no implementada

### Trazabilidad

- Caso canonico: CP-09-03.
- Componente: PagarVacanteRequest.php.
- Tipo: defecto funcional de validacion.
- Severidad: media.
- Prioridad propuesta: P1.

### Resultado esperado

Un numero de tarjeta ficticio que no cumple Luhn
debe recibir HTTP 422 sin registrar un pago.

### Resultado observado

HTTP 201 en lugar de HTTP 422.

### Causa raiz

El metodo cumpleLuhn retornaba true incondicionalmente.

### Correccion

Implementacion del algoritmo de Luhn para validar
el digito de control de numeros de 16 digitos.

No se modifica el caracter simulado del pago.

### Evidencia RED

- Pruebas: 1.
- Aserciones: 10.
- Fallos: 1.
- Duracion: 0.523 segundos.
- Resultado: defecto reproducido.

### Evidencia GREEN

- Pruebas: 1.
- Aserciones: 13.
- Fallos: 0.
- Duracion: 0.405 segundos.
- Resultado: PASS.

### Regresion complementaria

- Moderacion y pago: 7/7 PASS, 106 aserciones.
- Registro de vacantes: 43/43 PASS, 623 aserciones.

Evidencias:

- backend/red/QA07-B01-luhn.txt
- backend/green/QA07-B01-luhn.txt
- backend/green/moderacion-pago.txt
- backend/green/registro-vacantes.txt

Estado: corregido y retest aprobado.

Jira: pendiente de vinculacion real mediante QA-29.
