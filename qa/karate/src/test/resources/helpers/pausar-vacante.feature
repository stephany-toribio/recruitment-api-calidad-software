@ignore
Feature: Pausar vacante publicada

  Scenario: Empresa pausa su vacante

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pausar'
    And header Authorization = 'Bearer ' + empresaToken
    And request {}
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.success == true
    * match response.data.id == vacanteId
    * match response.message == 'Vacante pausada. Ya no recibe postulaciones.'
    * match response.data.estado.general == 'pausada'
    * match response.data.estado.disponibilidad == 'pausada'

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')
    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.estado_empleo == 'pausada'
    * match db.eliminado_en == null
