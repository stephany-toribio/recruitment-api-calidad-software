@smoke @fixture-auth @QA-14
Feature: Ciclo autenticado de comprobacion

  @QA14-S04 @RUB-K04 @RUB-K05
  Scenario: Login consulta autenticada y revocacion

    * def System = Java.type('java.lang.System')
    * def correo = System.getenv('KARATE_FIXTURE_EMAIL')
    * assert correo != null

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'admin' }

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + sesion.token
    When method get
    Then status 200
    And match response.success == true
    And assert responseTime < responseTimeLimit

    * def token = sesion.token
    * def cierre = call read('classpath:helpers/logout.feature')

    Given url apiUrl
    And path 'auth', 'me'
    And header Authorization = 'Bearer ' + sesion.token
    When method get
    Then status 401

    * def schema = read('classpath:schemas/unauthenticated.json')
    And match response == schema
