@ignore
Feature: Registro reutilizable de empresa sintetica

  Scenario: Crear empresa mediante API

    * def System = Java.type('java.lang.System')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')

    * def fixture = FixtureBridge.datos(runId, 'empresa')
    * def datos = fixture.datos

    * set datos.password = password

    Given url apiUrl
    And path 'empresas'
    And request datos
    When method post
    Then status 201

    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/registro-empresa.json')
    And match response == schema

    * def empresaId = response.data.id
    * def correo = response.data.correo
    * def ruc = response.data.ruc
