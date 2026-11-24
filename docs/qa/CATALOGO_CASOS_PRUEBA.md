# Catálogo Detallado de Casos de Prueba — QA-04

- **Proyecto**: Portal de Empleos CREO+ (PWA y API REST) — Calidad de Software.
- **Responsable**: R3 (@luisvalenzuela25).
- **Revisor asignado**: R1 (@stephany-toribio).
- **Hito**: M0 (Alcance y línea base).
- **Versión**: 1.0.0.
- **Fecha**: 24/11/2026.
- **Rama**: `feature/QA-04-matriz-pruebas`.

---

## 1. Convenciones y estados de prueba

Cada caso de prueba se desglosa desde las 34 familias base (`CP-01` a `CP-34`) utilizando identificadores de escenario (`CP-XX-YY`).
- **Estados posibles**:
  - `PENDIENTE`: Diseñado y documentado, en espera de ejecución en su suite automatizada correspondiente.
  - `EJECUTADO`: Prueba corrida en el ambiente local/QA pero con resultado aún no consolidado.
  - `APROBADO`: Ejecutada exitosamente con aserciones cumplidas y evidencia registrada.
  - `DEFECTUOSO`: Ejecutada con fallos o aserciones no cumplidas (se vincula a un defecto formal Sev1-Sev4).
- **Todos los casos inician en estado `PENDIENTE`** dentro de esta entrega de diseño QA-04.

---

## 2. Bloque 1: Registro y Autenticación (RF-01, RF-02, RNF-01)

### CP-01: Registro de Candidato
- **CP-01-01 (Positivo)**
  - **Objetivo**: Verificar el alta exitosa de un candidato con datos válidos y únicos.
  - **Tipo**: Funcional / Positiva (API Karate & Web Selenium).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: Entorno QA activo; base de datos con catálogos inicializados; correo y DNI no registrados previamente.
  - **Datos**: Correo nuevo `candidato.nuevo@qa.example.test`, DNI `00000099`, celular `900000099`, contraseña válida (`QA_FIXTURE_PASSWORD`).
  - **Pasos**:
    1. Enviar `POST /api/v1/candidatos` con payload de registro válido.
  - **Resultado esperado**: HTTP 201 Created; respuesta contiene objeto candidato con `id` asignado y mensaje de confirmación; token Sanctum si aplica.
  - **Comprobación de persistencia**: Registro en tabla `usuarios` con `rol_id` de candidato y en tabla `candidatos` con DNI correspondiente; password hasheada.
  - **Dependencias**: QA-02, QA-06, QA-15.
  - **Estado inicial**: `PENDIENTE`.

- **CP-01-02 (Negativo - Correo/DNI Duplicado)**
  - **Objetivo**: Impedir el registro cuando el correo o DNI ya existen.
  - **Tipo**: Funcional / Negativa (API Karate).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: Usuario `candidato1@qa.example.test` existente (DNI `00000001`).
  - **Datos**: Correo `candidato1@qa.example.test` o DNI `00000001`.
  - **Pasos**:
    1. Enviar `POST /api/v1/candidatos` con correo o DNI ya existente.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity; error de validación en campo `correo` o `dni`.
  - **Comprobación de persistencia**: El recuento de registros en `usuarios` y `candidatos` no sufre cambios; sin altas parciales.
  - **Dependencias**: QA-02, QA-15.
  - **Estado inicial**: `PENDIENTE`.

- **CP-01-03 (Negativo - Formato Inválido)**
  - **Objetivo**: Validar el rechazo de DNI con caracteres no numéricos o celular inválido.
  - **Tipo**: Funcional / Negativa (API Karate & Web Selenium).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: Ninguna.
  - **Datos**: DNI `ABC12345`, celular `123`.
  - **Pasos**:
    1. Enviar `POST /api/v1/candidatos` con campos malformados.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity; mensajes específicos por campo.
  - **Comprobación de persistencia**: Sin registros en base de datos.
  - **Dependencias**: QA-06, QA-15.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-02: Registro de Empresa
- **CP-02-01 (Positivo)**
  - **Objetivo**: Verificar el alta de una empresa con RUC válido y razón social.
  - **Tipo**: Funcional / Positiva (API Karate & Web Selenium).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: RUC `20000000099` y correo no registrados.
  - **Datos**: `empresa.nueva@qa.example.test`, RUC `20000000099`, Razón Social `Empresa Nueva QA SAC`.
  - **Pasos**:
    1. Enviar `POST /api/v1/empresas` con payload completo.
  - **Resultado esperado**: HTTP 201 Created; registro persistido con rol empresa.
  - **Comprobación de persistencia**: Fila en tabla `empresas` vinculada a `usuarios`.
  - **Dependencias**: QA-02, QA-06, QA-15.
  - **Estado inicial**: `PENDIENTE`.

- **CP-02-02 (Negativo - RUC Duplicado)**
  - **Objetivo**: Rechazar duplicidad de RUC en base de datos.
  - **Tipo**: Funcional / Negativa (API Karate).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: `empresa1@qa.example.test` existente con RUC `20000000001`.
  - **Datos**: RUC `20000000001` con diferente correo.
  - **Pasos**:
    1. Enviar `POST /api/v1/empresas` con RUC duplicado.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity; error en atributo `ruc`.
  - **Comprobación de persistencia**: No se crea nueva empresa.
  - **Dependencias**: QA-15.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-03: Inicio de Sesión de los Tres Roles
