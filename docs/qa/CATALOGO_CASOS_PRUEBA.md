# Catálogo Detallado de Casos de Prueba — QA-04

- **Proyecto**: Portal de Empleos CREO+ (PWA y API REST) — Calidad de Software.
- **Responsable**: R3 (@luisvalenzuela25).
- **Revisor asignado**: R1 (@stephany-toribio).
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.2.0 (Revisión técnica de consistencia).
- **Fecha**: 24/11/2026.
- **Rama**: `feature/QA-04-matriz-pruebas`.

---

## 1. Convenciones y estados de prueba

Cada caso de prueba se desglosa desde las 34 familias base (`CP-01` a `CP-34`) utilizando **102 identificadores canónicos de escenario** (`CP-XX-YY`), correspondientes exactamente a 50 escenarios positivos y 52 negativos.
- **Estados posibles de ciclo de vida**:
  - `PENDIENTE`: Diseñado y documentado, a la espera de ejecución en su suite automatizada correspondiente.
  - `EJECUTADO`: Prueba corrida en el ambiente local/QA pero con resultado aún no consolidado.
  - `APROBADO`: Ejecutada exitosamente con aserciones cumplidas y evidencia registrada.
  - `DEFECTUOSO`: Ejecutada con fallos o aserciones no cumplidas (se vincula a un defecto formal Sev1-Sev4).
- **Estado inicial**: **Todos los 102 escenarios inician en estado `PENDIENTE`** dentro de esta entrega de diseño de QA-04.
- **Manejo de identificadores**: Los scripts no deben quemar IDs numéricos de usuarios ni de entidades de negocio (se resuelven dinámicamente mediante login o consultas previas). Para catálogos inmutables del sistema, los IDs corresponden a las constantes oficiales sembradas por los seeders base (`EstadoPostulacionSeeder`: 1=Recibido, 2=En Revisión, 3=Entrevista, 4=Contratado, 5=Rechazado; `MotivoRechazoSeeder`: 1=Perfil no se ajusta, 2=Expectativa salarial alta, 3=Proceso cerrado).

---

## 2. Bloque 1: Registro y Autenticación (RF-01, RF-02, RNF-01)

### CP-01: Registro de Candidato
- **CP-01-01 (Positivo - Alta válida)**
  - **Objetivo**: Verificar el alta exitosa de un candidato con datos válidos y únicos.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: Base de datos de QA inicializada; correo y DNI no registrados previamente.
  - **Datos**: Correo `candidato.nuevo@qa.example.test`, DNI `00000099`, celular `900000099`, contraseña `QA_FIXTURE_PASSWORD`, nombre `Candidato`, apellidos `Nuevo QA`.
  - **Pasos**: Enviar `POST /api/v1/candidatos` con payload completo.
  - **Resultado esperado**: HTTP 201 Created; objeto candidato con `id` asignado y mensaje de éxito.
  - **Comprobación de persistencia**: Fila en tabla `usuarios` (`rol_id` de candidato) y tabla `candidatos` con DNI correspondiente; contraseña hasheada en DB.
  - **Dependencias**: QA-02, QA-06, QA-15. | **Estado inicial**: `PENDIENTE`.

- **CP-01-02 (Negativo - Correo o DNI Duplicado)**
  - **Objetivo**: Impedir el registro cuando el correo o DNI ya existen en el sistema.
  - **Tipo**: Funcional / Negativa (Karate).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: `USR-CND-01` (`candidato1@qa.example.test`, DNI `00000001`) ya registrado.
  - **Datos**: Body con correo `candidato1@qa.example.test` o DNI `00000001`.
  - **Pasos**: Enviar `POST /api/v1/candidatos`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity; error de validación en campo `correo` o `dni`.
  - **Comprobación de persistencia**: El recuento de filas en `usuarios` y `candidatos` no sufre cambios; sin altas parciales.
  - **Dependencias**: QA-02, QA-15. | **Estado inicial**: `PENDIENTE`.

- **CP-01-03 (Negativo - Formato Inválido)**
  - **Objetivo**: Validar el rechazo de DNI alfanumérico, celular corto o correo malformado.
  - **Tipo**: Validación / Negativa (Karate, Selenium).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: Ninguna.
  - **Datos**: DNI `ABC12345`, celular `123`, correo `invalido@`.
  - **Pasos**: Enviar `POST /api/v1/candidatos`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity; mensajes de error contractuales por campo.
  - **Comprobación de persistencia**: Sin nuevos registros en base de datos.
  - **Dependencias**: QA-06, QA-15. | **Estado inicial**: `PENDIENTE`.

---

### CP-02: Registro de Empresa
- **CP-02-01 (Positivo - Alta válida)**
  - **Objetivo**: Verificar el alta exitosa de una empresa con RUC válido y razón social.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: RUC `20000000099` y correo no registrados previamente.
  - **Datos**: `empresa.nueva@qa.example.test`, RUC `20000000099`, Razón Social `Empresa Nueva QA SAC`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**: Enviar `POST /api/v1/empresas` con payload completo.
  - **Resultado esperado**: HTTP 201 Created; objeto empresa devuelto con ID y rol asignado.
  - **Comprobación de persistencia**: Fila en `usuarios` y fila en `empresas` vinculada por `usuario_id`.
  - **Dependencias**: QA-02, QA-06, QA-15. | **Estado inicial**: `PENDIENTE`.

- **CP-02-02 (Negativo - RUC Duplicado)**
  - **Objetivo**: Rechazar duplicidad de RUC en la base de datos.
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: `USR-EMP-01` (`empresa1@qa.example.test`, RUC `20000000001`) existente.
  - **Datos**: RUC `20000000001` con correo diferente `otra@qa.example.test`.
  - **Pasos**: Enviar `POST /api/v1/empresas`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity; error en atributo `ruc`.
  - **Comprobación de persistencia**: Ninguna fila creada.
  - **Dependencias**: QA-15. | **Estado inicial**: `PENDIENTE`.

- **CP-02-03 (Negativo - RUC Inválido)**
  - **Objetivo**: Rechazar RUC que no cumpla con longitud de 11 dígitos numéricos.
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: Visitante anónimo.
  - **Datos**: RUC `12345` o `2000000000A`.
  - **Pasos**: Enviar `POST /api/v1/empresas`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: Sin cambios en BD.
  - **Dependencias**: QA-15. | **Estado inicial**: `PENDIENTE`.

---

### CP-03: Inicio de Sesión de los Tres Roles
- **CP-03-01 (Positivo - Candidato)**
  - **Objetivo**: Autenticar candidato y obtener token Bearer Sanctum.
  - **Tipo**: Funcional / Positiva (Karate, Selenium, Appium).
  - **Actor**: Candidato activo (`USR-CND-01`).
  - **Precondiciones**: Cuenta activa preparada en `QaSeeder`.
  - **Datos**: `candidato1@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**: `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 200 OK; token devuelto; rol devuelto `candidato`.
  - **Comprobación de persistencia**: Fila generada en `personal_access_tokens`.
  - **Dependencias**: QA-05, QA-15, QA-19, QA-22. | **Estado inicial**: `PENDIENTE`.

- **CP-03-02 (Positivo - Empresa)**
  - **Objetivo**: Autenticar empresa y verificar token y rol corporativo.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: Empresa activa (`USR-EMP-01`).
  - **Precondiciones**: Cuenta empresa activa en `QaSeeder`.
  - **Datos**: `empresa1@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**: `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 200 OK; rol devuelto `empresa`.
  - **Comprobación de persistencia**: Token en DB; redirección UI autorizada.
  - **Dependencias**: QA-05, QA-15, QA-20. | **Estado inicial**: `PENDIENTE`.

- **CP-03-03 (Positivo - Administrador)**
  - **Objetivo**: Autenticar administrador para moderación.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: Administrador (`USR-ADM-01`).
  - **Precondiciones**: Cuenta admin activa en `QaSeeder`.
  - **Datos**: `admin@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**: `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 200 OK; rol devuelto `admin`.
  - **Comprobación de persistencia**: Token en `personal_access_tokens`.
  - **Dependencias**: QA-05, QA-15, QA-20. | **Estado inicial**: `PENDIENTE`.

---

