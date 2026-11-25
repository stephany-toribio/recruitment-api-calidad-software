@ignore
Feature: Autenticacion reutilizable

  Scenario: Obtener token de una cuenta QA

    * def System = Java.type('java.lang.System')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')
    * assert password != null && password.length >= 16

    Given url apiUrl
    And path 'auth', 'login'
    And request { correo: '#(correo)', password: '#(password)' }
    When method post
    Then status 200

    * def schema = read('classpath:schemas/login-success.json')
    * def estructura = karate.match(response, schema).pass
    * assert estructura

    * assert response.data.correo == correo
    * assert response.data.rol == rol
    * assert response.data.usuario_id > 0
    * assert response.data.token.length > 0
    * assert responseTime < responseTimeLimit

    * def token = response.data.token
    * def usuarioId = response.data.usuario_id