- **CP-03-01 (Positivo - Candidato)**
  - **Objetivo**: Autenticar candidato y obtener token Bearer Sanctum.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium, Appium).
  - **Actor**: Candidato activo (`candidato1@qa.example.test`).
  - **Precondiciones**: Cuenta activa preparada en `QaSeeder`.
  - **Datos**: Correo `candidato1@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**:
    1. Enviar `POST /api/v1/auth/login` con credenciales.
  - **Resultado esperado**: HTTP 200 OK; token plano devuelto en payload; rol devuelto es `candidato`.
  - **Comprobación de persistencia**: Fila en `personal_access_tokens` vinculada al usuario.
  - **Dependencias**: QA-05, QA-15, QA-19, QA-22.
  - **Estado inicial**: `PENDIENTE`.

- **CP-03-02 (Positivo - Empresa)**
  - **Objetivo**: Autenticar empresa y comprobar redirección a dashboard corporativo.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Empresa activa (`empresa1@qa.example.test`).
  - **Precondiciones**: Cuenta empresa activa en `QaSeeder`.
  - **Datos**: `empresa1@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**:
    1. `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 200 OK; rol `empresa`; token Sanctum válido.
  - **Comprobación de persistencia**: Token en DB; redirección UI autorizada.
  - **Dependencias**: QA-05, QA-15, QA-20.
  - **Estado inicial**: `PENDIENTE`.

- **CP-03-03 (Positivo - Administrador)**
  - **Objetivo**: Autenticar administrador para moderación de vacantes.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Administrador (`admin@qa.example.test`).
  - **Precondiciones**: Cuenta admin activa en `QaSeeder`.
  - **Datos**: `admin@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**:
    1. `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 200 OK; rol `admin`.
  - **Comprobación de persistencia**: Token generado en `personal_access_tokens`.
  - **Dependencias**: QA-05, QA-15, QA-20.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-04: Credenciales Incorrectas, Bloqueo y Suspensión
- **CP-04-01 (Negativo - Password Errónea)**
  - **Objetivo**: Rechazar login ante contraseña errónea e incrementar contador de fallos.
  - **Tipo**: Seguridad / Negativa (API Karate, Selenium).
  - **Actor**: Candidato.
  - **Precondiciones**: Usuario `candidato1@qa.example.test` activo con `intentos_fallidos = 0`.
  - **Datos**: Correo `candidato1@qa.example.test`, password `PasswordErronea123!`.
  - **Pasos**:
    1. `POST /api/v1/auth/login` con password errónea.
  - **Resultado esperado**: HTTP 401 Unauthorized o 422 según contrato; mensaje de error contractual.
  - **Comprobación de persistencia**: Campo `intentos_fallidos` se incrementa en tabla `usuarios`.
  - **Dependencias**: QA-05, QA-15.
  - **Estado inicial**: `PENDIENTE`.

- **CP-04-02 (Negativo - Cuenta Bloqueada por Intentos)**
  - **Objetivo**: Impedir login cuando se alcanza el umbral de intentos fallidos.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: Candidato con intentos excedidos.
  - **Precondiciones**: Usuario con `bloqueado_hasta` mayor a la hora actual.
  - **Datos**: Credenciales correctas de usuario bloqueado temporalmente.
  - **Pasos**:
    1. `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 403 Forbidden o 423 Locked; mensaje contractual indicando bloqueo temporal.
  - **Comprobación de persistencia**: `bloqueado_hasta` permanece intacto.
  - **Dependencias**: QA-05, QA-15.
  - **Estado inicial**: `PENDIENTE`.

- **CP-04-03 (Negativo - Cuenta Suspendida)**
  - **Objetivo**: Rechazar acceso a usuario con estado administrativo `suspendido`.
  - **Tipo**: Seguridad / Negativa (API Karate, Selenium).
  - **Actor**: Candidato suspendido (`suspendido@qa.example.test`).
  - **Precondiciones**: Usuario configurado con `estado_cuenta = 'suspendido'` en `QaSeeder`.
  - **Datos**: `suspendido@qa.example.test`, contraseña `QA_FIXTURE_PASSWORD`.
  - **Pasos**:
    1. `POST /api/v1/auth/login`.
  - **Resultado esperado**: HTTP 403 Forbidden; mensaje contractual indicando suspensión de la cuenta.
  - **Comprobación de persistencia**: No se emite token en `personal_access_tokens`.
  - **Dependencias**: QA-05, QA-15.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-05: Cierre de Sesión y Revocación de Tokens
- **CP-05-01 (Positivo - Logout)**
  - **Objetivo**: Cerrar sesión válidamente y revocar el token activo.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium, Appium).
  - **Actor**: Candidato autenticado.
  - **Precondiciones**: Sesión activa con token Bearer válido.
  - **Datos**: Token de `candidato1@qa.example.test`.
  - **Pasos**:
    1. Enviar `POST /api/v1/auth/logout` con header `Authorization: Bearer <token>`.
  - **Resultado esperado**: HTTP 200 OK; mensaje de logout exitoso.
  - **Comprobación de persistencia**: El token actual es eliminado de `personal_access_tokens`.
  - **Dependencias**: QA-05, QA-15, QA-23.
  - **Estado inicial**: `PENDIENTE`.

- **CP-05-02 (Negativo - Reutilización de Token Revocado)**
  - **Objetivo**: Verificar que un token ya revocado no permite invocar rutas protegidas.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: Candidato deslogueado.
  - **Precondiciones**: Token utilizado previamente en CP-05-01.
  - **Datos**: Token revocado en header `Authorization`.
  - **Pasos**:
    1. Enviar `GET /api/v1/auth/me` con el token revocado.
  - **Resultado esperado**: HTTP 401 Unauthorized; token rechazado por Sanctum.
  - **Comprobación de persistencia**: Sin cambios en base de datos.
  - **Dependencias**: QA-05, QA-15.
  - **Estado inicial**: `PENDIENTE`.

---

## 3. Bloque 2: Perfiles, Vacantes, Moderación y Pagos (RF-03, RF-04, RF-05, RNF-01)

### CP-06: Perfil Propio y Protección de Recursos Ajenos
- **CP-06-01 (Positivo - Consulta de Perfil Propio)**
  - **Objetivo**: Obtener datos del perfil propio autenticado.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: `candidato1@qa.example.test`.
  - **Precondiciones**: Candidato autenticado.
  - **Datos**: Token válido de candidato 1.
  - **Pasos**:
    1. `GET /api/v1/candidatos/me`.
  - **Resultado esperado**: HTTP 200 OK; devuelve nombre, apellidos, DNI y habilidades propias.
  - **Comprobación de persistencia**: Consistente con registros en `candidatos`.
  - **Dependencias**: QA-05, QA-06.
  - **Estado inicial**: `PENDIENTE`.

- **CP-06-02 (Positivo - Edición de Perfil Propio)**
  - **Objetivo**: Actualizar celular y experiencia propia.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: `candidato1@qa.example.test`.
  - **Precondiciones**: Token activo.
  - **Datos**: Celular nuevo `900000888`.
  - **Pasos**:
    1. `PUT /api/v1/candidatos/me` con nuevos datos.
  - **Resultado esperado**: HTTP 200 OK; datos actualizados devueltos.
  - **Comprobación de persistencia**: `candidatos.celular` actualizado a `900000888`.
  - **Dependencias**: QA-05, QA-06.
  - **Estado inicial**: `PENDIENTE`.