### CP-04: Credenciales Incorrectas, Bloqueo y Suspensión
- **CP-04-01 (Negativo - Password Errónea)**
  - **Objetivo**: Rechazar login ante contraseña errónea e incrementar contador de fallos.
  - **Tipo**: Seguridad / Negativa (Karate, Selenium).
  - **Actor**: Candidato (`USR-CND-01`).
  - **Precondiciones**: `USR-CND-01` activo con `intentos_fallidos = 0`.
  - **Datos**: `candidato1@qa.example.test`, contraseña `PasswordIncorrecta123!`.
  - **Pasos**: `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 401 Unauthorized o 422 según contrato.
  - **Comprobación de persistencia**: Campo `intentos_fallidos` se incrementa en tabla `usuarios`.
  - **Dependencias**: QA-05, QA-15. | **Estado inicial**: `PENDIENTE`.

- **CP-04-02 (Negativo - Cuenta Bloqueada Temporalmente)**
  - **Objetivo**: Rechazar login cuando se supera el límite de intentos y la cuenta queda bloqueada.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: Candidato con bloqueo temporal.
  - **Precondiciones**: Usuario con `bloqueado_hasta > NOW()`.
  - **Datos**: Credenciales correctas de usuario bloqueado.
  - **Pasos**: `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 403 Forbidden o 423 Locked; mensaje contractual indicando bloqueo temporal.
  - **Comprobación de persistencia**: Sin cambios en BD; no se emite token.
  - **Dependencias**: QA-05, QA-15. | **Estado inicial**: `PENDIENTE`.

- **CP-04-03 (Negativo - Cuenta Suspendida Administrativamente)**
  - **Objetivo**: Rechazar acceso a usuario con estado `suspendido`.
  - **Tipo**: Seguridad / Negativa (Karate, Selenium).
  - **Actor**: Candidato suspendido (`USR-CND-03`).
  - **Precondiciones**: Usuario con `estado_cuenta = 'suspendido'` en `QaSeeder`.
  - **Datos**: `suspendido@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**: `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 403 Forbidden; mensaje contractual indicando cuenta suspendida.
  - **Comprobación de persistencia**: No se genera token en BD.
  - **Dependencias**: QA-05, QA-15. | **Estado inicial**: `PENDIENTE`.

---

### CP-05: Cierre de Sesión y Revocación de Tokens
- **CP-05-01 (Positivo - Logout)**
  - **Objetivo**: Cerrar sesión válidamente y revocar el token activo en el backend.
  - **Tipo**: Funcional / Positiva (Karate, Selenium, Appium).
  - **Actor**: Candidato autenticado (`USR-CND-01`).
  - **Precondiciones**: Token Bearer activo.
  - **Pasos**: `POST /api/v1/auth/logout` con header `Authorization: Bearer <token>`.
  - **Resultado esperado**: HTTP 200 OK; mensaje de logout exitoso.
  - **Comprobación de persistencia**: El registro del token es eliminado de `personal_access_tokens`.
  - **Dependencias**: QA-05, QA-15, QA-23. | **Estado inicial**: `PENDIENTE`.

- **CP-05-02 (Negativo - Token Revocado)**
  - **Objetivo**: Impedir consumo de rutas protegidas tras haber hecho logout.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: Candidato deslogueado.
  - **Precondiciones**: Token utilizado previamente en CP-05-01.
  - **Pasos**: `GET /api/v1/auth/me` con el token revocado.
  - **Resultado esperado**: HTTP 401 Unauthorized.
  - **Comprobación de persistencia**: Sin cambios en BD.
  - **Dependencias**: QA-05, QA-15. | **Estado inicial**: `PENDIENTE`.

- **CP-05-03 (Negativo - Token Malformado)**
  - **Objetivo**: Impedir acceso con cabecera de autenticación corrupta o incompleta.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: Visitante anónimo.
  - **Pasos**: `GET /api/v1/auth/me` con header `Authorization: Bearer token_falso_invalido`.
  - **Resultado esperado**: HTTP 401 Unauthorized.
  - **Comprobación de persistencia**: Sin mutaciones.
  - **Dependencias**: QA-05, QA-15. | **Estado inicial**: `PENDIENTE`.

---

## 3. Bloque 2: Perfiles, Vacantes, Moderación y Pagos (RF-03, RF-04, RF-05, RNF-01)

### CP-06: Perfil Propio y Control de Pertenencia
- **CP-06-01 (Positivo - Consulta de Perfil Propio)**
  - **Objetivo**: Obtener datos del perfil propio autenticado.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-CND-01`.
  - **Pasos**: `GET /api/v1/candidatos/me`.
  - **Resultado esperado**: HTTP 200 OK; devuelve nombre, apellidos, DNI y celular propios.
  - **Comprobación de persistencia**: Consistente con registro en tabla `candidatos`.
  - **Dependencias**: QA-05, QA-06. | **Estado inicial**: `PENDIENTE`.

- **CP-06-02 (Positivo - Edición de Perfil Propio)**
  - **Objetivo**: Actualizar celular del candidato autenticado.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-CND-01`.
  - **Datos**: Celular nuevo `900000888`.
  - **Pasos**: `PUT /api/v1/candidatos/me` con payload `{ "celular": "900000888" }`.
  - **Resultado esperado**: HTTP 200 OK; datos actualizados devueltos.
  - **Comprobación de persistencia**: `candidatos.celular` actualizado a `900000888`.
  - **Dependencias**: QA-05, QA-06. | **Estado inicial**: `PENDIENTE`.

- **CP-06-03 (Negativo - Empresa Accede a Perfil sin Postulación Vinculada)**
  - **Objetivo**: Impedir que una empresa consulte el perfil de un candidato que no postuló a su vacante.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación inexistente o perteneciente a otra empresa.
  - **Pasos**: `GET /api/v1/empresas/me/postulaciones/{postulacion}/perfil`.
  - **Resultado esperado**: HTTP 403 Forbidden o 404 Not Found.
  - **Comprobación de persistencia**: Ninguna mutación.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-06-04 (Negativo - Candidato Modifica Perfil Ajeno)**
  - **Objetivo**: Garantizar que el candidato solo pueda mutar su propia entidad a través de `/me`.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Pasos**: Intentar modificar datos enviando identificadores ajenos en el cuerpo.
  - **Resultado esperado**: HTTP 403 Forbidden o payload ignorado; la API deriva identidad únicamente del token Sanctum.
  - **Comprobación de persistencia**: Perfil de otros candidatos intacto.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

---

### CP-07: Registro y Gestión de Vacantes Propias
- **CP-07-01 (Positivo - Creación de Vacante)**
  - **Objetivo**: Registrar vacante propia con campos obligatorios, vigencia y puestos válidos.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-EMP-01`.
  - **Datos**: Título `Desarrollador Full Stack QA`, `numero_vacantes = 2`, `salario_min = 3000`, `salario_max = 4500`, fechas vigentes.
  - **Pasos**: `POST /api/v1/empresas/me/vacantes`.
  - **Resultado esperado**: HTTP 201 Created; vacante creada con `estado_empleo = 'activa'` (para la empresa), sin moderación ni pago (no pública).
  - **Comprobación de persistencia**: Fila en tabla `empleos` con `empresa_id` de `USR-EMP-01`.
  - **Dependencias**: QA-07, QA-16, QA-20. | **Estado inicial**: `PENDIENTE`.

- **CP-07-02 (Negativo - Salario Mínimo Mayor a Máximo)**
  - **Objetivo**: Validar regla de integridad de rango salarial (`chk_empleos_salarios`).
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Datos**: `salario_min = 5000`, `salario_max = 3000`.
  - **Pasos**: `POST /api/v1/empresas/me/vacantes`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: No se crea vacante.
  - **Dependencias**: QA-07, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-07-03 (Negativo - Fecha Fin Anterior a Fecha Inicio)**
  - **Objetivo**: Validar regla de vigencia temporal (`chk_empleos_vigencia`).
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Datos**: `fecha_inicio_vigencia = 2026-12-01`, `fecha_fin_vigencia = 2026-11-01`.
  - **Pasos**: `POST /api/v1/empresas/me/vacantes`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: Sin cambios en BD.
  - **Dependencias**: QA-07, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-07-04 (Negativo - Número de Vacantes Menor o Igual a Cero)**
  - **Objetivo**: Validar regla de cupos positivos (`chk_empleos_numero_vacantes`).
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Datos**: `numero_vacantes = 0`.
  - **Pasos**: `POST /api/v1/empresas/me/vacantes`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: Sin cambios en BD.
  - **Dependencias**: QA-07, QA-16. | **Estado inicial**: `PENDIENTE`.

---

### CP-08: Aprobación y Moderación Administrativa
- **CP-08-01 (Positivo - Administrador Aprueba Vacante)**
  - **Objetivo**: Aprobar vacante registrada habilitando la etapa de pago simulado.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-ADM-01`.
  - **Precondiciones**: Vacante registrada pendiente de revisión (`VAC-BORRADOR`).
  - **Pasos**: `PATCH /api/v1/admin/vacantes/{vacante}/aprobar`.
  - **Resultado esperado**: HTTP 200 OK; decisión `aprobada`.
  - **Comprobación de persistencia**: Registro en `revisiones_vacante` con `decision = 'aprobada'` y `admin_id`.
  - **Dependencias**: QA-07, QA-16, QA-20. | **Estado inicial**: `PENDIENTE`.

