@ignore
Feature: Verificar rechazo de postulacion a vacante no disponible

  Scenario: Rechazar postulacion sin modificar persistencia

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def antes = BusinessFixtureBridge.cantidades(runId)
    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * def candidatoAntes = BusinessFixtureBridge.consultarCandidato(runId, candidatoId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    And request { vacante_id: '#(vacanteId)' }
    When method post
    Then status 404
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/error-negocio.json')

    * match response == schema
    * match response.error_code == 'VACANCY_NOT_AVAILABLE'

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes

    * def vacanteDespues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match vacanteDespues == vacanteAntes

    * def candidatoDespues = BusinessFixtureBridge.consultarCandidato(runId, candidatoId)
    * match candidatoDespues == candidatoAntes
