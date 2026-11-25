@QA-17 @duplicados
Feature: Integridad de postulaciones duplicadas

  @CP-13-01 @demo
  Scenario: Rechazar segunda postulacion del mismo candidato

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def preparada = call read('classpath:helpers/preparar-postulacion.feature')

    * def candidatoToken = preparada.candidatoToken
    * def candidatoId = preparada.candidatoId
    * def vacanteId = preparada.vacanteId
    * def postulacionId = preparada.postulacionId

    * def antes = BusinessFixtureBridge.cantidades(runId)

    * def postulacionAntes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match vacanteAntes.postulaciones == 1
    * match postulacionAntes.candidato_id == candidatoId
    * match postulacionAntes.empleo_id == vacanteId

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    And request { vacante_id: '#(vacanteId)' }
    When method post
    Then status 409
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/error-negocio.json')

    * match response == schema
    * match response.error_code == 'DUPLICATE_APPLICATION'

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes

    * def postulacionDespues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match postulacionDespues == postulacionAntes

    * def vacanteDespues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match vacanteDespues == vacanteAntes
    * match vacanteDespues.postulaciones == 1
