@QA-15 @registro @fixture-auth
Feature: Registro de candidatos

  Background:
    * url apiUrl
    * def System = Java.type('java.lang.System')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')
    * assert password != null && password.length >= 16

  @CP-01-01 @demo @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04
  Scenario: Registro valido y persistencia del candidato

    * def fixture = FixtureBridge.datos(runId, 'candidato')
    * assert fixture.success == true

    * def datos = fixture.datos
    * set datos.password = password
    * set datos.password_confirmation = password

    Given path 'candidatos'
    And request datos
    When method post

    * def camposError = responseStatus == 422 && response.errors ? karate.keysOf(response.errors) : []
    * if (responseStatus != 201) karate.fail('HTTP inesperado: ' + responseStatus + '; campos de validacion: ' + camposError.join(', '))

    Then status 201

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/registro-candidato.json')
    And match response == schema

    And match response.data.correo == datos.correo
    And match response.data.dni == datos.dni
    And match response.data.celular == datos.celular
    And match response.data.busca_primer_empleo == true

    * def db = FixtureBridge.consultar(runId, datos.correo, password)

    * match db.existe == true
    * match db.rol == 'candidato'
    * match db.password_hasheada == true
    * match db.usuario_id == response.data.usuario_id
    * match db.candidato_id == response.data.id
    * match db.dni == datos.dni
    * match db.celular == datos.celular
    * match db.estudios == 1
    * match db.idiomas == 1
    * match db.habilidades == 1
    * match db.experiencias == 0
    * match db.tokens == 0

  @CP-01-02 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K05
  Scenario Outline: Rechazar candidato con <campo> duplicado

    * def existente = call read('classpath:helpers/registrar-candidato.feature')

    * def fixture = FixtureBridge.datos(runId, 'candidato')
    * def datos = fixture.datos

    * set datos.password = password
    * set datos.password_confirmation = password

    * eval datos[campo] = existente[campo]

    * def antes = FixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos'
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

    @demo
    Examples:
      | campo |
      | dni   |

    Examples:
      | campo   |
      | correo  |
      | celular |


  @CP-01-03 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04
  Scenario Outline: Rechazar candidato con dato invalido en <campo>

    * def fixture = FixtureBridge.datos(runId, 'candidato')
    * def datos = fixture.datos

    * set datos.password = password
    * set datos.password_confirmation = password

    * eval datos[campo] = valor

    * if (campo == 'password') datos.password_confirmation = valor

    * def antes = FixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos'
    And request datos
    When method post
    Then status 422

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')
    And match response == schema

    * match karate.keysOf(response.errors) contains campoError
    * def mensajes = response.errors[campoError]
    * match mensajes == '#[] #string'
    * assert mensajes.length > 0

    * def despues = FixtureBridge.cantidades(runId)

    * match despues == antes

    @demo
    Examples:
      | campo                | valor!        | campoError   |
      | dni                  | 'ABC12345'    | dni          |

    Examples:
      | campo                | valor!        | campoError   |
      | dni                  | '123'         | dni          |
      | celular              | '123'         | celular      |
      | correo               | 'invalido@'   | correo       |
      | nombre               | ''            | nombre       |
      | apellidos            | ''            | apellidos    |
      | fecha_nacimiento     | '2020-01-01'  | fecha_nacimiento |
      | genero               | 'inexistente' | genero       |
      | password             | 'abc'         | password     |
      | estudios             | []            | estudios     |
      | idiomas              | []            | idiomas      |
      | habilidades          | []            | habilidades  |
      | busca_primer_empleo  | false         | experiencias |
