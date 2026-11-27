@QA-15 @autenticacion @fixture-auth
Feature: Autenticacion de los tres roles

  Background:
    * def System = Java.type('java.lang.System')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')

  @CP-03-01 @demo @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04 @RUB-K05
  Scenario: Login valido de candidato

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')
    * def correo = candidato.correo

    * def antes = FixtureBridge.consultar(runId, correo, password)
    * match antes.tokens == 0

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'candidato' }

    * def despues = FixtureBridge.consultar(runId, correo, password)

    * match despues.tokens == 1
    * match despues.rol == 'candidato'
    * match despues.usuario_id == sesion.usuarioId
    * match despues.intentos_fallidos == 0

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + sesion.token
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/auth-me.json')
    And match response == schema
    And match response.data.id == sesion.usuarioId
    And match response.data.correo == correo
    And match response.data.rol == 'candidato'


  @CP-03-02 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04 @RUB-K05
  Scenario: Login valido de empresa

    * def empresa = call read('classpath:helpers/registrar-empresa.feature')
    * def correo = empresa.correo

    * def antes = FixtureBridge.consultar(runId, correo, password)
    * match antes.tokens == 0

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'empresa' }

    * def despues = FixtureBridge.consultar(runId, correo, password)

    * match despues.tokens == 1
    * match despues.rol == 'empresa'
    * match despues.usuario_id == sesion.usuarioId

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + sesion.token
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/auth-me.json')
    And match response == schema
    And match response.data.id == sesion.usuarioId
    And match response.data.correo == correo
    And match response.data.rol == 'empresa'


  @CP-03-03 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04 @RUB-K05
  Scenario: Login valido de administrador

    * def admin = FixtureBridge.crearAdmin(runId, password)
    * assert admin.success == true
    * def correo = admin.correo

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'admin' }

    * def db = FixtureBridge.consultar(runId, correo, password)

    * match db.tokens == 1
    * match db.rol == 'admin'
    * match db.usuario_id == admin.usuario_id

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + sesion.token
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/auth-me.json')
    And match response == schema
    And match response.data.id == admin.usuario_id
    And match response.data.correo == correo
    And match response.data.rol == 'admin'