- **CP-08-02 (Positivo - Administrador No Aprueba con Motivo)**
  - **Objetivo**: Registrar rechazo administrativo con motivo explícito.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-ADM-01`.
  - **Precondiciones**: Vacante pendiente de revisión (`VAC-BORRADOR`).
  - **Datos**: Body `{ "motivo": "Descripción insuficiente de funciones del puesto" }`.
  - **Pasos**: `PATCH /api/v1/admin/vacantes/{vacante}/no-aprobar`.
  - **Resultado esperado**: HTTP 200 OK; decisión `no_aprobada`.
  - **Comprobación de persistencia**: Fila en `revisiones_vacante` con `decision = 'no_aprobada'` y motivo persistente.
  - **Dependencias**: QA-07, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-08-03 (Negativo - No Aprobar sin Motivo)**
  - **Objetivo**: Verificar check de base de datos que exige motivo al no aprobar (`chk_revisiones_vacante_motivo`).
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: `USR-ADM-01`.
  - **Datos**: Body vacío o con espacios en blanco.
  - **Pasos**: `PATCH /api/v1/admin/vacantes/{vacante}/no-aprobar`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: No se inserta registro en `revisiones_vacante`.
  - **Dependencias**: QA-07, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-08-04 (Negativo - Empresa Intenta Aprobar Vacante)**
  - **Objetivo**: Bloquear acceso de rol empresa a rutas de moderación administrativa.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Pasos**: Enviar PATCH a `/api/v1/admin/vacantes/{vacante}/aprobar` con token de empresa.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`).
  - **Comprobación de persistencia**: Sin cambios en BD.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

---

### CP-09: Pago Simulado de Vacante Aprobada
- **CP-09-01 (Positivo - Pago Exitoso)**
  - **Objetivo**: Completar pago simulado con tarjeta de prueba publicando la vacante.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: Empresa propietaria (`USR-EMP-01`).
  - **Precondiciones**: Vacante en condición `VAC-APROB-SIN-PAGO`.
  - **Datos**: Tarjeta de prueba válida (últimos 4 dígitos `4242`), monto acorde al tarifario.
  - **Pasos**: `POST /api/v1/empresas/me/vacantes/{vacante}/pago`.
  - **Resultado esperado**: HTTP 201 Created contractual; mensaje 'Pago simulado aprobado. La vacante ya está publicada.'; resultado `exitoso`.
  - **Comprobación de persistencia**: Fila en `pagos_vacante` con `resultado = 'exitoso'`, `monto` y `tarjeta_ultimos4`.
  - **Dependencias**: QA-07, QA-16, QA-20. | **Estado inicial**: `PENDIENTE`.

- **CP-09-02 (Negativo - Pago de Vacante No Aprobada)**
  - **Objetivo**: Impedir el pago de una vacante pendiente o rechazada.
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Vacante en condición `VAC-BORRADOR` o `VAC-RECHAZADA`.
  - **Pasos**: `POST /api/v1/empresas/me/vacantes/{vacante}/pago`.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'VACANCY_NOT_APPROVED'`.
  - **Comprobación de persistencia**: No se genera pago exitoso en BD.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-09-03 (Negativo - Pago con Datos de Tarjeta Inválidos)**
  - **Objetivo**: Rechazar pago con número de tarjeta o fecha de expiración inválidos.
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Datos**: Tarjeta con formato erróneo o incompleto.
  - **Pasos**: `POST /api/v1/empresas/me/vacantes/{vacante}/pago`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: Sin registro en `pagos_vacante`.
  - **Dependencias**: QA-07, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-09-04 (Negativo - Empresa B Intenta Pagar Vacante de Empresa A)**
  - **Objetivo**: Bloquear intento de una empresa de pagar una vacante ajena.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-EMP-02`.
  - **Precondiciones**: Vacante perteneciente a `USR-EMP-01`.
  - **Pasos**: Enviar POST a `/api/v1/empresas/me/vacantes/{vacante_empresa1}/pago` con token de empresa 2.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`).
  - **Comprobación de persistencia**: Sin cambios.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-09-05 (Negativo - Pago Duplicado sobre Vacante ya Pagada)**
  - **Objetivo**: Rechazar cualquier intento de volver a pagar una vacante que ya cuenta con un pago exitoso previo.
  - **Tipo**: Integridad / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Vacante ya en condición `VAC-DISPONIBLE` con pago previo exitoso (CP-09-01 ejecutado).
  - **Pasos**: Enviar un segundo request `POST /api/v1/empresas/me/vacantes/{vacante}/pago` con datos de tarjeta válidos.
  - **Resultado esperado**: HTTP 409 Conflict contractual; `code = 'VACANCY_ALREADY_PAID'`; mensaje 'La vacante ya tiene un pago exitoso.'.
  - **Comprobación de persistencia**: El recuento de filas en `pagos_vacante` con `resultado = 'exitoso'` para dicha vacante se mantiene exactamente en 1; no se duplica la publicación.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

---

## 4. Bloque 3: Búsqueda, Disponibilidad y Postulación (RF-06, RF-07, RNF-02)

### CP-10: Reglas de Disponibilidad de Vacantes (11 Condiciones de Indisponibilidad)
- **CP-10-01 (Negativo - Postulación en Vacante Borrador / Sin Revisión)**
  - **Objetivo**: Denegar postulación si la oferta no ha sido revisada por moderación (`VAC-BORRADOR`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante creada pero sin fila en `revisiones_vacante`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_borrador> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `code = 'VACANCY_NOT_AVAILABLE'`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-02 (Negativo - Postulación en Vacante No Aprobada)**
  - **Objetivo**: Denegar postulación si la vacante fue rechazada administrativamente (`VAC-RECHAZADA`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante con `decision = 'no_aprobada'`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_rechazada> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-03 (Negativo - Postulación en Vacante Aprobada sin Pago)**
  - **Objetivo**: Denegar postulación si la vacante fue aprobada pero no pagada (`VAC-APROB-SIN-PAGO`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante aprobada sin fila en `pagos_vacante`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_sin_pago> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-04 (Negativo - Postulación en Vacante con Pago Fallido)**
  - **Objetivo**: Denegar postulación si la vacante tuvo intentos de pago pero ninguno exitoso (`VAC-PAGO-FALLIDO`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante con fila en `pagos_vacante` donde `resultado = 'fallido'`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_pago_fallido> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-05 (Negativo - Postulación en Vacante Pausada)**
  - **Objetivo**: Denegar postulación si la empresa pausó temporalmente la oferta (`VAC-PAUSADA`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate, PHPUnit/MySQL).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante publicada con `estado_empleo = 'pausada'`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_pausada> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-06 (Negativo - Postulación en Vacante Finalizada por la Empresa)**
  - **Objetivo**: Denegar postulación si la empresa dio por concluida la convocatoria (`VAC-FINALIZADA`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante con `estado_empleo = 'finalizada'`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_finalizada> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-07 (Negativo - Postulación en Vacante Vencida)**
  - **Objetivo**: Denegar postulación si la fecha fin de vigencia ya expiró (`VAC-VENCIDA`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante con `fecha_fin_vigencia < CURDATE()`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_vencida> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin nueva postulación.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-08 (Negativo - Postulación en Vacante con Vigencia Futura)**
  - **Objetivo**: Denegar postulación si la fecha de inicio de vigencia aún no ha llegado (`VAC-VIGENCIA-FUTURA`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante con `fecha_inicio_vigencia > CURDATE()`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_futura> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-09 (Negativo - Postulación en Vacante con Cupos Agotados)**
  - **Objetivo**: Denegar postulación si ya se alcanzó el total de contratados (`contratados == numero_vacantes`, `VAC-COMPLETA`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate, PHPUnit/MySQL).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante con todos sus puestos cubiertos.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_completa> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin cambios en BD.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-10 (Negativo - Postulación en Vacante de Empresa Suspendida)**
  - **Objetivo**: Denegar postulación si la cuenta de la empresa propietaria fue suspendida (`VAC-EMPRESA-SUSP`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante aprobada y pagada pero con usuario empresa en `estado_cuenta = 'suspendido'`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_empresa_suspendida> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin registro en `postulaciones`.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-10-11 (Negativo - Postulación en Vacante Eliminada)**
  - **Objetivo**: Denegar postulación si la vacante fue borrada lógicamente (`deleted_at IS NOT NULL`, `VAC-ELIMINADA`).
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante con soft delete.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_eliminada> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `VACANCY_NOT_AVAILABLE`.
  - **Comprobación de persistencia**: Sin cambios en BD.
  - **Dependencias**: QA-07, QA-17. | **Estado inicial**: `PENDIENTE`.

---

### CP-11: Búsqueda, Filtros y Detalle de Vacante Disponible
- **CP-11-01 (Positivo - Búsqueda Pública con Filtros)**
  - **Objetivo**: Filtrar vacantes disponibles por área y modalidad devolviendo solo las que cumplen las 7 condiciones de disponibilidad.
  - **Tipo**: Funcional / Positiva (Karate, Selenium, Appium).
  - **Actor**: Candidato autenticado.
  - **Pasos**: `GET /api/v1/vacantes?modalidad=remoto`.
  - **Resultado esperado**: HTTP 200 OK; listado con vacantes activas y disponibles.
  - **Comprobación de persistencia**: Solo lectura.
  - **Dependencias**: QA-07, QA-19. | **Estado inicial**: `PENDIENTE`.

- **CP-11-02 (Positivo - Consulta de Detalle de Vacante Disponible)**
  - **Objetivo**: Obtener detalle de una oferta publicada verificando campos acordados.
  - **Tipo**: Funcional / Positiva (Karate, Selenium, Appium).
  - **Actor**: Candidato autenticado.
  - **Precondiciones**: Vacante en condición `VAC-DISPONIBLE`.
  - **Pasos**: `GET /api/v1/vacantes/{vacante}`.
  - **Resultado esperado**: HTTP 200 OK; estructura de vacante pública.
  - **Comprobación de persistencia**: Solo lectura.
  - **Dependencias**: QA-07, QA-19, QA-22. | **Estado inicial**: `PENDIENTE`.

- **CP-11-03 (Positivo - Búsqueda sin Resultados Coincidentes)**
  - **Objetivo**: Verificar que un criterio de búsqueda que no coincida retorne arreglo vacío sin fallas de servidor.
  - **Tipo**: Funcional / Positiva (Karate).
  - **Actor**: Candidato autenticado.
  - **Pasos**: `GET /api/v1/vacantes?q=termino_inexistente_xyz123`.
  - **Resultado esperado**: HTTP 200 OK; arreglo `data: []`.
  - **Comprobación de persistencia**: Solo lectura.
  - **Dependencias**: QA-07. | **Estado inicial**: `PENDIENTE`.

---

### CP-12: Primera Postulación Persistente
- **CP-12-01 (Positivo - Creación Exitosa en Recibido)**
  - **Objetivo**: Registrar la primera postulación de un candidato en etapa `Recibido`.
  - **Tipo**: Funcional / Positiva (Karate, Selenium, Appium).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Vacante en condición `VAC-DISPONIBLE`; candidato sin postulación previa.
  - **Datos**: Body `{ "vacante_id": <id_disponible> }`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones`.
  - **Resultado esperado**: HTTP 201 Created; postulación registrada con `estado_postulacion_id = 1` (`Recibido`).
  - **Comprobación de persistencia**: Fila en tabla `postulaciones` con `candidato_id` de `USR-CND-01`, `empleo_id` y `retirada_en = NULL`.
  - **Dependencias**: QA-08, QA-16, QA-19, QA-22. | **Estado inicial**: `PENDIENTE`.