- **CP-06-03 (Negativo - Empresa Accede a Perfil de Candidato sin Postulación)**
  - **Objetivo**: Impedir que una empresa consulte el perfil de un candidato que no ha postulado a sus vacantes.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: `empresa1@qa.example.test`.
  - **Precondiciones**: Postulación inexistente o de otra empresa.
  - **Datos**: `id` de postulación no perteneciente a vacantes de empresa 1.
  - **Pasos**:
    1. `GET /api/v1/empresas/me/postulaciones/{postulacion}/perfil`.
  - **Resultado esperado**: HTTP 403 Forbidden o 404 Not Found.
  - **Comprobación de persistencia**: Ninguna mutación.
  - **Dependencias**: QA-05, QA-17.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-07: Registro y Gestión de Vacantes Propias
- **CP-07-01 (Positivo - Creación de Vacante)**
  - **Objetivo**: Registrar vacante con campos obligatorios, fechas de vigencia y puestos.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: `empresa1@qa.example.test`.
  - **Precondiciones**: Empresa activa autenticada.
  - **Datos**: Título `Desarrollador Full Stack QA`, `numero_vacantes = 2`, `salario_min = 3000`, `salario_max = 4500`, fechas vigentes.
  - **Pasos**:
    1. `POST /api/v1/empresas/me/vacantes` con payload válido.
  - **Resultado esperado**: HTTP 201 Created; estado inicial de vacante `activa` para la empresa, pero sin aprobación ni pago (no pública).
  - **Comprobación de persistencia**: Fila en tabla `empleos` con `empresa_id` de empresa 1; sin registros en `revisiones_vacante` ni `pagos_vacante`.
  - **Dependencias**: QA-07, QA-16, QA-20.
  - **Estado inicial**: `PENDIENTE`.

- **CP-07-02 (Negativo - Salario Mínimo Mayor a Máximo)**
  - **Objetivo**: Validar regla de integridad en rango salarial.
  - **Tipo**: Validación / Negativa (API Karate).
  - **Actor**: `empresa1@qa.example.test`.
  - **Precondiciones**: Token empresa válido.
  - **Datos**: `salario_min = 5000`, `salario_max = 3000`.
  - **Pasos**:
    1. `POST /api/v1/empresas/me/vacantes`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity; error en validación de salarios (`chk_empleos_salarios`).
  - **Comprobación de persistencia**: No se crea vacante.
  - **Dependencias**: QA-07, QA-16.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-08: Aprobación y Moderación Administrativa
- **CP-08-01 (Positivo - Administrador Aprueba Vacante)**
  - **Objetivo**: Aprobar vacante registrada para habilitar fase de pago simulado.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: `admin@qa.example.test`.
  - **Precondiciones**: Vacante registrada pendiente de revisión.
  - **Datos**: `id` de vacante registrada.
  - **Pasos**:
    1. `PATCH /api/v1/admin/vacantes/{vacante}/aprobar`.
  - **Resultado esperado**: HTTP 200 OK; decisión `aprobada`.
  - **Comprobación de persistencia**: Registro en `revisiones_vacante` con `decision = 'aprobada'` y `admin_id`.
  - **Dependencias**: QA-07, QA-16, QA-20.
  - **Estado inicial**: `PENDIENTE`.

- **CP-08-02 (Positivo - Administrador No Aprueba con Motivo)**
  - **Objetivo**: Registrar rechazo administrativo con motivo explícito.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: `admin@qa.example.test`.
  - **Precondiciones**: Vacante pendiente.
  - **Datos**: `motivo = 'Descripción insuficiente de funciones'`.
  - **Pasos**:
    1. `PATCH /api/v1/admin/vacantes/{vacante}/no-aprobar` con payload `{ "motivo": "..." }`.
  - **Resultado esperado**: HTTP 200 OK; decisión `no_aprobada`.
  - **Comprobación de persistencia**: Registro en `revisiones_vacante` con `decision = 'no_aprobada'` y motivo persistente.
  - **Dependencias**: QA-07, QA-16.
  - **Estado inicial**: `PENDIENTE`.

- **CP-08-03 (Negativo - No Aprobar sin Motivo)**
  - **Objetivo**: Verificar restricción de check MySQL que exige motivo al no aprobar.
  - **Tipo**: Validación / Negativa (API Karate).
  - **Actor**: `admin@qa.example.test`.
  - **Precondiciones**: Vacante pendiente.
  - **Datos**: Payload con `motivo = ''` o nulo.
  - **Pasos**:
    1. `PATCH /api/v1/admin/vacantes/{vacante}/no-aprobar`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: No se inserta registro en `revisiones_vacante`.
  - **Dependencias**: QA-07, QA-16.
  - **Estado inicial**: `PENDIENTE`.

- **CP-08-04 (Negativo - Empresa Intenta Aprobar Vacante)**
  - **Objetivo**: Bloquear acceso de roles no autorizados al endpoint administrativo.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: `empresa1@qa.example.test`.
  - **Precondiciones**: Token de rol empresa.
  - **Datos**: Endpoint `/api/v1/admin/vacantes/{vacante}/aprobar`.
  - **Pasos**:
    1. Enviar PATCH con token de empresa.
  - **Resultado esperado**: HTTP 403 Forbidden; autorización de servidor inviolable.
  - **Comprobación de persistencia**: Sin cambios.
  - **Dependencias**: QA-05, QA-17.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-09: Pago Simulado de Vacante Aprobada
- **CP-09-01 (Positivo - Pago Exitoso)**
  - **Objetivo**: Completar pago simulado con tarjeta de prueba y publicar la oferta.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: `empresa1@qa.example.test` (propietaria de la vacante).
  - **Precondiciones**: Vacante con revisión aprobada en `revisiones_vacante`.
  - **Datos**: Tarjeta de prueba válida, monto acorde al tarifario.
  - **Pasos**:
    1. `POST /api/v1/empresas/me/vacantes/{vacante}/pago` con payload de tarjeta simulada.
  - **Resultado esperado**: HTTP 201 Created o 200 OK; resultado `exitoso`; vacante pasa a estar publicada.
  - **Comprobación de persistencia**: Registro en `pagos_vacante` con `resultado = 'exitoso'`, `monto` y `tarjeta_ultimos4`.
  - **Dependencias**: QA-07, QA-16, QA-20.
  - **Estado inicial**: `PENDIENTE`.

