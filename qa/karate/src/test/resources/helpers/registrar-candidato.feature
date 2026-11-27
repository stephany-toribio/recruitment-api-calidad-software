@ignore
Feature: Registro reutilizable de candidato sintetico

  Scenario: Crear candidato mediante API

    * def System = Java.type('java.lang.System')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')

    * def fixture = FixtureBridge.datos(runId, 'candidato')
    * def datos = fixture.datos

    * set datos.password = password
    * set datos.password_confirmation = password

    Given url apiUrl
    And path 'candidatos'
    And request datos
    When method post
    Then status 201

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/registro-candidato.json')
    And match response == schema

    * def candidatoId = response.data.id
    * def usuarioId = response.data.usuario_id
    * def correo = response.data.correo
    * def dni = response.data.dni
    * def celular = response.data.celular
