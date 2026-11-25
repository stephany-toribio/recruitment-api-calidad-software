@ignore
Feature: Registrar postulacion mediante API

  Scenario: Crear postulacion en estado Recibido

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def catalogos = BusinessFixtureBridge.catalogosNegocio(runId)

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    And request { vacante_id: '#(vacanteId)' }
    When method post
    Then status 201
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/postulacion-creada.json')

    * match response == schema
    * match response.data.vacante.id == vacanteId
    * match response.data.vacante.titulo == tituloVacante

    * def postulacionId = response.data.id
    * assert postulacionId > 0

    * def db = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match db.success == true
    * match db.id == postulacionId
    * match db.candidato_id == candidatoId
    * match db.empleo_id == vacanteId
    * match db.estado_nombre == 'Recibido'
    * match db.estado_postulacion_id == catalogos.estados.Recibido
    * match db.retirada_en == null
    * match db.historial == []
    * match db.notificaciones == 0

    * def despues = BusinessFixtureBridge.cantidades(runId)

    * match despues.postulaciones == (antes.postulaciones + 1)
    * match despues.historial == antes.historial
    * match despues.vacantes == antes.vacantes
