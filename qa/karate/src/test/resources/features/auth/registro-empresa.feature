@QA-15 @registro @fixture-auth
Feature: Registro de empresas

  Background:
    * url apiUrl
    * def System = Java.type('java.lang.System')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')
    * assert password != null && password.length >= 16

  @CP-02-01 @demo @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04
  Scenario: Registro valido y persistencia de empresa

    * def fixture = FixtureBridge.datos(runId, 'empresa')
    * assert fixture.success == true

    * def datos = fixture.datos
    * set datos.password = password

    Given path 'empresas'
    And request datos
    When method post

    * def camposError = responseStatus == 422 && response.errors ? karate.keysOf(response.errors) : []
    * if (responseStatus != 201) karate.fail('HTTP inesperado: ' + responseStatus + '; campos de validacion: ' + camposError.join(', '))

    Then status 201

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/registro-empresa.json')
    And match response == schema

    And match response.data.correo == datos.correo
    And match response.data.ruc == datos.ruc
    And match response.data.razon_social == datos.razon_social
    And match response.data.sector == datos.sector

    * def db = FixtureBridge.consultar(runId, datos.correo, password)

    * match db.existe == true
    * match db.rol == 'empresa'
    * match db.password_hasheada == true
    * match db.empresa_id == response.data.id
    * match db.ruc == datos.ruc
    * match db.tokens == 0

  @CP-02-02 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K05
  Scenario Outline: Rechazar empresa con <campo> duplicado

    * def existente = call read('classpath:helpers/registrar-empresa.feature')

    * def fixture = FixtureBridge.datos(runId, 'empresa')
    * def datos = fixture.datos

    * set datos.password = password

    * eval datos[campo] = existente[campo]

    * def antes = FixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas'
    And request datos
    When method post
    Then status 422

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')
    And match response == schema

    * def camposEsperados = []
    * eval camposEsperados.push(campo)
    * match karate.keysOf(response.errors) == camposEsperados
    * def mensajes = response.errors[campo]
    * match mensajes == '#[] #string'
    * assert mensajes.length > 0

    * def despues = FixtureBridge.cantidades(runId)

    * match despues == antes

    Examples:
      | campo  |
      | correo |
      | ruc    |


  @CP-02-03 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04
  Scenario Outline: Rechazar empresa con dato invalido en <campo>

    * def fixture = FixtureBridge.datos(runId, 'empresa')
    * def datos = fixture.datos

    * set datos.password = password

    * eval datos[campo] = valor

    * def antes = FixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas'
    And request datos
    When method post
    Then status 422

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')
    And match response == schema

    * match karate.keysOf(response.errors) contains campo
    * def mensajes = response.errors[campo]
    * match mensajes == '#[] #string'
    * assert mensajes.length > 0

    * def despues = FixtureBridge.cantidades(runId)

    * match despues == antes

    Examples:
      | campo             | valor!                |
      | ruc               | '12345'               |
      | ruc               | '2000000000A'         |
      | correo            | 'invalido@'           |
      | sector            | 'No permitido'        |
      | encargado_empresa | ''                    |
      | razon_social      | ''                    |
      | sitio_web         | 'no-es-url'           |
      | descripcion       | 'A'.repeat(251)       |
      | password          | 'abc'                 |
