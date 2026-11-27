# QA-13 — Verificación del entorno local

Fecha: 25/11/2026.
Responsable de ejecución: Luis Valenzuela.
Rama: feature/QA-13-entorno-local-reproducible.

## Alcance de esta ejecución

Infraestructura local de API, base de datos y frontend.
Sin despliegue público ni HTTPS público.

El cambio de alcance del issue y las referencias afectadas
debe confirmarse antes del cierre administrativo.

## Versiones

- Docker: 29.7.2.
- Docker Compose: 5.4.0.
- PHP: 8.3.37.
- Laravel: 12.69.2.
- Node.js: 22.23.5.
- npm: 10.9.9.

## Commits base

- Backend: 9efd9462123a2dc30c73113aa11aa523872e0f4d.
- Frontend: cb54dc9de80e2b6565b601931f925cf24445ce00.

Los cambios QA-13 deben identificarse mediante el commit y PR finales.

## Resultados observados

| Comprobación | Resultado |
| --- | --- |
| Docker Compose config --quiet | Sin errores |
| API y MySQL | Disponibles; MySQL saludable |
| GET /up | 200 |
| GET perfil sin token | 401 con JSON |
| CORS desde http://127.0.0.1:4201 | Allow-Origin exacto |
| CORS desde origen desconocido | Sin Allow-Origin |
| Sintaxis de configuración y seeders nuevos | Sin errores |
| Carga de catálogos y fixtures | Completada |
| Repetición de carga | Conteos estables |
| Seis fixtures | Roles y estados esperados |
| Respaldo SQL fuera del repositorio | Creado |
| Restauración en MySQL temporal aislado | Completada |
| Comparación de conteos representativos | Coincidente |
| Login mediante frontend QA | 200 |
| Consulta del perfil mediante frontend QA | 200 |
| Consulta del perfil después de F5 | 200 |

Las verificaciones fueron ejecutadas por el integrante.
Las respuestas HTTP y la restauración se registraron en la conversación.
No se presentan como capturas o archivos de log que no estén adjuntos.

## Datos reproducibles

QaEntornoSeeder carga catálogos y llama a QaSeeder sin AdminSeeder.

Se verificaron:
- 3 roles.
- 6 usuarios.
- 3 candidatos.
- 2 empresas.
- 7 áreas de estudio.
- 59 carreras.
- 5 estados de postulación.
- 153 habilidades.
- 6 idiomas.
- 22 instituciones.
- 3 motivos de rechazo.
- 14 organizaciones.
- 5 países.
- 20 puestos.
- 7 tipos de estudio.

La carga restablece atributos de fixtures y revoca sus tokens.
No elimina otros datos ni representa una restauración completa.

## Respaldo y recuperación comprobados

- Tamaño del respaldo: 78101 bytes.
- SHA256:
  BECFBC36884247B6FF7CAA4F4B0BD0B78DF48F3DF54FBB104555B1194FC782DA.
- Destino de prueba: base qa13_restore en un contenedor MySQL temporal.
- Sin puertos publicados ni acceso de red.
- Almacenamiento temporal y contenedor eliminados al finalizar.
- La base funcional no fue sobrescrita.

Conteos coincidentes:
31 tablas, 61 migraciones, 3 roles, 6 usuarios,
3 candidatos y 2 empresas.

Esta comprobación no acredita igualdad de todos los valores.
El SQL contiene datos y tokens y no debe incluirse en Git.

## Evidencias archivadas

- inventario.txt.
- seed-pasada-1.txt.
- seed-pasada-2.txt.
- repetibilidad.txt.

## Límites y pendientes

- Conectividad Android no evaluada en esta ejecución.
- La recarga web se verificó con ng serve.
- No se ejecutaron suites Karate, JMeter, Selenium o Appium.
- No se verificó instalación desde cero en otra máquina.
- Falta completar la guía reproducible de inicialización y recuperación.
- Falta confirmar actualización del issue y referencias del alcance.
- Pendientes revisión cruzada e integración del PR.

No se declara el cierre completo del issue con este reporte.
