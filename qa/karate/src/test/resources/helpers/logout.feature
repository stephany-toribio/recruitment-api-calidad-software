@ignore
Feature: Cierre de sesion reutilizable

  Scenario: Revocar token utilizado

    Given url apiUrl
    And path 'auth', 'logout'
    And header Authorization = 'Bearer ' + token
    And request {}
    When method post
    Then status 200

    And match response.success == true
    And match response.message == '#string'
    And assert responseTime < responseTimeLimit
