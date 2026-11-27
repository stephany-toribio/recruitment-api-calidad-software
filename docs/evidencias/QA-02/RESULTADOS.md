
# Evidencias de ejecución — QA-02

## 1. Información general

- Issue: #12.
- Épica: E01.
- Fecha de ejecución: 24/11/2026.
- Commit base: 870c710.
- Rama: feature/QA-02-configuracion-entorno-qa.
- Entorno: WSL2 y Docker Compose.
- Tipo de ejecución: local.

## 2. Validación de infraestructura

| ID | Verificación | Resultado | Estado |
|---|---|---|---|
| INF-01 | Validación Docker Compose | Sin errores | PASS |
| INF-02 | Construcción de imagen PHP | Exitosa | PASS |
| INF-03 | Extensión pdo_mysql | Disponible | PASS |
| INF-04 | Extensión pdo_sqlite | Disponible | PASS |
| INF-05 | Instalación de dependencias | 115 paquetes | PASS |
| INF-06 | Requisitos Composer | Correctos | PASS |
| INF-07 | Generación APP_KEY | Exitosa | PASS |
| INF-08 | Disponibilidad Laravel | HTTP 200 | PASS |
| INF-09 | Disponibilidad MySQL | Healthy | PASS |
| INF-10 | Red independiente | Creada | PASS |
| INF-11 | Volumen independiente | Creado | PASS |
| INF-12 | Conexión MySQL aplicación | Base correcta | PASS |
| INF-13 | Conexión MySQL testing | Base correcta | PASS |
| INF-14 | Migraciones aplicación | Sin errores | PASS |
| INF-15 | Migraciones testing | Sin errores | PASS |

## 3. Línea base de pruebas automatizadas

| Suite | Total | Aprobadas | Fallidas | Aserciones | Duración |
|---|---:|---:|---:|---:|---:|
| Unit | 14 | 14 | 0 | 14 | 0.14 s |
| Feature | 205 | 205 | 0 | 1117 | 5.51 s |
| MySQL | 345 | 327 | 18 | 2586 | 37.328 s |
| Total | 564 | 546 | 18 | 3717 | 42.978 s |

Tasa de aprobación: 96.81%.

La ejecución individual de PostularVacanteMySqlTest obtuvo
66 pruebas aprobadas y 297 aserciones.

No se contabiliza nuevamente porque pertenece a la suite MySQL.

## 4. Validación de datos ficticios

| ID | Verificación | Resultado | Estado |
|---|---|---|---|
| FIX-01 | Creación de usuarios | 6 | PASS |
| FIX-02 | Creación de empresas | 2 | PASS |
| FIX-03 | Creación de candidatos | 3 | PASS |
| FIX-04 | Repetición del seeder | Sin duplicados | PASS |
| FIX-05 | Identificadores de usuarios | Conservados | PASS |
| FIX-06 | Verificación de contraseñas | 6 correctas | PASS |
| FIX-07 | Almacenamiento mediante hash | 6 correctas | PASS |
| FIX-08 | Seeder en base y ambiente no autorizados | Rechazado, exit 1 | PASS |
| FIX-09 | Cleanup en base incorrecta | Rechazado, exit 1 | PASS |
| FIX-10 | Primera limpieza | 6 cuentas eliminadas | PASS |
| FIX-11 | Segunda limpieza | 0 cuentas eliminadas | PASS |
| FIX-12 | Conservación de roles, países y habilidades | Sin pérdida | PASS |
| FIX-13 | Restauración de cuentas | Seeder exitoso | PASS |
| FIX-14 | Conservación de cuenta externa | Usuario de control conservado | PASS |
| FIX-15 | Recuento posterior a restauración | 6 usuarios, 2 empresas y 3 candidatos | PASS |
| FIX-16 | Rechazo de limpieza con dependencias | Limpieza rechazada y registros conservados | PASS |

### Limitaciones y verificaciones pendientes

- Investigación y corrección de los 18 fallos MySQL en QA-07.
- Reproducción independiente del entorno por el revisor.
- Validación de los flujos funcionales completos mediante las
  herramientas de automatización previstas en el proyecto.
- Validación del entorno desde dispositivos Android físicos.

## 5. Catálogos inicializados

| Catálogo | Registros |
|---|---:|
| Roles | 3 |
| Países | 5 |
| Áreas de estudio | 7 |
| Habilidades | 153 |
| Carreras | 59 |
| Niveles de puesto | 8 |
| Idiomas | 6 |
| Tipos de estudio | 7 |
| Instituciones | 22 |
| Organizaciones | 14 |
| Estados de postulación | 5 |
| Motivos de rechazo | 3 |
| Total | 292 |

## 6. Hallazgos funcionales

### QA02-H01 — Búsqueda de vacantes

- Estado: pendiente de investigación.
- Tarea relacionada: QA-07 (#15).
- Clase: BusquedaVacantesMySqlTest.
- Pruebas afectadas: 17.

Métodos afectados:

1. test_cuenta_postulaciones_reales_y_obtiene_el_rango:
   8 fallos.
2. test_recurso_publico_entrega_la_aproximacion:
   8 fallos.
3. test_http_no_expone_conteos_ni_datos_privados:
   1 fallo.

Resultados observados:

- Elementos esperados no encontrados.
- Respuesta HTTP con 2 elementos en lugar de 3.

Causa raíz pendiente de determinar.

### QA02-H02 — Contrato del detalle de vacantes

- Estado: pendiente de investigación.
- Tarea relacionada: QA-07 (#15).
- Clase: DetalleVacanteMySqlTest.
- Pruebas afectadas: 1.

Método:

test_recurso_entrega_solo_los_campos_publicos_acordados.

Resultado esperado: 20 campos públicos.

Resultado obtenido: 22 campos públicos.

Campos adicionales: requisitos y beneficios.

Se debe contrastar la respuesta con el contrato funcional vigente
antes de modificar la prueba o la implementación.

## 7. Incidencias técnicas resueltas

| Incidencia | Solución |
|---|---|
| Conflictos de puertos y contenedores | Configuración Compose independiente |
| Dubious ownership de Git | Configuración safe.directory específica |
| Error de declaración XML | Eliminación de espacios iniciales |
| Permisos del seeder | Corrección de propietario en WSL |
| Seeder vacío | Escritura y validación del código completo |
| Contraseña de fixtures ausente | Configuración local de la variable |

## 8. Conclusión

El entorno local de Calidad de Software dispone de una
infraestructura Docker independiente, bases de datos aisladas,
catálogos inicializados y fixtures reproducibles.

Se ejecutaron 564 pruebas automatizadas, de las cuales
546 fueron aprobadas y 18 presentaron fallos.

La tasa de aprobación inicial fue de 96.81%.

Los hallazgos identificados quedan documentados para su
investigación en QA-07.

El entorno se encuentra preparado para iniciar las actividades
de desarrollo y automatización, sujeto a la revisión cruzada
y aprobación del Pull Request asociado a QA-02.