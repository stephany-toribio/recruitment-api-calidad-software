@QA-16 @vacantes
Feature: Registro y validacion de vacantes

  Background:
    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def empresa = call read('classpath:helpers/registrar-empresa.feature')
    * def correo = empresa.correo
    * def empresaId = empresa.empresaId

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'empresa' }

    * def empresaToken = sesion.token


  @CP-07-01 @RF-04 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04 @RUB-K05
  Scenario: Registrar vacante valida sin publicacion prematura

    * def antes = BusinessFixtureBridge.cantidades(runId)

    * def creada = call read('classpath:helpers/crear-vacante.feature')

    * def despues = BusinessFixtureBridge.cantidades(runId)

    * match despues.vacantes == (antes.vacantes + 1)
    * match despues.habilidades_vacantes == (antes.habilidades_vacantes + 5)
    * match despues.revisiones == antes.revisiones
    * match despues.pagos == antes.pagos
    * match despues.postulaciones == antes.postulaciones

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', creada.vacanteId
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200

    And assert responseTime < responseTimeLimit
    And match response.success == true
    And match response.data.id == creada.vacanteId
    And match response.data.estado.general == 'pendiente_revision'
    And match response.data.estado.publicada_en == null


  @CP-07-02 @RF-04 @RUB-K01 @RUB-K02 @RUB-K03 @demo
  Scenario: Rechazar rango salarial invertido

    * def fixture = BusinessFixtureBridge.datosVacante(runId)
    * def datos = fixture.datos

    * set datos.salario_min = 5000
    * set datos.salario_max = 3000

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes'
    And header Authorization = 'Bearer ' + empresaToken
    And request datos
    When method post
    Then status 422

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')
    And match response == schema
    And match response.errors.salario_max == '#[] #string'
    And assert response.errors.salario_max.length > 0

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes


  @CP-07-03 @RF-04 @RUB-K01 @RUB-K02 @RUB-K03
  Scenario: Rechazar fecha final anterior al inicio

    * def fixture = BusinessFixtureBridge.datosVacante(runId)
    * def datos = fixture.datos

    * def inicioOriginal = datos.fecha_inicio_vigencia
    * def finOriginal = datos.fecha_fin_vigencia

    * set datos.fecha_inicio_vigencia = finOriginal
    * set datos.fecha_fin_vigencia = inicioOriginal

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes'
    And header Authorization = 'Bearer ' + empresaToken
    And request datos
    When method post
    Then status 422

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')
    And match response == schema
    And match response.errors.fecha_fin_vigencia == '#[] #string'
    And assert response.errors.fecha_fin_vigencia.length > 0

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes


  @CP-07-04 @RF-04 @RUB-K01 @RUB-K02 @RUB-K03
  Scenario: Rechazar vacante sin cupos

    * def fixture = BusinessFixtureBridge.datosVacante(runId)
    * def datos = fixture.datos

    * set datos.max_contrataciones = 0

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes'
    And header Authorization = 'Bearer ' + empresaToken
    And request datos
    When method post
    Then status 422

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')
    And match response == schema
    And match response.errors.max_contrataciones == '#[] #string'
    And assert response.errors.max_contrataciones.length > 0

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes
