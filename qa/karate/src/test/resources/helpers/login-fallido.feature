@ignore
Feature: Intento de autenticacion incorrecto

  Scenario: Verificar intento fallido y contador

    Given url apiUrl
    And path 'auth', 'login'
    And request { correo: '#(correo)', password: 'Incorrecta#2026' }
    When method post
    Then status 401

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/login-incorrecto.json')
    And match response == schema

    And match response.errors.intentos_fallidos == intento
    And match response.errors.intentos_restantes == (5 - intento)
