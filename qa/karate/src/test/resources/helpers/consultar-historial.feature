@ignore
Feature: Consultar historial de postulaciones de una vacante

  Scenario: Verificar contrato de historial

    * def movimientoSchema = read('classpath:schemas/movimiento-historico.json')

    * def postulacionSchema = read('classpath:schemas/postulacion-historica.json')

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones', 'historial'
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/historial-empresa.json')

    * match response == schema
    * match response.data.vacante.id == vacanteId

    * def historial = response.data
