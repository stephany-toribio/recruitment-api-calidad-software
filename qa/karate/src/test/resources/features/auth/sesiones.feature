@QA-15 @sesiones @fixture-auth
Feature: Cierre de sesion y revocacion de tokens

  Background:
    * def System = Java.type('java.lang.System')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')

  @CP-05-01 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K05
  Scenario: Logout elimina el token activo

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')
    * def correo = candidato.correo

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'candidato' }

    * def antes = FixtureBridge.consultar(runId, correo, password)
    * match antes.tokens == 1

    * def token = sesion.token
    * def cierre = call read('classpath:helpers/logout.feature')

    * def schema = read('classpath:schemas/logout-success.json')
    * match cierre.response == schema

    * def despues = FixtureBridge.consultar(runId, correo, password)
    * match despues.tokens == 0


  @CP-05-02 @demo @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K05
  Scenario: Token revocado no permite acceso posterior

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')
    * def correo = candidato.correo

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'candidato' }

    * def token = sesion.token
    * def cierre = call read('classpath:helpers/logout.feature')

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + token
    When method get
    Then status 401

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/unauthenticated.json')
    And match response == schema

    * def db = FixtureBridge.consultar(runId, correo, password)
    * match db.tokens == 0


  @CP-05-03 @RUB-K01 @RUB-K02 @RUB-K03
  Scenario: Token malformado es rechazado

    * def antes = FixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer token_falso_invalido'
    When method get
    Then status 401

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/unauthenticated.json')
    And match response == schema

    * def despues = FixtureBridge.cantidades(runId)
    * match despues == antes


  @QA15-EXT-01 @RUB-K04 @RUB-K05
  Scenario: Logout revoca solo la sesion utilizada

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')
    * def correo = candidato.correo

    * def sesion1 = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'candidato' }
    * def sesion2 = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'candidato' }

    * assert sesion1.token != sesion2.token

    * def antes = FixtureBridge.consultar(runId, correo, password)
    * match antes.tokens == 2

    * def token = sesion1.token
    * def cierre = call read('classpath:helpers/logout.feature')

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + sesion1.token
    When method get
    Then status 401
    And assert responseTime < responseTimeLimit

    * def errorSchema = read('classpath:schemas/unauthenticated.json')
    And match response == errorSchema

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + sesion2.token
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/auth-me.json')
    And match response == schema

    * def durante = FixtureBridge.consultar(runId, correo, password)
    * match durante.tokens == 1

    * def token = sesion2.token
    * def cierreFinal = call read('classpath:helpers/logout.feature')

    * def despues = FixtureBridge.consultar(runId, correo, password)
    * match despues.tokens == 0
