@ignore
Feature: Eliminar logicamente vacante publicada

  Scenario: Empresa elimina su vacante

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId
    And header Authorization = 'Bearer ' + empresaToken
    When method delete
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.success == true
    * match response.data.id == vacanteId
    * match response.message == 'Vacante eliminada. Puedes consultarla en el historial.'
    * match response.data.estado.general == 'eliminada'
    * match response.data.eliminado_en == '#string'

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')
    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.eliminado_en == '#string'