- **CP-09-02 (Negativo - Pago de Vacante No Aprobada)**
  - **Objetivo**: Impedir el pago de una vacante no aprobada o aún pendiente.
  - **Tipo**: Lógica de Negocio / Negativa (API Karate).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Vacante en estado pendiente o `no_aprobada`.
  - **Datos**: Intento de pago simulado.
  - **Pasos**:
    1. `POST /api/v1/empresas/me/vacantes/{vacante}/pago`.
  - **Resultado esperado**: HTTP 409 Conflict o 422 según contrato; pago denegado.
  - **Comprobación de persistencia**: No se registra pago exitoso.
  - **Dependencias**: QA-07, QA-17.
  - **Estado inicial**: `PENDIENTE`.

- **CP-09-03 (Negativo - Pago por Empresa Ajena)**
  - **Objetivo**: Impedir que una empresa pague la vacante de otra empresa.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: `empresa2@qa.example.test`.
  - **Precondiciones**: Vacante perteneciente a `empresa1@qa.example.test`.
  - **Datos**: Token de empresa 2 invocando vacante de empresa 1.
  - **Pasos**:
    1. `POST /api/v1/empresas/me/vacantes/{vacante_empresa1}/pago`.
  - **Resultado esperado**: HTTP 403 Forbidden o 404 Not Found (`RESOURCE_NOT_FOUND` / `FORBIDDEN`).
  - **Comprobación de persistencia**: Sin mutaciones.
  - **Dependencias**: QA-05, QA-17.
  - **Estado inicial**: `PENDIENTE`.

---

## 4. Bloque 3: Búsqueda, Disponibilidad y Postulación (RF-06, RF-07, RNF-02)

### CP-10: Reglas de Disponibilidad de Vacantes
- **CP-10-01 (Negativo - Vacante No Disponible No Admite Postulación)**
  - **Objetivo**: Verificar que una vacante pausada, finalizada, vencida o sin pagar rechace nuevas postulaciones con 404 `VACANCY_NOT_AVAILABLE`.
  - **Tipo**: Lógica de Negocio / Negativa (API Karate, PHPUnit/MySQL).
  - **Actor**: Candidato autenticado.
  - **Precondiciones**: Vacante con `estado_empleo = 'pausada'` o fecha fin vigencia expirada.
  - **Datos**: `vacante_id` de oferta pausada.
  - **Pasos**:
    1. `POST /api/v1/candidatos/me/postulaciones` con body `{ "vacante_id": <id_pausada> }`.
  - **Resultado esperado**: HTTP 404 Not Found; `code = 'VACANCY_NOT_AVAILABLE'`.
  - **Comprobación de persistencia**: Fila de postulación no creada.
  - **Dependencias**: QA-07, QA-08, QA-17.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-11: Búsqueda, Filtros y Detalle de Vacante Disponible
- **CP-11-01 (Positivo - Búsqueda con Filtros)**
  - **Objetivo**: Filtrar vacantes disponibles por área de estudio y modalidad.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium, Appium).
  - **Actor**: Candidato autenticado.
  - **Precondiciones**: Vacantes publicadas y disponibles en base de datos.
  - **Datos**: `GET /api/v1/vacantes?modalidad=remoto`.
  - **Pasos**:
    1. Enviar GET con parámetros de consulta.
  - **Resultado esperado**: HTTP 200 OK; listado incluye únicamente vacantes con las 7 condiciones de disponibilidad activas.
  - **Comprobación de persistencia**: Solo lectura.
  - **Dependencias**: QA-07, QA-11, QA-19.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-12: Primera Postulación Persistente
- **CP-12-01 (Positivo - Alta en Recibido)**
  - **Objetivo**: Registrar la primera postulación de un candidato a una vacante disponible.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium, Appium).
  - **Actor**: `candidato1@qa.example.test`.
  - **Precondiciones**: Vacante publicada y disponible; candidato sin postulación previa en dicha vacante.
  - **Datos**: Body `{ "vacante_id": <id_disponible> }`.
  - **Pasos**:
    1. `POST /api/v1/candidatos/me/postulaciones`.
  - **Resultado esperado**: HTTP 201 Created; postulación creada con estado `Recibido` (`estado_postulacion_id = 1`); candidato deducido del token (no del body).
  - **Comprobación de persistencia**: Fila en tabla `postulaciones` con `candidato_id`, `empleo_id`, `estado_postulacion_id` y `retirada_en = null`.
  - **Dependencias**: QA-08, QA-16, QA-19, QA-22.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-13: Postulación Duplicada y Concurrencia del Mismo Candidato
- **CP-13-01 (Negativo - Duplicado Secuencial)**
  - **Objetivo**: Rechazar un segundo intento secuencial de postulación del mismo candidato al mismo empleo.
  - **Tipo**: Integridad / Negativa (API Karate).
  - **Actor**: `candidato1@qa.example.test`.
  - **Precondiciones**: CP-12-01 ejecutado previamente.
  - **Datos**: Mismo `vacante_id`.
  - **Pasos**:
    1. `POST /api/v1/candidatos/me/postulaciones` con la misma vacante.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'DUPLICATE_APPLICATION'`.
  - **Comprobación de persistencia**: Exactamente 1 registro en tabla `postulaciones`; sin filas duplicadas.
  - **Dependencias**: QA-08, QA-17.
  - **Estado inicial**: `PENDIENTE`.

- **CP-13-02 (Negativo - Concurrencia / Condición de Carrera del Mismo Candidato)**
  - **Objetivo**: Demostrar que 2 requests simultáneos del mismo candidato resultan en una sola inserción y una respuesta 409.
  - **Tipo**: Concurrencia / Integridad (JMeter, PHPUnit/MySQL).
  - **Actor**: `candidato1@qa.example.test`.
  - **Precondiciones**: Candidato sin postulación previa; vacante disponible.
  - **Datos**: 2 hilos paralelos disparando `POST /api/v1/candidatos/me/postulaciones`.
  - **Pasos**:
    1. Disparar requests concurrentes exactos con sincronización de hilos.
  - **Resultado esperado**: 1 request responde HTTP 201 Created; el otro responde HTTP 409 Conflict con `DUPLICATE_APPLICATION`.
  - **Comprobación de persistencia**: Restricción `unique(['candidato_id', 'empleo_id'])` respetada; recuento en DB = 1.
  - **Dependencias**: QA-08, QA-25.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-14: Postulaciones Simultáneas de Candidatos Diferentes
- **CP-14-01 (Positivo - Admisión Simultánea)**
  - **Objetivo**: Comprobar que múltiples candidatos distintos pueden postular concurrentemente mientras la oferta esté disponible (el cupo limita contrataciones, no postulaciones).
  - **Tipo**: Concurrencia / Rendimiento (JMeter, PHPUnit/MySQL).
  - **Actor**: `candidato1@qa.example.test`, `candidato2@qa.example.test`, ..., `candidatoN`.
  - **Precondiciones**: Vacante con `numero_vacantes = 1` pero disponible.
  - **Datos**: 10 candidatos distintos postulando en el mismo segundo.
  - **Pasos**:
    1. Ejecutar grupo de hilos en JMeter con credenciales parametrizadas.
  - **Resultado esperado**: Todos los candidatos obtienen HTTP 201 Created; ninguno es bloqueado por el cupo de vacantes.
  - **Comprobación de persistencia**: 10 filas creadas en `postulaciones`.
  - **Dependencias**: QA-08, QA-25.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-15: Autorización de Servidor y Protección de Identidad
- **CP-15-01 (Negativo - Token Ausente)**
  - **Objetivo**: Bloquear endpoints privados cuando no se envía header `Authorization`.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: Visitante anónimo.
  - **Precondiciones**: Ninguna.
  - **Datos**: `GET /api/v1/candidatos/me/postulaciones` sin header.
  - **Pasos**:
    1. Enviar request sin token.
  - **Resultado esperado**: HTTP 401 Unauthorized.
  - **Comprobación de persistencia**: Sin cambios.
  - **Dependencias**: QA-05, QA-17.
  - **Estado inicial**: `PENDIENTE`.

- **CP-15-02 (Negativo - Candidato Invoca Endpoint de Empresa)**
  - **Objetivo**: Impedir que un candidato acceda a rutas restringidas a rol empresa.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: Candidato autenticado.
  - **Precondiciones**: Token de rol candidato.
  - **Datos**: `GET /api/v1/empresas/me/vacantes`.
  - **Pasos**:
    1. Enviar GET con token de candidato.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`).
  - **Comprobación de persistencia**: Sin cambios.
  - **Dependencias**: QA-05, QA-17.
  - **Estado inicial**: `PENDIENTE`.

