@QA-15 @seguridad @fixture-auth
Feature: Credenciales incorrectas bloqueo y suspension

  Background:
    * def System = Java.type('java.lang.System')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')

  @CP-04-01 @demo @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K05
  Scenario: Password incorrecta incrementa contador sin emitir token

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')
    * def correo = candidato.correo

    * def antes = FixtureBridge.consultar(runId, correo, password)
    * match antes.intentos_fallidos == 0
    * match antes.tokens == 0

    * def fallo = call read('classpath:helpers/login-fallido.feature') { correo: '#(correo)', intento: 1 }

    * def db = FixtureBridge.consultar(runId, correo, password)

    * match db.intentos_fallidos == 1
    * match db.tokens == 0
    * match db.bloqueado == false


  @CP-04-02 @demo @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K05
  Scenario: Cinco fallos generan bloqueo y posterior recuperacion

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')
    * def correo = candidato.correo

    * def antes = FixtureBridge.consultar(runId, correo, password)
    * match antes.intentos_fallidos == 0
    * match antes.tokens == 0
    * match antes.bloqueado == false

    * def fallo1 = call read('classpath:helpers/login-fallido.feature') { correo: '#(correo)', intento: 1 }
    * def fallo2 = call read('classpath:helpers/login-fallido.feature') { correo: '#(correo)', intento: 2 }
    * def fallo3 = call read('classpath:helpers/login-fallido.feature') { correo: '#(correo)', intento: 3 }
    * def fallo4 = call read('classpath:helpers/login-fallido.feature') { correo: '#(correo)', intento: 4 }

    * def inicioBloqueo = java.lang.System.currentTimeMillis()

    Given url apiUrl
    And path 'auth', 'login'
    And request { correo: '#(correo)', password: 'Incorrecta#2026' }
    When method post
    Then status 429

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/login-bloqueo-inicial.json')
    And match response == schema

    * def db = FixtureBridge.consultar(runId, correo, password)

    * match db.intentos_fallidos == 5
    * match db.tokens == 0
    * match db.bloqueado == true

    * def finBloqueo = Date.parse(db.bloqueado_hasta)
    * def duracion = finBloqueo - inicioBloqueo
    * assert duracion >= 895000 && duracion <= 905000

    Given url apiUrl
    And path 'auth', 'login'
    And request { correo: '#(correo)', password: '#(password)' }
    When method post
    Then status 429

    And assert responseTime < responseTimeLimit

    * def schemaBloqueado = read('classpath:schemas/login-bloqueado.json')
    And match response == schemaBloqueado

    * def durante = FixtureBridge.consultar(runId, correo, password)

    * match durante.intentos_fallidos == 5
    * match durante.tokens == 0
    * match durante.bloqueado_hasta == db.bloqueado_hasta

    # Simulacion explicita del vencimiento.
    # No se reduce la duracion contractual del bloqueo.

    * def expirado = FixtureBridge.expirarBloqueo(runId, correo)

    * match expirado.success == true
    * match expirado.bloqueado == false
    * match expirado.intentos_fallidos == 5

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'candidato' }

    * def recuperado = FixtureBridge.consultar(runId, correo, password)

    * match recuperado.intentos_fallidos == 0
    * match recuperado.bloqueado == false
    * match recuperado.bloqueado_hasta == null
    * match recuperado.tokens == 1


  @CP-04-03 @RUB-K01 @RUB-K02 @RUB-K03
  Scenario: Cuenta suspendida rechaza autenticacion con password correcta

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')
    * def correo = candidato.correo

    * def suspension = FixtureBridge.suspender(runId, correo)

    * match suspension.success == true
    * match suspension.estado == 'suspendido'

    Given url apiUrl
    And path 'auth', 'login'
    And request { correo: '#(correo)', password: '#(password)' }
    When method post
    Then status 403

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/login-suspendido.json')
    And match response == schema

    * def db = FixtureBridge.consultar(runId, correo, password)

    * match db.estado == 'suspendido'
    * match db.tokens == 0
    * match db.intentos_fallidos == 0
