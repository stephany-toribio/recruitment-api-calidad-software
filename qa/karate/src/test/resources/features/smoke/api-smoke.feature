@smoke @QA-14
Feature: Comprobacion inicial de la API CREO+

  Background:
    * url apiUrl

  @QA14-S01 @RUB-K04 @RUB-K05
  Scenario: Disponibilidad mediante helper reutilizable

    * def health = call read('classpath:helpers/health.feature')
    * match health.healthStatus == 200
    * assert health.healthTime < responseTimeLimit
    * match runId == '#uuid'

  @QA14-S02 @RUB-K02
  Scenario: Recurso protegido sin autenticacion

    Given path 'auth', 'me'
    When method get
    Then status 401

    * def schema = read('classpath:schemas/unauthenticated.json')
    And match response == schema

    And assert responseTime < responseTimeLimit

  @QA14-S03 @RUB-K02
  Scenario: Login sin campos obligatorios

    Given path 'auth', 'login'
    And request {}
    When method post
    Then status 422

    * def schema = read('classpath:schemas/validation-login.json')
    And match response == schema

    And assert responseTime < responseTimeLimit