---

## 5. Bloque 4: Selección, Decisiones, Retiro e Historial (RF-08, RF-09, RF-10, RNF-02)

### CP-16: Flujo Completo de Transiciones Permitidas
- **CP-16-01 (Positivo - Recibido a En Revisión)**
  - **Objetivo**: Empresa avanza postulación de `Recibido` a `En Revisión`.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Postulación en `Recibido`.
  - **Datos**: `PATCH /api/v1/empresas/me/vacantes/{vacante}/postulaciones/{postulacion}/avanzar`.
  - **Pasos**:
    1. Ejecutar PATCH de avance.
  - **Resultado esperado**: HTTP 200 OK; estado resultante `En Revisión`.
  - **Comprobación de persistencia**: `postulaciones.estado_postulacion_id = 2`; fila registrada en `historial_postulaciones`.
  - **Dependencias**: QA-09, QA-16, QA-20.
  - **Estado inicial**: `PENDIENTE`.

- **CP-16-02 (Positivo - En Revisión a Entrevista)**
  - **Objetivo**: Empresa avanza postulación de `En Revisión` a `Entrevista`.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Postulación en `En Revisión`.
  - **Pasos**:
    1. `PATCH .../avanzar`.
  - **Resultado esperado**: HTTP 200 OK; estado `Entrevista` (`id = 3`).
  - **Comprobación de persistencia**: Actualización de estado y nuevo movimiento en historial.
  - **Dependencias**: QA-09, QA-16.
  - **Estado inicial**: `PENDIENTE`.

- **CP-16-03 (Positivo - Entrevista a Contratado con Cupo Disponible)**
  - **Objetivo**: Contratar postulante en entrevista si `contratados < numero_vacantes`.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Postulación en `Entrevista`; vacante con puestos disponibles.
  - **Pasos**:
    1. `PATCH .../contratar`.
  - **Resultado esperado**: HTTP 200 OK; estado `Contratado` (`id = 4`).
  - **Comprobación de persistencia**: Estado final persistido; historial registrado; contador de contratados incrementado.
  - **Dependencias**: QA-09, QA-16, QA-20.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-17: Rechazo con Motivo Válido
- **CP-17-01 (Positivo - Rechazo desde En Revisión)**
  - **Objetivo**: Rechazar postulante indicando motivo del catálogo.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Postulación en `En Revisión`.
  - **Datos**: Body `{ "motivo_rechazo_id": 1 }` ('Perfil no se ajusta').
  - **Pasos**:
    1. `PATCH .../rechazar` con `motivo_rechazo_id`.
  - **Resultado esperado**: HTTP 200 OK; estado `Rechazado`.
  - **Comprobación de persistencia**: `postulaciones.estado_postulacion_id = 5`; en `historial_postulaciones` se guarda `motivo_rechazo_id = 1`.
  - **Dependencias**: QA-09, QA-16.
  - **Estado inicial**: `PENDIENTE`.

- **CP-17-03 (Negativo - Rechazo sin Motivo)**
  - **Objetivo**: Impedir rechazo sin motivo válido.
  - **Tipo**: Validación / Negativa (API Karate).
  - **Actor**: Empresa propietaria.
  - **Datos**: Body vacío o con ID inexistente.
  - **Pasos**:
    1. `PATCH .../rechazar` sin `motivo_rechazo_id`.
  - **Resultado esperado**: HTTP 422 Unprocessable Entity.
  - **Comprobación de persistencia**: Estado anterior intacto; sin cambios en historial.
  - **Dependencias**: QA-09, QA-16.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-18: Saltos de Estado Inválidos y Operaciones en Estados Finales
