@ignore
Feature: Comprobacion reutilizable de disponibilidad

  Scenario: Verificar servidor Laravel

    Given url baseUrl
    And path 'up'
    When method get
    Then status 200
    And match response contains 'Application up'
    And assert responseTime < responseTimeLimit

    * def healthStatus = responseStatus
    * def healthTime = responseTime