- **CP-12-02 (Negativo - Candidato con Cuenta Suspendida Postula)**
  - **Objetivo**: Impedir que un candidato suspendido postule a una vacante.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-CND-03` (Suspendido).
  - **Procedimiento de preparación ejecutable**:
    1. Se emite un token de prueba previo para el candidato (mientras estaba activo o mediante helper de pruebas).
    2. Se ejecuta la suspensión administrativa en la base de datos: `UPDATE usuarios SET estado_cuenta = 'suspendido', fecha_suspension = NOW() WHERE correo = 'suspendido@qa.example.test'`.
    3. Con el token previamente obtenido, se envía la petición de postulación.
    4. Al finalizar la prueba, se restaura la cuenta con `QaSeeder`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con el token del candidato suspendido.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`); `PostulacionService` comprueba en BD `$usuarioCandidato->estado_cuenta !== 'activo'` y bloquea la operación.
  - **Comprobación de persistencia**: Ninguna fila creada en `postulaciones`.
  - **Dependencias**: QA-08, QA-17. | **Estado inicial**: `PENDIENTE`.

---

### CP-13: Postulación Duplicada Secuencial y Carrera Concurrente
- **CP-13-01 (Negativo - Duplicado Secuencial)**
  - **Objetivo**: Rechazar un segundo intento secuencial de postulación del mismo candidato a la misma vacante.
  - **Tipo**: Integridad / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: CP-12-01 ejecutado previamente.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con el mismo `vacante_id`.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'DUPLICATE_APPLICATION'`.
  - **Comprobación de persistencia**: Exactamente 1 fila en `postulaciones` para el par `(candidato_id, empleo_id)`.
  - **Dependencias**: QA-08, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-13-02 (Negativo - Carrera Concurrente del Mismo Candidato)**
  - **Objetivo**: Verificar que 2 solicitudes concurrentes exactas del mismo candidato resultan en una creación (201) y un conflicto (409).
  - **Tipo**: Concurrencia / Integridad (JMeter, PHPUnit/MySQL).
  - **Actor**: `USR-CND-01` (2 hilos simultáneos).
  - **Precondiciones**: Candidato sin postulación previa; vacante disponible.
  - **Pasos**: Disparar requests paralelos simultáneos sincronizados.
  - **Resultado esperado**: 1 respuesta HTTP 201 Created; 1 respuesta HTTP 409 Conflict (`DUPLICATE_APPLICATION`).
  - **Comprobación de persistencia**: Restricción `unique(['candidato_id', 'empleo_id'])` preservada; recuento en DB = 1.
  - **Dependencias**: QA-08, QA-17, QA-25. | **Estado inicial**: `PENDIENTE`.

---

### CP-14: Postulaciones Simultáneas de Candidatos Diferentes
- **CP-14-01 (Positivo - Admisión Simultánea)**
  - **Objetivo**: Comprobar que candidatos distintos pueden postular concurrentemente (el cupo limita contrataciones, no postulaciones).
  - **Tipo**: Concurrencia / Rendimiento (JMeter, PHPUnit/MySQL).
  - **Actor**: `USR-CND-01`, `USR-CND-02` y candidatos de carga.
  - **Precondiciones**: Vacante con `numero_vacantes = 1` pero disponible.
  - **Pasos**: 10 candidatos distintos envían `POST /api/v1/candidatos/me/postulaciones` en el mismo segundo.
  - **Resultado esperado**: Todos obtienen HTTP 201 Created; ninguno es bloqueado por el cupo de vacantes.
  - **Comprobación de persistencia**: 10 filas registradas en `postulaciones`.
  - **Dependencias**: QA-08, QA-25. | **Estado inicial**: `PENDIENTE`.

- **CP-14-02 (Positivo - Postulación con Múltiples Postulantes Previos)**
  - **Objetivo**: Permitir postular cuando ya existen decenas de candidatos inscritos mientras la oferta siga vigente.
  - **Tipo**: Funcional / Positiva (Karate).
  - **Actor**: Nuevo candidato autenticado.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones`.
  - **Resultado esperado**: HTTP 201 Created.
  - **Comprobación de persistencia**: Nueva fila en `postulaciones`.
  - **Dependencias**: QA-08, QA-16. | **Estado inicial**: `PENDIENTE`.

---

