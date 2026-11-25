@ignore
Feature: Retirar postulacion conservando su historial

  Scenario: Retirar postulacion propia mediante API

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match antes.retirada_en == null

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones', postulacionId, 'retirar'
    And header Authorization = 'Bearer ' + candidatoToken
    And request {}
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/postulacion-retirada.json')

    * match response == schema
    * match response.data.id == postulacionId
    * match response.data.estado.nombre == antes.estado_nombre

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match despues.id == antes.id
    * match despues.candidato_id == antes.candidato_id
    * match despues.empleo_id == antes.empleo_id
    * match despues.estado_postulacion_id == antes.estado_postulacion_id
    * match despues.fecha_postulacion == antes.fecha_postulacion
    * match despues.retirada_en == '#string'
    * match despues.historial == antes.historial