- **CP-18-01 (Negativo - Salto Directo de Recibido a Contratado)**
  - **Objetivo**: Rechazar transición no permitida según la máquina de estados.
  - **Tipo**: Integridad / Negativa (API Karate).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Postulación en `Recibido`.
  - **Pasos**:
    1. Enviar `PATCH .../contratar` sobre postulación en `Recibido`.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'INVALID_TRANSITION'`.
  - **Comprobación de persistencia**: Estado sigue en `Recibido`; sin registros espurios en historial.
  - **Dependencias**: QA-09, QA-17.
  - **Estado inicial**: `PENDIENTE`.

- **CP-18-02 (Negativo - Modificar Postulación en Estado Final Contratado/Rechazado)**
  - **Objetivo**: Bloquear cualquier acción sobre postulaciones que ya alcanzaron estado final.
  - **Tipo**: Integridad / Negativa (API Karate).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Postulación en `Contratado`.
  - **Pasos**:
    1. Enviar `PATCH .../avanzar` o `PATCH .../rechazar`.
  - **Resultado esperado**: HTTP 409 Conflict; `INVALID_TRANSITION`.
  - **Comprobación de persistencia**: Estado permanece en `Contratado`.
  - **Dependencias**: QA-09, QA-17.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-19: Contratación Concurrente y Límite de Puestos
- **CP-19-01 (Negativo - Sobrecontratación Concurrente al Último Puesto)**
  - **Objetivo**: Demostrar que ante 2 contrataciones simultáneas para el único cupo disponible, solo 1 prospera y la otra se rechaza con 409.
  - **Tipo**: Concurrencia / Integridad (JMeter, PHPUnit/MySQL).
  - **Actor**: Empresa propietaria con 2 evaluadores concurrentes.
  - **Precondiciones**: Vacante con `numero_vacantes = 1`; 2 postulaciones en etapa `Entrevista`.
  - **Datos**: Requests simultáneos de `PATCH .../contratar` para la postulación A y la postulación B.
  - **Pasos**:
    1. Disparar requests concurrentes con sincronización estricta.
  - **Resultado esperado**: 1 request retorna HTTP 200 OK (`Contratado`); el otro retorna HTTP 409 Conflict con `code = 'HIRING_LIMIT_REACHED'`.
  - **Comprobación de persistencia**: Recuento de postulaciones con estado `Contratado` en la vacante es exactamente 1; nunca se excede `numero_vacantes`.
  - **Dependencias**: QA-09, QA-25.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-20: Retiro de Postulación
- **CP-20-01 (Positivo - Retiro por Candidato Propietario en Recibido/Revisión/Entrevista)**
  - **Objetivo**: Permitir al candidato propietario retirarse conservando el último estado y marcando fecha de retiro.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Candidato propietario de la postulación.
  - **Precondiciones**: Postulación en etapa `En Revisión` con `retirada_en = null`.
  - **Pasos**:
    1. `PATCH /api/v1/candidatos/me/postulaciones/{postulacion}/retirar`.
  - **Resultado esperado**: HTTP 200 OK; postulación queda marcada como retirada; estado de selección se preserva (`En Revisión`).
  - **Comprobación de persistencia**: `postulaciones.retirada_en IS NOT NULL`; `estado_postulacion_id` conserva el valor previo.
  - **Dependencias**: QA-08, QA-17, QA-19.
  - **Estado inicial**: `PENDIENTE`.

- **CP-20-02 (Negativo - Retiro por Candidato Ajeno)**
  - **Objetivo**: Impedir que un candidato retire la postulación de otro candidato.
  - **Tipo**: Seguridad / Negativa (API Karate).
  - **Actor**: `candidato2@qa.example.test`.
  - **Precondiciones**: Postulación perteneciente a `candidato1@qa.example.test`.
  - **Pasos**:
    1. Candidato 2 envía PATCH de retiro con ID de postulación de Candidato 1.
  - **Resultado esperado**: HTTP 403 Forbidden (`FORBIDDEN`).
  - **Comprobación de persistencia**: `retirada_en` permanece en NULL.
  - **Dependencias**: QA-05, QA-17.
  - **Estado inicial**: `PENDIENTE`.

- **CP-20-03 (Negativo - Intento de Repostular tras Retiro)**
  - **Objetivo**: Confirmar que el retiro no borra la fila ni habilita repostular a la misma vacante.
  - **Tipo**: Integridad / Negativa (API Karate).
  - **Actor**: Candidato que se retiró previamente.
  - **Precondiciones**: CP-20-01 ejecutado.
  - **Pasos**:
    1. `POST /api/v1/candidatos/me/postulaciones` con el mismo `vacante_id`.
  - **Resultado esperado**: HTTP 409 Conflict (`DUPLICATE_APPLICATION`).
  - **Comprobación de persistencia**: No se genera nueva postulación.
  - **Dependencias**: QA-08, QA-17.
  - **Estado inicial**: `PENDIENTE`.

- **CP-20-04 (Negativo - Retiro en Estado Final Contratado)**
  - **Objetivo**: Impedir el retiro de una postulación ya contratada o rechazada.
  - **Tipo**: Lógica de Negocio / Negativa (API Karate).
  - **Actor**: Candidato contratado.
  - **Precondiciones**: Postulación en estado `Contratado`.
  - **Pasos**:
    1. PATCH de retiro.
  - **Resultado esperado**: HTTP 409 Conflict; `code = 'APPLICATION_NOT_WITHDRAWABLE'`.
  - **Comprobación de persistencia**: `retirada_en` permanece en NULL.
  - **Dependencias**: QA-08, QA-17.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-21: Seguimiento Propio y Consulta Histórica
- **CP-21-01 (Positivo - Mis Postulaciones)**
  - **Objetivo**: Candidato consulta sus postulaciones y el estado actual de cada una.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Candidato autenticado.
  - **Pasos**:
    1. `GET /api/v1/candidatos/me/postulaciones`.
  - **Resultado esperado**: HTTP 200 OK; arreglo de postulaciones con título de vacante, estado, si está retirada y fecha.
  - **Comprobación de persistencia**: Solo lectura.
  - **Dependencias**: QA-08, QA-09, QA-16.
  - **Estado inicial**: `PENDIENTE`.

- **CP-21-02 (Positivo - Consulta de Vacante Histórica Pausada o Eliminada)**
  - **Objetivo**: Candidato que ya postuló puede consultar el detalle de su vacante aunque haya sido pausada o eliminada por la empresa.
  - **Tipo**: Funcional / Positiva (API Karate, Selenium).
  - **Actor**: Candidato con postulación activa.
  - **Precondiciones**: Vacante en soft delete o pausada tras haber recibido la postulación.
  - **Pasos**:
    1. `GET /api/v1/candidatos/me/postulaciones/{postulacion}/vacante`.
  - **Resultado esperado**: HTTP 200 OK; datos históricos de la vacante visibles para el postulante.
  - **Comprobación de persistencia**: Solo lectura con `withTrashed()`.
  - **Dependencias**: QA-08, QA-16.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-22: Historial de Postulaciones y Kanban
- **CP-22-01 (Positivo - Consulta de Historial Completo por la Empresa)**
  - **Objetivo**: Verificar que la empresa puede auditar toda la cronología de movimientos, fechas y motivos de rechazo.
  - **Tipo**: Auditoría / Positiva (API Karate, Selenium).
  - **Actor**: Empresa propietaria.
  - **Precondiciones**: Postulaciones con transiciones previas.
  - **Pasos**:
    1. `GET /api/v1/empresas/me/vacantes/{vacante}/postulaciones/historial`.
  - **Resultado esperado**: HTTP 200 OK; listado incluye postulaciones activas y retiradas, con arreglo `movimientos` ordenado por `fecha_cambio`.
  - **Comprobación de persistencia**: Correspondencia exacta con tabla `historial_postulaciones`.
  - **Dependencias**: QA-09, QA-16, QA-20.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-23: Recorrido Integrado E2E Web
- **CP-23-01 (Positivo - Recorrido Completo de los Tres Roles en Web)**
  - **Objetivo**: Ejecutar flujo integrado en navegador web: Empresa registra vacante -> Admin aprueba -> Empresa paga simulado -> Candidato busca y postula -> Empresa entrevista y contrata -> Ambos verifican seguimiento e historial.
  - **Tipo**: E2E / Integrada (Selenium).
  - **Actor**: Los 3 roles coordinados.
  - **Precondiciones**: Frontend compilado; API y base QA aisladas activas.
  - **Pasos**:
    1. Recorrido automatizado paso a paso mediante Page Objects.
  - **Resultado esperado**: Todo el recorrido concluye exitosamente sin errores en consola y con persistencia confirmada en base de datos.
  - **Dependencias**: QA-19, QA-20, QA-30.
  - **Estado inicial**: `PENDIENTE`.

---

## 6. Bloque 5: PWA Móvil Android (PWA-01 a PWA-04)

### CP-24: Manifiesto e Instalación Standalone en Android
- **CP-24-01 (Positivo - Instalación y Lanzador)**
  - **Objetivo**: Comprobar que la PWA es instalable en Android con manifest válido (iconos 192/512, standalone, start_url HTTPS).
  - **Tipo**: PWA / Dispositivo (Appium).
  - **Actor**: Candidato móvil.
  - **Precondiciones**: PWA servida bajo HTTPS; dispositivo/emulador Android conectado.
  - **Pasos**:
    1. Instalar PWA y abrirla desde el icono del lanzador Android.
  - **Resultado esperado**: Apertura en modo `standalone` sin barra de navegación del navegador externo; contexto webview accesible por Appium.
  - **Comprobación de persistencia**: Modo de visualización standalone activo.
  - **Dependencias**: QA-10, QA-21, QA-22.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-25: Diseño Móvil, Teclado Virtual y Accesibilidad
- **CP-25-01 (Positivo - Usabilidad en Resoluciones Móviles)**
  - **Objetivo**: Verificar que los formularios de postulación y login sean operables en pantallas de 360×800 y 412×915 sin desbordamientos horizontales ni botones ocultos tras el teclado virtual.
  - **Tipo**: Usabilidad Móvil (Appium, Selenium Mobile Emulation).
  - **Actor**: Candidato móvil.
  - **Pasos**:
    1. Abrir pantalla de login y búsqueda en viewports móviles; activar teclado virtual en campos de entrada.
  - **Resultado esperado**: Botones de acción visibles y accesibles; selectores de prueba interactuables.
  - **Dependencias**: QA-12, QA-22.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-26: Apertura sin Conexión y Privacidad de Caché
- **CP-26-01 (Positivo - Vista Offline Honesta)**
  - **Objetivo**: Al perder conexión de red, la PWA debe presentar una vista offline explicativa en lugar de una pantalla en blanco o error de red nativo.
  - **Tipo**: PWA / Resiliencia (Appium con corte de red).
  - **Actor**: Candidato móvil sin internet.
  - **Precondiciones**: PWA visitada previamente en línea para instalar el Service Worker.
  - **Pasos**:
    1. Activar modo avión en el dispositivo/emulador.
    2. Recargar o abrir la PWA.
  - **Resultado esperado**: Shell de la aplicación y vista offline amigable; se informa la ausencia de red.
  - **Dependencias**: QA-11, QA-23.
  - **Estado inicial**: `PENDIENTE`.

- **CP-26-02 (Negativo - Prohibición de Caché de Datos Privados)**
  - **Objetivo**: Verificar que el Service Worker y Cache Storage NO almacenen tokens, perfiles de candidatos ni respuestas de endpoints privados (`/me`, `/postulaciones`).
  - **Tipo**: Seguridad / Inspección de Almacenamiento (Appium / DevTools).
  - **Actor**: Auditor de seguridad.
  - **Pasos**:
    1. Inspeccionar Cache Storage tras navegar autenticado.
  - **Resultado esperado**: La caché contiene únicamente assets estáticos (JS, CSS, iconos, fuentes); cero respuestas de la API con datos privados o cabeceras Bearer.
  - **Dependencias**: QA-11, QA-23.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-27: Pérdida de Conexión en Escritura y Recuperación
- **CP-27-01 (Negativo - Corte de Red Durante Envío de Postulación)**
  - **Objetivo**: Demostrar que ante pérdida de red durante una postulación, la PWA NO simula éxito ficticio ni reenvía a ciegas sin verificar el estado del servidor.
  - **Tipo**: Resiliencia / Concurrencia (Appium + Inspección API).
  - **Actor**: Candidato móvil.
  - **Precondiciones**: Postulación en progreso; corte forzado de conexión en el cliente justo al hacer click en Postular.
  - **Pasos**:
    1. Cortar conexión durante la petición HTTP.
  - **Resultado esperado**: La PWA informa error de comunicación y estado incierto; no muestra pantalla de "Postulación Exitosa"; ofrece botón de "Comprobar estado".
  - **Comprobación de persistencia**: La base de datos conserva consistencia (si la transacción entró, se registró; si no, no se registró; nunca queda un estado corrupto).
  - **Dependencias**: QA-11, QA-22, QA-23.
  - **Estado inicial**: `PENDIENTE`.

- **CP-27-02 (Positivo - Consulta Persistente al Reconectar)**
  - **Objetivo**: Al recuperar red, el cliente consulta `/candidatos/me/postulaciones` para verificar si la postulación fue persistida antes de reintentar.
  - **Tipo**: Resiliencia (Appium).
  - **Actor**: Candidato móvil.
  - **Pasos**:
    1. Restaurar red.
    2. Consultar seguimiento.
  - **Resultado esperado**: Si la postulación ya existía, el cliente muestra el estado real y bloquea el botón de postular; si no existía, permite el reintento limpio.
  - **Dependencias**: QA-11, QA-22.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-28: Actualización entre Builds de la PWA
- **CP-28-01 (Positivo - Detección de Nueva Versión y Actualización)**
  - **Objetivo**: Comprobar que al desplegar un nuevo build del frontend, el Service Worker detecta la actualización y avisa al usuario sin pérdida silenciosa de formularios.
  - **Tipo**: PWA / Ciclo de vida (Appium).
  - **Actor**: Candidato con PWA instalada.
  - **Pasos**:
    1. Desplegar actualización del bundle frontend.
    2. Abrir la PWA instalada.
  - **Resultado esperado**: La PWA muestra notificación no invasiva de nueva versión disponible; al aceptar, actualiza la app de manera coherente.
  - **Dependencias**: QA-11, QA-23.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-29: Flujo E2E Candidato en PWA Android
- **CP-29-01 (Positivo - Recorrido Completo desde Móvil)**
  - **Objetivo**: Ejecutar login, búsqueda de empleo, revisión de detalle y postulación desde la PWA instalada en Android mediante Appium.
  - **Tipo**: E2E Móvil (Appium).
  - **Actor**: `candidato1@qa.example.test`.
  - **Precondiciones**: PWA instalada en Android; entorno QA accesible por red.
  - **Pasos**:
    1. Abrir PWA desde lanzador.
    2. Login con credenciales QA.
    3. Buscar vacante.
    4. Postular y confirmar recepción de respuesta 201.
  - **Resultado esperado**: Recorrido fluido y exitoso con aserciones en pantalla y persistencia en DB.
  - **Dependencias**: QA-21, QA-22.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-30: Sesión Móvil, Logout y Reapertura
- **CP-30-01 (Positivo - Reapertura con Sesión Válida)**
  - **Objetivo**: Comprobar que cerrar y reabrir la PWA mantiene la sesión si el token sigue activo.
  - **Tipo**: Funcional Móvil (Appium).
  - **Actor**: Candidato móvil.
  - **Pasos**:
    1. Login -> Cerrar app -> Reabrir app.
  - **Resultado esperado**: Acceso directo al área privada sin solicitar credenciales nuevamente.
  - **Dependencias**: QA-23.
  - **Estado inicial**: `PENDIENTE`.

- **CP-30-02 (Negativo - Sesión Expirada o Revocada)**
  - **Objetivo**: Comprobar que si el token fue revocado en el backend, la reapertura móvil redirige ordenadamente al login sin exponer vistas protegidas.
  - **Tipo**: Seguridad Móvil (Appium).
  - **Actor**: Candidato con token revocado externamente.
  - **Pasos**:
    1. Revocar token en DB -> Reabrir PWA.
  - **Resultado esperado**: Redirección a pantalla de login; limpieza de sesión local.
  - **Dependencias**: QA-23.
  - **Estado inicial**: `PENDIENTE`.

---

## 7. Bloque 6: Rendimiento, Carga y CI (RNF-03, RNF-04)

### CP-31: Carga de Búsqueda y Detalle de Vacantes
- **CP-31-01 (Rendimiento - Lecturas de Búsqueda)**
  - **Objetivo**: Medir p95, throughput y errores en `/api/v1/vacantes` bajo carga de 25 usuarios concurrentes.
  - **Tipo**: Rendimiento / Carga (JMeter).
  - **Actor**: 25 usuarios virtuales candidatos.
  - **Precondiciones**: Entorno QA aislado; base de datos poblada con vacantes y catálogos.
  - **Criterio de aceptación**: p95 ≤ 2.0 s; tasa de errores inesperados < 1.0%; cero caídas de servicio.
  - **Pasos**:
    1. Ramp-up de 30 segundos, duración sostenida de 2 minutos, ramp-down.
  - **Resultado esperado**: Métricas dentro de los umbrales aprobados; reporte HTML generado.
  - **Dependencias**: QA-24, QA-25.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-32: Login Concurrente con Credenciales Únicas
- **CP-32-01 (Rendimiento - Autenticación Concurrente)**
  - **Objetivo**: Validar el endpoint `/api/v1/auth/login` con 25 usuarios simultáneos consumiendo datos de un archivo CSV ficticio sin colisiones ni reutilización indebida de tokens.
  - **Tipo**: Carga / Seguridad (JMeter).
  - **Actor**: 25 usuarios virtuales distintos.
  - **Criterio de aceptación**: p95 ≤ 3.0 s; correlación de tokens 100% exitosa; tasa de error < 1.0%.
  - **Pasos**:
    1. Disparar logins paralelos con CSV Data Set Config.
  - **Resultado esperado**: 25 tokens únicos devueltos y verificados.
  - **Dependencias**: QA-24, QA-25.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-33: Carga de Postulaciones y Decisiones de Kanban
- **CP-33-01 (Rendimiento e Integridad Posterior a la Carga)**
  - **Objetivo**: Ejecutar carga de escrituras (postulaciones masivas y avances de kanban) y verificar que la base de datos mantenga integridad perfecta (cero pérdida, duplicación o sobrecontratación).
  - **Tipo**: Carga / Integridad Transaccional (JMeter + PHPUnit/MySQL).
  - **Actor**: Múltiples candidatos y empresas virtuales.
  - **Criterio de aceptación**: p95 escrituras ≤ 3.0 s; integridad post-carga 100% (recuento de postulaciones contratadas == cupo máximo permitido).
  - **Pasos**:
    1. Ejecutar plan JMX de escrituras concurrentes.
    2. Ejecutar script de auditoría SQL / test de integridad al finalizar.
  - **Resultado esperado**: Cero inconsistencias en relaciones de bases de datos.
  - **Dependencias**: QA-24, QA-25, QA-26.
  - **Estado inicial**: `PENDIENTE`.

---

### CP-34: Reproducibilidad, CI y Trazabilidad de la Entrega
- **CP-34-01 (CI - Pipeline Automatizado en GitHub Actions)**
  - **Objetivo**: Ejecutar suite completa de validación desde una copia limpia en contenedores Docker y runners de CI sin secretos expuestos.
  - **Tipo**: Integración Continua (GitHub Actions).
  - **Actor**: Servidor de CI.
  - **Pasos**:
    1. Disparar workflow en PR hacia `develop`.
  - **Resultado esperado**: Build, lint, pruebas PHPUnit y suites automatizadas ejecutadas con reportes exportados; falla si algún caso falla.
  - **Dependencias**: QA-27, QA-28, QA-30.
  - **Estado inicial**: `PENDIENTE`.
