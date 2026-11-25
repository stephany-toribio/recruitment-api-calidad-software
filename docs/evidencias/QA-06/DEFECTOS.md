# Registro de hallazgos - QA-06

## QA06-T01 - Preparacion insuficiente de catalogos

Tipo: defecto de infraestructura de pruebas.

Resultado inicial:
- 35 pruebas ejecutadas.
- 11 aprobadas.
- 24 fallidas.
- 244 aserciones.
- 21 fallos por ausencia del tipo de estudio.
- 3 fallos HTTP 404 durante registro empresarial.

La ausencia de roles es una hipotesis para los HTTP 404.

Correccion propuesta:
Preparar roles y catalogos dentro de la transaccion
aislada de cada prueba.

Evidencia inicial:
backend/registro-inicial.txt

Retest 01:
- 35 pruebas aprobadas.
- 545 aserciones.
- Cero fallos.
- Duracion: 12.265 segundos.

Evidencia:
backend/registro-retest-01.txt

Suite ampliada de registro y perfiles:
- 48 pruebas aprobadas.
- 757 aserciones.
- Cero fallos.
- Duracion: 18.545 segundos.

Evidencia:
backend/registro-perfiles.txt

Estado: RESUELTO.

La preparacion de catalogos soluciono los fallos
sin modificar las reglas funcionales del backend.

## QA06-F01 - Ruta antigua de perfil

El componente creo-edit-profile utiliza DEMO_PROFILE
y sigue registrado en app.routes.ts.

Estado: confirmado por inspeccion.

## QA06-F02 - Guardado simulado

CreoEditProfileComponent muestra confirmacion
mediante setTimeout sin persistencia en la API.

Estado: confirmado por inspeccion.

## QA06-F03 - Persistencia de datos personales

El borrador de registro utiliza localStorage.

No almacena contrasenas, pero conserva datos
personales y academicos.

Estado: confirmado por inspeccion.

## QA06-F04 - Restauracion de paso sin credenciales

El borrador restaura pasos avanzados sin restaurar
la contrasena.

Estado: riesgo funcional identificado por inspeccion;
reproduccion pendiente.

## QA06-F05 - Recreacion del borrador

Verificar el ciclo de vida del componente tras
un registro exitoso.

Estado: pendiente de confirmacion.

## QA06-F06 - Mensajes de validacion

Verificar la correspondencia entre errores de
registro y actualizacion de perfiles.

Estado: pendiente de confirmacion.

---

## Resultados consolidados de los hallazgos frontend

| Hallazgo | Correccion o verificacion | Evidencia | Estado |
| --- | --- | --- | --- |
| QA06-F01 | Redireccion al perfil funcional protegido | F29 | Retest estatico PASS |
| QA06-F02 | Pantalla simulada retirada del flujo navegable | F29 | Retest estatico PASS |
| QA06-F03 | Borrador limitado a sessionStorage | F03, F04, F05 | PASS auxiliar |
| QA06-F04 | Restauracion desde primer paso | F06 | PASS auxiliar |
| QA06-F05 | Guarda contra recreacion del borrador | F07 | PASS auxiliar |
| QA06-F06 | Mapper de mensajes de duplicidad | F23-A, F23-B, F23-C | Verificado |
| QA06-F07 | Pantalla y servicio de perfil empresarial | F10-F22, F25-F28, F30 | PASS auxiliar |
| QA06-F08 | Bloqueo de navegacion del candidato | F08, F33-F36 | PASS auxiliar |
| QA06-F09 | Evitar nuevo registro tras login fallido | F37 | PASS auxiliar |
| QA06-F10 | Botones y guardas de procesamiento | F31, F39-F42 y builds | PASS parcial |
| QA06-F11 | Retorno al paso con errores de registro | F32, F38 | PASS auxiliar |

## Verificaciones adicionales

- Bloqueo de modales durante guardado: F24.
- Restricciones UNIQUE de MySQL: PASS.
- Regresion frontend: 75/75.
- Compilaciones Angular: 3/3.

## Riesgos residuales

Los retests auxiliares y estaticos no equivalen a
pruebas end-to-end en navegador.

La concurrencia HTTP real no ha sido ejecutada.

Las verificaciones visuales y de accesibilidad se
completaran en las tareas web y movil correspondientes.