### CP-15: Autorización de Servidor y Protección de Identidad
- **CP-15-01 (Negativo - Token Ausente)**
  - **Objetivo**: Bloquear endpoints protegidos cuando no se incluye cabecera `Authorization`.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: Visitante anónimo.
  - **Pasos**: `GET /api/v1/candidatos/me/postulaciones` sin header Bearer.
  - **Resultado esperado**: HTTP 401 Unauthorized.
  - **Comprobación de persistencia**: Sin mutaciones.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-15-02 (Negativo - Candidato Invoca Endpoint de Empresa)**
  - **Objetivo**: Bloquear acceso de candidato a rutas exclusivas de rol empresa.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Pasos**: `GET /api/v1/empresas/me/vacantes` con token de candidato.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`).
  - **Comprobación de persistencia**: Sin cambios.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-15-03 (Negativo - Empresa Modifica Vacante de Otra Empresa)**
  - **Objetivo**: Impedir que una empresa pause, finalice o modifique vacantes de otra empresa.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-EMP-02`.
  - **Precondiciones**: Vacante de `USR-EMP-01`.
  - **Pasos**: `PATCH /api/v1/empresas/me/vacantes/{vacante_empresa1}/pausar` con token de empresa 2.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`).
  - **Comprobación de persistencia**: `estado_empleo` permanece en `activa`.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-15-04 (Negativo - Inyección de Candidato en Body de Postulación)**
  - **Objetivo**: Garantizar que el servidor derive la identidad estrictamente del token y rechace o ignore `candidato_id` en el body.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-CND-01`.
  - **Datos**: Body `{ "vacante_id": 1, "candidato_id": 999 }`.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones`.
  - **Resultado esperado**: HTTP 201 Created (asociando la postulación al ID real del token de `USR-CND-01`) o 422 si la regla de validación prohíbe el campo; nunca asocia al candidato inyectado.
  - **Comprobación de persistencia**: `postulaciones.candidato_id` coincide estrictamente con `USR-CND-01`.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

---

## 5. Bloque 4: Selección, Decisiones, Retiro e Historial (RF-08, RF-09, RF-10, RNF-02)

### CP-16: Transiciones Permitidas en Kanban
- **CP-16-01 (Positivo - Recibido a En Revisión)**
  - **Objetivo**: Avanzar postulación de `Recibido` a `En Revisión`.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: Empresa propietaria (`USR-EMP-01`).
  - **Precondiciones**: Postulación en condición `POS-RECIBIDO`.
  - **Pasos**: `PATCH /api/v1/empresas/me/vacantes/{vacante}/postulaciones/{postulacion}/avanzar`.
  - **Resultado esperado**: HTTP 200 OK; estado resultante `En Revisión` (`id = 2`).
  - **Comprobación de persistencia**: `postulaciones.estado_postulacion_id = 2`; registro en `historial_postulaciones`.
  - **Dependencias**: QA-09, QA-16, QA-20. | **Estado inicial**: `PENDIENTE`.

- **CP-16-02 (Positivo - En Revisión a Entrevista)**
  - **Objetivo**: Avanzar postulación de `En Revisión` a `Entrevista`.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación en `POS-REVISION`.
  - **Pasos**: `PATCH .../avanzar`.
  - **Resultado esperado**: HTTP 200 OK; estado `Entrevista` (`id = 3`).
  - **Comprobación de persistencia**: `estado_postulacion_id = 3`; nuevo movimiento en `historial_postulaciones`.
  - **Dependencias**: QA-09, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-16-03 (Positivo - Entrevista a Contratado con Cupo)**
  - **Objetivo**: Contratar postulante en entrevista existiendo vacantes disponibles (`contratados < numero_vacantes`).
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación en `POS-ENTREVISTA`; cupo disponible.
  - **Pasos**: `PATCH .../contratar`.
  - **Resultado esperado**: HTTP 200 OK; estado final `Contratado` (`id = 4`).
  - **Comprobación de persistencia**: `estado_postulacion_id = 4`; historial registrado.
  - **Dependencias**: QA-09, QA-16, QA-20. | **Estado inicial**: `PENDIENTE`.

---

### CP-17: Rechazo con Motivo Válido
- **CP-17-01 (Positivo - Rechazo desde En Revisión)**
  - **Objetivo**: Rechazar postulante indicando motivo del catálogo (`motivo_rechazo_id = 1`: 'Perfil no se ajusta').
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación en `POS-REVISION`.
  - **Pasos**: `PATCH .../rechazar` con body `{ "motivo_rechazo_id": 1 }`.
  - **Resultado esperado**: HTTP 200 OK; estado final `Rechazado` (`id = 5`).
  - **Comprobación de persistencia**: `postulaciones.estado_postulacion_id = 5`; en `historial_postulaciones` se guarda `motivo_rechazo_id = 1`.
  - **Dependencias**: QA-09, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-17-02 (Positivo - Rechazo desde Entrevista)**
  - **Objetivo**: Rechazar postulante desde etapa `Entrevista` con motivo del catálogo (`motivo_rechazo_id = 2`).
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación en `POS-ENTREVISTA`.
  - **Pasos**: `PATCH .../rechazar` con body `{ "motivo_rechazo_id": 2 }`.
  - **Resultado esperado**: HTTP 200 OK; estado final `Rechazado`.
  - **Comprobación de persistencia**: Estado e historial registrados en BD.
  - **Dependencias**: QA-09, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-17-03 (Negativo - Rechazo sin Motivo)**
  - **Objetivo**: Impedir rechazo sin motivo o con ID de motivo inexistente.
  - **Tipo**: Validación / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Datos**: Body `{}` o `{ "motivo_rechazo_id": 999 }`.
  - **Pasos**: `PATCH .../rechazar`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: Estado anterior intacto; sin cambios en historial.
  - **Dependencias**: QA-09, QA-16. | **Estado inicial**: `PENDIENTE`.

---

### CP-18: Saltos de Estado Inválidos y Operaciones Bloqueadas
- **CP-18-01 (Negativo - Salto Directo Recibido a Contratado)**
  - **Objetivo**: Rechazar salto de etapas no permitido en la máquina de estados.
  - **Tipo**: Integridad / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación en `POS-RECIBIDO`.
  - **Pasos**: `PATCH .../contratar`.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'INVALID_TRANSITION'`.
  - **Comprobación de persistencia**: Postulación permanece en `Recibido`.
  - **Dependencias**: QA-09, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-18-02 (Negativo - Modificar Postulación en Estado Final)**
  - **Objetivo**: Bloquear cualquier acción sobre postulación ya contratada o rechazada.
  - **Tipo**: Integridad / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación en `POS-CONTRATADO` o `POS-RECHAZADO`.
  - **Pasos**: `PATCH .../avanzar` o `PATCH .../rechazar`.
  - **Resultado esperado**: HTTP 409 Conflict; `INVALID_TRANSITION`.
  - **Comprobación de persistencia**: Estado intacto.
  - **Dependencias**: QA-09, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-18-03 (Negativo - Transición sobre Postulación Retirada)**
  - **Objetivo**: Impedir que la empresa mueva una postulación retirada por el candidato.
  - **Tipo**: Integridad / Negativa (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Postulación en condición `POS-RETIRADA` (`retirada_en IS NOT NULL`).
  - **Pasos**: `PATCH .../avanzar` o `PATCH .../contratar`.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'APPLICATION_WITHDRAWN'`.
  - **Comprobación de persistencia**: Sin mutaciones.
  - **Dependencias**: QA-09, QA-17. | **Estado inicial**: `PENDIENTE`.

---

### CP-19: Contratación Concurrente y Límite de Puestos
- **CP-19-01 (Negativo - Sobrecontratación Concurrente al Último Puesto)**
  - **Objetivo**: Demostrar que ante 2 contrataciones concurrentes al único cupo restante, 1 prospera y la otra se rechaza con 409.
  - **Tipo**: Concurrencia / Integridad (JMeter, PHPUnit/MySQL).
  - **Actor**: `USR-EMP-01` (2 evaluadores concurrentes).
  - **Precondiciones**: Vacante con `numero_vacantes = 1`; 2 postulaciones en `POS-ENTREVISTA`.
  - **Pasos**: Disparar `PATCH .../contratar` simultáneamente sobre postulación A y B.
  - **Resultado esperado**: 1 request responde HTTP 200 OK; el concurrente responde HTTP 409 Conflict con `HIRING_LIMIT_REACHED`.
  - **Comprobación de persistencia**: Recuento de contratados en la vacante es exactamente 1; nunca se excede `numero_vacantes`.
  - **Dependencias**: QA-09, QA-25. | **Estado inicial**: `PENDIENTE`.

- **CP-19-02 (Positivo - Contrataciones Paralelas con Cupos Disponibles)**
  - **Objetivo**: Permitir contrataciones concurrentes exitosas si hay puestos suficientes (`numero_vacantes = 5`, contratando a 2 candidatos a la vez).
  - **Tipo**: Concurrencia (JMeter, PHPUnit/MySQL).
  - **Actor**: `USR-EMP-01`.
  - **Precondiciones**: Vacante con puestos libres suficientes.
  - **Pasos**: Contratar simultáneamente a 2 candidatos en entrevista.
  - **Resultado esperado**: Ambos requests retornan HTTP 200 OK.
  - **Comprobación de persistencia**: 2 contratados añadidos; `contratados <= numero_vacantes`.
  - **Dependencias**: QA-09, QA-25. | **Estado inicial**: `PENDIENTE`.

---

### CP-20: Retiro de Postulación
- **CP-20-01 (Positivo - Retiro por Candidato Propietario)**
  - **Objetivo**: Permitir al candidato retirarse voluntariamente en etapas permitidas conservando el estado y fijando fecha de retiro.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: Postulación en `POS-REVISION` con `retirada_en = NULL`.
  - **Pasos**: `PATCH /api/v1/candidatos/me/postulaciones/{postulacion}/retirar`.
  - **Resultado esperado**: HTTP 200 OK; postulación marcada como retirada; estado de selección preservado.
  - **Comprobación de persistencia**: `postulaciones.retirada_en IS NOT NULL`; `estado_postulacion_id = 2`.
  - **Dependencias**: QA-08, QA-17, QA-19. | **Estado inicial**: `PENDIENTE`.

- **CP-20-02 (Negativo - Retiro por Candidato Ajeno)**
  - **Objetivo**: Impedir que un candidato retire la postulación de otro candidato.
  - **Tipo**: Seguridad / Negativa (Karate).
  - **Actor**: `USR-CND-02`.
  - **Precondiciones**: Postulación perteneciente a `USR-CND-01`.
  - **Pasos**: Candidato 2 envía PATCH de retiro con ID de postulación de Candidato 1.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`).
  - **Comprobación de persistencia**: `retirada_en` permanece en NULL.
  - **Dependencias**: QA-05, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-20-03 (Negativo - Repostular tras Retiro)**
  - **Objetivo**: Confirmar que el retiro no permite volver a postular a la misma vacante.
  - **Tipo**: Integridad / Negativa (Karate).
  - **Actor**: Candidato previamente retirado.
  - **Precondiciones**: CP-20-01 ejecutado.
  - **Pasos**: `POST /api/v1/candidatos/me/postulaciones` con el mismo `vacante_id`.
  - **Resultado esperado**: HTTP 409 Conflict (`DUPLICATE_APPLICATION`).
  - **Comprobación de persistencia**: No se genera nueva fila.
  - **Dependencias**: QA-08, QA-17. | **Estado inicial**: `PENDIENTE`.

