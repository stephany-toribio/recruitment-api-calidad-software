# Resultados de QA-06 - Registro y perfiles

## 1. Identificacion

- Tarea: QA-06.
- Epica: E02 - Flujo de negocio estable.
- Hito: M1.
- Responsable: stephany-toribio.
- Fecha: 2026-11-25.
- Rama: feature/QA-06-registro-perfiles.
- Ambiente: WSL2, Docker, Laravel y MySQL.
- PHP: 8.3.37.
- PHPUnit: 11.5.56.
- Base de integracion: portalempleoshu04testing.

Issue:
https://github.com/stephany-toribio/web-app-calidad-software/issues/4

## 2. Objetivo

Verificar el registro y la gestion de perfiles de candidatos
y empresas, incluyendo integridad, validaciones, persistencia,
autorizacion y restricciones de unicidad.

## 3. Resultados

| Suite | Pruebas | Aserciones | Fallos |
| --- | ---: | ---: | ---: |
| Unit/Feature | 259 | 1645 | 0 |
| MySQL | 433 | 3917 | 0 |
| Total | 692 | 5562 | 0 |

QA-06 incorpora 49 pruebas de integracion MySQL.

La regresion general incluye las pruebas de QA-06.
No deben sumarse nuevamente al total.

## 4. Evolucion de la suite

| Ejecucion | Pruebas | Aprobadas | Fallidas | Aserciones |
| --- | ---: | ---: | ---: | ---: |
| Inicial | 35 | 11 | 24 | 244 |
| Retest de catalogos | 35 | 35 | 0 | 545 |
| Registro y perfiles | 48 | 48 | 0 | 757 |
| Restricciones UNIQUE | 1 | 1 | 0 | 12 |

Los 24 fallos iniciales se solucionaron mediante
la preparacion reproducible de roles y catalogos.

No fue necesario modificar las reglas funcionales de Laravel.

## 5. Cobertura

### Registro de candidato

- Registro valido y persistencia.
- Correo, DNI y celular duplicados.
- Campos obligatorios y formatos invalidos.
- Edad minima.
- Seguridad y confirmacion de contrasena.
- Estudios, idiomas y habilidades.
- Experiencia requerida.
- Catalogos inexistentes.
- Limites de habilidades.
- Ausencia de registros parciales ante rechazo.

### Registro de empresa

- Registro valido y persistencia.
- Correo y RUC duplicados.
- RUC invalido.
- Sector fuera del catalogo.
- URL invalida.
- Campos obligatorios.
- Longitud de descripcion.
- Seguridad de contrasena.

### Perfiles y autorizacion

- Consulta del perfil propio.
- Actualizacion y consulta posterior.
- Conservacion de relaciones del candidato.
- Rechazo de identificadores duplicados.
- Proteccion frente a identificadores ajenos.
- Rechazo de accesos sin autenticacion.
- Rechazo de roles incompatibles.
- Prevencion de elevacion de privilegios.

### Integridad y privacidad

- Contrasenas almacenadas mediante hash.
- Ausencia de contrasenas en los recursos evaluados.
- Precognition sin persistencia.
- Restricciones UNIQUE en correo, DNI, celular y RUC.

## 6. Datos de prueba

Se utilizan datos sinteticos con correos example.test.

Los roles y catalogos se preparan dentro de transacciones
aisladas que se revierten al finalizar cada prueba.

La suite verifica el ambiente, el motor, el host y la base
efectiva antes de realizar escrituras.

## 7. Trazabilidad

Familias principales: CP-01, CP-02 y CP-06.

Se reutiliza la evidencia de QA-05 para los escenarios de
consulta no autorizada e inyeccion de identificadores
del candidato.

Las pruebas PHPUnit no equivalen a ejecuciones Karate.

## 8. Evidencias

- backend/registro-inicial.txt
- backend/registro-retest-01.txt
- backend/registro-perfiles.txt
- backend/restricciones-unicidad.txt
- backend/regresion-completa/unit-feature.txt
- backend/regresion-completa/mysql.txt
- backend/final/unit-feature.txt
- backend/final/mysql.txt
- DEFECTOS.md
- SHA256SUMS.txt

## 9. Limitaciones y riesgos residuales

- Las restricciones UNIQUE no demuestran por si solas
  la respuesta HTTP ante solicitudes concurrentes.
- La validacion de concurrencia real queda pendiente
  de la estrategia de rendimiento.
- La automatizacion Karate corresponde a QA-14,
  QA-15, QA-16 y QA-17 segun su alcance.
- La validacion visual y end-to-end corresponde
  a las tareas Selenium y Appium.
- Los resultados no acreditan todavia el cumplimiento
  completo de la rubrica academica.

## 10. Conclusion

Las pruebas de integracion de QA-06 y la regresion general
del backend finalizaron satisfactoriamente.

La integracion con develop y el cierre del issue requieren
la publicacion y fusion de los PR correspondientes.