- **CP-20-04 (Negativo - Retiro en Estado Final)**
  - **Objetivo**: Impedir el retiro de una postulación ya contratada o rechazada.
  - **Tipo**: Lógica de Negocio / Negativa (Karate).
  - **Actor**: Candidato contratado.
  - **Precondiciones**: Postulación en `POS-CONTRATADO`.
  - **Pasos**: PATCH de retiro.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'APPLICATION_NOT_WITHDRAWABLE'`.
  - **Comprobación de persistencia**: `retirada_en` permanece en NULL.
  - **Dependencias**: QA-08, QA-17. | **Estado inicial**: `PENDIENTE`.

---

### CP-21: Seguimiento Propio y Consulta Histórica
- **CP-21-01 (Positivo - Mis Postulaciones)**
  - **Objetivo**: Candidato consulta el estado y seguimiento de todas sus postulaciones activas e históricas.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: `USR-CND-01`.
  - **Pasos**: `GET /api/v1/candidatos/me/postulaciones`.
  - **Resultado esperado**: HTTP 200 OK; listado con títulos, estados, si está retirada y fechas.
  - **Comprobación de persistencia**: Solo lectura.
  - **Dependencias**: QA-08, QA-09, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-21-02 (Positivo - Consulta de Vacante Histórica Pausada o Eliminada)**
  - **Objetivo**: Candidato con postulación previa puede consultar el detalle de su vacante aunque la empresa la haya pausado o eliminado.
  - **Tipo**: Funcional / Positiva (Karate, Selenium).
  - **Actor**: Candidato con postulación previa.
  - **Precondiciones**: Vacante pausada o eliminada (`VAC-PAUSADA` o `VAC-ELIMINADA`) con postulación existente.
  - **Pasos**: `GET /api/v1/candidatos/me/postulaciones/{postulacion}/vacante`.
  - **Resultado esperado**: HTTP 200 OK; datos de la vacante histórica devueltos (`withTrashed()`).
  - **Comprobación de persistencia**: Solo lectura.
  - **Dependencias**: QA-08, QA-16. | **Estado inicial**: `PENDIENTE`.

---

### CP-22: Historial de Postulaciones y Kanban
- **CP-22-01 (Positivo - Consulta de Historial Completo por la Empresa)**
  - **Objetivo**: Verificar que la empresa puede auditar toda la cronología de movimientos, fechas y motivos en su vacante.
  - **Tipo**: Auditoría / Positiva (Karate, Selenium).
  - **Actor**: `USR-EMP-01`.
  - **Pasos**: `GET /api/v1/empresas/me/vacantes/{vacante}/postulaciones/historial`.
  - **Resultado esperado**: HTTP 200 OK; listado incluye activas y retiradas con sub-arreglo `movimientos`.
  - **Comprobación de persistencia**: Consistente con `historial_postulaciones`.
  - **Dependencias**: QA-09, QA-16, QA-20. | **Estado inicial**: `PENDIENTE`.

- **CP-22-02 (Positivo - Conservación de Motivo en Historial tras Múltiples Cambios)**
  - **Objetivo**: Verificar que el motivo de rechazo queda fijado en el registro histórico correspondiente.
  - **Tipo**: Integridad / Positiva (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Pasos**: Consultar historial de postulación rechazada.
  - **Resultado esperado**: Objeto `motivo_rechazo` presente con ID y texto descriptivo.
  - **Comprobación de persistencia**: Relación persistente con `motivos_rechazo`.
  - **Dependencias**: QA-09, QA-16. | **Estado inicial**: `PENDIENTE`.

- **CP-22-03 (Positivo - Historial Mantiene Registros de Postulaciones Retiradas)**
  - **Objetivo**: Garantizar que el retiro no purga las transiciones previas de la postulación en la auditoría.
  - **Tipo**: Integridad / Positiva (Karate).
  - **Actor**: `USR-EMP-01`.
  - **Pasos**: Consultar historial tras ejecución de CP-20-01.
  - **Resultado esperado**: `retirada = true`, `retirada_en` visible, y todos los movimientos previos conservados.
  - **Comprobación de persistencia**: Sin registros borrados en `historial_postulaciones`.
  - **Dependencias**: QA-09, QA-16. | **Estado inicial**: `PENDIENTE`.

---

### CP-23: Recorrido E2E Integrado Web
- **CP-23-01 (Positivo - Recorrido E2E Completo de los Tres Roles)**
  - **Objetivo**: Demostrar el flujo íntegro del negocio en navegador web coordinando los 3 roles:
    1. Empresa `USR-EMP-01` inicia sesión y registra vacante.
    2. Administrador `USR-ADM-01` inicia sesión y aprueba la vacante en moderación.
    3. Empresa completa pago simulado con tarjeta de prueba; vacante pasa a publicada.
    4. Candidato `USR-CND-01` busca la vacante, revisa detalle y postula.
    5. Empresa revisa Kanban, avanza a entrevista y contrata.
    6. Candidato y empresa verifican estado final e historial persistente.
  - **Tipo**: E2E Integrada (Selenium).
  - **Actor**: Coordinación de los 3 roles.
  - **Precondiciones**: Base aislada de QA preparada; frontend levantado; credenciales de `QaSeeder`.
  - **Pasos**: Ejecución automatizada paso a paso mediante Page Objects con esperas explícitas.
  - **Resultado esperado**: Recorrido 100% exitoso sin errores en consola web y con estado final reflejado en UI y BD.
  - **Comprobación de persistencia**: Estado final `Contratado`, pago exitoso y movimientos en historial auditables.
  - **Dependencias**: QA-19, QA-20, QA-30. | **Estado inicial**: `PENDIENTE`.

---

## 6. Bloque 5: PWA Móvil Android (PWA-01 a PWA-04)

### CP-24: Manifiesto e Instalación Standalone en Android
- **CP-24-01 (Positivo - Instalación y Modo Standalone)**
  - **Objetivo**: Comprobar instalación en Android con manifest válido (iconos 192/512, standalone, start_url HTTPS).
  - **Tipo**: PWA / Dispositivo (Appium).
  - **Actor**: Candidato móvil.
  - **Precondiciones**: PWA servida bajo HTTPS; dispositivo/emulador Android conectado.
  - **Pasos**: Instalar y abrir PWA desde el lanzador de Android.
  - **Resultado esperado**: Apertura en ventana standalone sin barra de URL del navegador externo.
  - **Dependencias**: QA-10, QA-21, QA-22. | **Estado inicial**: `PENDIENTE`.

- **CP-24-02 (Positivo - Exposición de Contexto WebView)**
  - **Objetivo**: Verificar que Appium puede conmutar al contexto webview de la PWA instalada para interactuar con el DOM.
  - **Tipo**: Automatización Móvil (Appium).
  - **Actor**: Runner Appium.
  - **Pasos**: Ejecutar `driver.getContextHandles()` y conmutar a `WEBVIEW_*`.
  - **Resultado esperado**: Contexto WebView identificado e interactuable.
  - **Dependencias**: QA-21, QA-22. | **Estado inicial**: `PENDIENTE`.

---

### CP-25: Diseño Móvil, Accesibilidad y Teclado Virtual
- **CP-25-01 (Positivo - Usabilidad en Viewports Móviles)**
  - **Objetivo**: Verificar formularios operables en resoluciones 360×800 y 412×915 con teclado virtual desplegado.
  - **Tipo**: Usabilidad Móvil (Appium, Selenium Mobile).
  - **Actor**: Candidato móvil.
  - **Pasos**: Abrir formulario de login y postulación en viewports móviles; activar teclado virtual.
  - **Resultado esperado**: Sin desbordamientos horizontales; botones de acción accesibles.
  - **Dependencias**: QA-12, QA-22. | **Estado inicial**: `PENDIENTE`.

- **CP-25-02 (Positivo - Selectores Estables y Accesibilidad)**
  - **Objetivo**: Validar presencia de atributos `data-testid` o selectores estables en botones y campos del flujo crítico.
  - **Tipo**: Automatización / Accesibilidad (Appium).
  - **Actor**: Runner Appium.
  - **Pasos**: Localizar elementos clave del flujo por selector estable.
  - **Resultado esperado**: 100% de los elementos interactivos localizables sin depender de clases CSS dinámicas.
  - **Dependencias**: QA-12, QA-22. | **Estado inicial**: `PENDIENTE`.

---

### CP-26: Apertura sin Conexión y Privacidad de Caché
- **CP-26-01 (Positivo - Vista Offline Honesta)**
  - **Objetivo**: Presentar shell y vista explicativa offline en lugar de error de red nativo al perder conectividad.
  - **Tipo**: Resiliencia / PWA (Appium con corte de red).
  - **Actor**: Candidato móvil sin internet.
  - **Precondiciones**: PWA precargada previamente en línea.
  - **Pasos**: Activar modo avión y recargar/abrir la PWA.
  - **Resultado esperado**: Shell de la app visible informando la falta de conexión de red de forma clara.
  - **Dependencias**: QA-11, QA-23. | **Estado inicial**: `PENDIENTE`.

- **CP-26-02 (Negativo - Sin Tokens en Cache Storage)**
  - **Objetivo**: Verificar que el Service Worker no almacena tokens Bearer en Cache Storage.
  - **Tipo**: Seguridad / Inspección (Appium / DevTools).
  - **Actor**: Auditor de seguridad.
  - **Pasos**: Inspeccionar Cache Storage tras sesión activa.
  - **Resultado esperado**: Cero tokens o credenciales almacenados en caché SW.
  - **Dependencias**: QA-11, QA-23. | **Estado inicial**: `PENDIENTE`.

- **CP-26-03 (Negativo - Sin Respuestas Privadas en Caché)**
  - **Objetivo**: Verificar que respuestas de endpoints privados (`/me`, `/postulaciones`) no se cacheen en el Service Worker.
  - **Tipo**: Seguridad / Inspección (Appium / DevTools).
  - **Actor**: Auditor de seguridad.
  - **Pasos**: Inspeccionar entradas de caché.
  - **Resultado esperado**: Solo assets estáticos (JS, CSS, iconos); cero respuestas JSON de API privada.
  - **Dependencias**: QA-11, QA-23. | **Estado inicial**: `PENDIENTE`.

---

### CP-27: Pérdida de Conexión en Escrituras y Recuperación
- **CP-27-01 (Negativo - Corte de Red Durante Envío de Postulación)**
  - **Objetivo**: Demostrar que ante corte de red durante una postulación, la PWA NO simula éxito ficticio ni reenvía a ciegas.
  - **Tipo**: Resiliencia / PWA (Appium).
  - **Actor**: Candidato móvil.
  - **Pasos**: Cortar red justo al enviar la solicitud de postulación.
  - **Resultado esperado**: La app informa error de red y estado incierto; no muestra confirmación de éxito ni encola peticiones sin control.
  - **Comprobación de persistencia**: Base de datos íntegra sin duplicados.
  - **Dependencias**: QA-11, QA-22, QA-23. | **Estado inicial**: `PENDIENTE`.

- **CP-27-02 (Positivo - Consulta de Estado Persistente al Reconectar)**
  - **Objetivo**: Al restablecer red, la aplicación consulta el resultado persistente en el servidor antes de permitir reintentar.
  - **Tipo**: Resiliencia / PWA (Appium).
  - **Actor**: Candidato móvil.
  - **Pasos**: Restaurar conexión e invocar mecanismo de validación de estado persistente.
  - **Resultado esperado**: Si la postulación ya existía en el servidor, muestra el estado real y bloquea el botón de postular; si no existía, permite el reintento limpio.
  - **Dependencias**: QA-11, QA-22. | **Estado inicial**: `PENDIENTE`.

---

### CP-28: Actualización entre Builds de la PWA
- **CP-28-01 (Positivo - Detección de Nueva Versión)**
  - **Objetivo**: Comprobar que el Service Worker detecta un nuevo build desplegado sin pérdida silenciosa de formularios.
  - **Tipo**: PWA / Ciclo de vida (Appium).
  - **Actor**: Candidato con PWA abierta.
  - **Pasos**: Desplegar nueva versión del frontend y abrir la app.
  - **Resultado esperado**: Notificación amigable de actualización disponible.
  - **Dependencias**: QA-11, QA-23. | **Estado inicial**: `PENDIENTE`.

- **CP-28-02 (Positivo - Recarga en Versión Actualizada)**
  - **Objetivo**: Verificar que al aceptar la actualización, la PWA recarga ordenadamente en la versión más reciente.
  - **Tipo**: PWA / Ciclo de vida (Appium).
  - **Actor**: Candidato móvil.
  - **Pasos**: Aceptar actualización.
  - **Resultado esperado**: PWA operativa en el nuevo build con navegación consistente.
  - **Dependencias**: QA-11, QA-23. | **Estado inicial**: `PENDIENTE`.

---

### CP-29: Flujo Móvil Completo en PWA Android
- **CP-29-01 (Positivo - Recorrido Completo desde Dispositivo Android)**
  - **Objetivo**: Demostrar login, búsqueda de empleo, consulta de detalle y postulación desde la PWA instalada en Android mediante Appium.
  - **Tipo**: E2E Móvil (Appium).
  - **Actor**: `USR-CND-01`.
  - **Precondiciones**: PWA instalada en Android; entorno QA accesible.
  - **Pasos**: Abrir PWA -> Login -> Búsqueda -> Ver detalle -> Postular.
  - **Resultado esperado**: Flujo completo aprobado con confirmación visual de postulación en UI.
  - **Dependencias**: QA-21, QA-22. | **Estado inicial**: `PENDIENTE`.

- **CP-29-02 (Positivo - Verificación de Persistencia tras Postulación Móvil)**
  - **Objetivo**: Verificar que la postulación ejecutada desde Android existe físicamente en MySQL en estado `Recibido`.
  - **Tipo**: Integración / Persistencia (Appium + DB).
  - **Actor**: Runner Appium / Script de verificación.
  - **Pasos**: Consultar tabla `postulaciones` tras CP-29-01.
  - **Resultado esperado**: Fila persistida con `candidato_id` de `USR-CND-01` y `retirada_en = NULL`.
  - **Dependencias**: QA-22. | **Estado inicial**: `PENDIENTE`.

---

### CP-30: Sesión Móvil, Reapertura y Logout
- **CP-30-01 (Positivo - Reapertura de App con Sesión Válida desde Segundo Plano)**
  - **Objetivo**: Verificar que al pasar la PWA a segundo plano y reabrirla en Android manteniendo la instancia de navegación, la sesión se preserva conforme al contrato vigente de `sessionStorage` en el frontend (`AuthService.ts`). Si el equipo decide en QA-11/QA-23 cambiar a almacenamiento persistente (`localStorage`), este caso se actualizará con el nuevo contrato acordado.
  - **Tipo**: Funcional Móvil (Appium).
  - **Actor**: Candidato móvil.
  - **Pasos**: Iniciar sesión -> Poner PWA en segundo plano -> Reabrir PWA desde el conmutador de aplicaciones.
  - **Resultado esperado**: Acceso directo al área privada sin solicitar login nuevamente.
  - **Dependencias**: QA-23. | **Estado inicial**: `PENDIENTE`.

- **CP-30-02 (Negativo - Reapertura con Token Expirado o Revocado)**
  - **Objetivo**: Comprobar que si el token fue revocado en backend, la reapertura limpia el almacenamiento de sesión (`sessionStorage`) y redirige al login sin exponer vistas privadas.
  - **Tipo**: Seguridad Móvil (Appium).
  - **Actor**: Candidato con sesión revocada en servidor.
  - **Pasos**: Revocar token en DB -> Reabrir PWA.
  - **Resultado esperado**: Redirección inmediata a pantalla de login.
  - **Dependencias**: QA-23. | **Estado inicial**: `PENDIENTE`.

- **CP-30-03 (Negativo - Logout en PWA y Bloqueo de Navegación Atrás)**
  - **Objetivo**: Comprobar que tras hacer logout en la PWA, el botón de retroceso de Android no permite reingresar a vistas protegidas en caché.
  - **Tipo**: Seguridad Móvil (Appium).
  - **Actor**: Candidato móvil.
  - **Pasos**: Hacer logout -> Presionar botón atrás del sistema Android.
  - **Resultado esperado**: Se mantiene en pantalla de login; vistas privadas bloqueadas por guards de Angular.
  - **Dependencias**: QA-23. | **Estado inicial**: `PENDIENTE`.

---

## 7. Bloque 6: Rendimiento, Carga y CI (RNF-03, RNF-04)

> **Nota sobre rendimiento**: Los valores de p95 ≤ 2.0 s (lecturas), p95 ≤ 3.0 s (escrituras) y tasa de error < 1.0% representan **objetivos preliminares de planificación** propuestos en este diseño, sujetos a medición de la línea base real y aprobación formal en la tarea **QA-24** (#22).

### CP-31: Carga de Búsqueda y Detalle de Vacantes
- **CP-31-01 (Rendimiento - Búsqueda Bajo Carga)**
  - **Objetivo**: Medir p95 y tasa de error en `/api/v1/vacantes` bajo carga propuesta de 25 usuarios virtuales concurrentes.
  - **Tipo**: Rendimiento / Carga (JMeter).
  - **Actor**: 25 usuarios virtuales.
  - **Precondiciones**: Entorno QA aislado; base poblada con vacantes disponibles.
  - **Objetivo preliminar**: p95 ≤ 2.0 s; error < 1.0% (a confirmar en QA-24).
  - **Pasos**: Rampa de 30 s, duración 2 min sostenidos con reporte JTL/HTML.
  - **Resultado esperado**: Métricas dentro de los umbrales aprobados en QA-24.
  - **Dependencias**: QA-24, QA-25. | **Estado inicial**: `PENDIENTE`.

- **CP-31-02 (Rendimiento - Detalle de Vacante Bajo Carga)**
  - **Objetivo**: Medir tiempos de respuesta en `/api/v1/vacantes/{vacante}` bajo carga concurrente sostenida.
  - **Tipo**: Rendimiento (JMeter).
  - **Actor**: 25 usuarios virtuales.
  - **Objetivo preliminar**: p95 ≤ 2.0 s; cero caídas de servicio.
  - **Pasos**: Ejecutar plan de pruebas de lecturas de detalle.
  - **Resultado esperado**: Respuesta HTTP 200 en todas las muestras válidas.
  - **Dependencias**: QA-24, QA-25. | **Estado inicial**: `PENDIENTE`.

---

### CP-32: Login Concurrente con Credenciales Únicas
- **CP-32-01 (Rendimiento - Autenticación Concurrente)**
  - **Objetivo**: Validar `/api/v1/auth/login` con 25 usuarios simultáneos consumiendo datos de CSV ficticio sin colisiones ni reutilización indebida de tokens.
  - **Tipo**: Carga / Seguridad (JMeter).
  - **Actor**: 25 usuarios candidatos virtuales (`USR-LOAD-CND-01` a `25`).
  - **Objetivo preliminar**: p95 ≤ 3.0 s; correlación de tokens 100% exitosa.
  - **Pasos**: Disparar logins paralelos con CSV Data Set Config.
  - **Resultado esperado**: 25 tokens únicos emitidos y verificados.
  - **Dependencias**: QA-24, QA-25. | **Estado inicial**: `PENDIENTE`.

- **CP-32-02 (Negativo - Inyección Masiva de Credenciales Inválidas)**
  - **Objetivo**: Comprobar que ráfagas de logins fallidos son rechazadas ordenadamente sin provocar errores 500 ni fugas de memoria.
  - **Tipo**: Resiliencia / Carga (JMeter).
  - **Actor**: Usuarios con credenciales erróneas.
  - **Pasos**: Enviar 50 peticiones simultáneas con passwords incorrectas.
  - **Resultado esperado**: 100% de respuestas con error de cliente (401/422); cero respuestas 500.
  - **Dependencias**: QA-24, QA-25. | **Estado inicial**: `PENDIENTE`.

---

### CP-33: Carga de Postulaciones e Integridad Posterior
- **CP-33-01 (Rendimiento e Integridad Posterior - Postulaciones y Cupos)**
  - **Objetivo**: Ejecutar carga de postulaciones y contrataciones masivas verificando que la base mantenga integridad estricta posterior: **el recuento de contratados nunca puede superar el número de puestos disponibles (`contratados <= numero_vacantes`)**. La igualdad solo se exigirá si el volumen de solicitudes exitosas garantiza cubrir el cupo completo.
  - **Tipo**: Carga / Integridad Transaccional (JMeter + PHPUnit/MySQL).
  - **Actor**: Candidatos y empresas virtuales concurrentes.
  - **Objetivo preliminar**: p95 escrituras ≤ 3.0 s; integridad 100% post-carga.
  - **Pasos**: Ejecutar plan JMX de escrituras y correr script SQL de auditoría de integridad al finalizar.
  - **Resultado esperado**: Ninguna vacante presenta `contratados > numero_vacantes`.
  - **Dependencias**: QA-24, QA-25, QA-26. | **Estado inicial**: `PENDIENTE`.

- **CP-33-02 (Rendimiento - Decisiones Concurrentes en Kanban)**
  - **Objetivo**: Medir estabilidad de `/avanzar`, `/rechazar` y `/contratar` bajo múltiples evaluadores simultáneos.
  - **Tipo**: Carga / Concurrencia (JMeter).
  - **Actor**: Evaluadores concurrentes de `USR-EMP-01`.
  - **Pasos**: Ejecutar movimientos paralelos en el tablero.
  - **Resultado esperado**: Operaciones concurrentes serializadas mediante `lockForUpdate()`; sin deadlocks no manejados.
  - **Dependencias**: QA-24, QA-25. | **Estado inicial**: `PENDIENTE`.

---

### CP-34: Reproducibilidad, CI y Trazabilidad de la Entrega
- **CP-34-01 (CI - Pipeline en Copia Limpia)**
  - **Objetivo**: Ejecutar build, lint y suites automatizadas desde clon limpio en GitHub Actions sin intervención manual.
  - **Tipo**: CI (GitHub Actions).
  - **Actor**: Servidor de integración continua.
  - **Pasos**: Disparar workflow en PR hacia `develop`.
  - **Resultado esperado**: Pipeline finaliza en verde; reportes exportados como artefactos; fallo visible si un test falla.
  - **Dependencias**: QA-27, QA-28, QA-30. | **Estado inicial**: `PENDIENTE`.

- **CP-34-02 (CI - Reproducibilidad de Fixtures y Ausencia de Secretos)**
  - **Objetivo**: Verificar que los seeders corran limpiamente en entorno Docker aislado y que los logs no expongan variables `.env` ni contraseñas.
  - **Tipo**: Seguridad / Reproducibilidad (Shell / Docker).
  - **Actor**: Runner CI / Auditor de configuración.
  - **Pasos**: Ejecutar `db:seed --class=QaSeeder` en contenedor e inspeccionar log de salida.
  - **Resultado esperado**: Éxito sin volcados de contraseñas ni tokens en stdout.
  - **Dependencias**: QA-02, QA-27, QA-30. | **Estado inicial**: `PENDIENTE`.
