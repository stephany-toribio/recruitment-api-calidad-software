@QA-17 @retiros
Feature: Retiro de postulaciones y restricciones de integridad

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def schemaError = read('classpath:schemas/error-negocio.json')


  @CP-20-01
  Scenario: Retiro permitido del candidato propietario

    * def preparada = call read('classpath:helpers/preparar-estado-postulacion.feature') { estadoObjetivo: 'revision' }

    * def candidatoToken = preparada.candidatoToken
    * def candidatoId = preparada.candidatoId
    * def postulacionId = preparada.postulacionId
    * def vacanteId = preparada.vacanteId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    * match antes.candidato_id == candidatoId
    * match antes.estado_nombre == 'En Revisión'
    * match antes.retirada_en == null

    * def retiro = call read('classpath:helpers/retirar-postulacion.feature')

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match despues.retirada_en == '#string'
    * match despues.estado_nombre == antes.estado_nombre
    * match despues.estado_postulacion_id == antes.estado_postulacion_id
    * match despues.historial == antes.historial
    * match despues.notificaciones == antes.notificaciones

    * def cantidadesDespues = BusinessFixtureBridge.cantidades(runId)
    * match cantidadesDespues == cantidadesAntes


  @CP-20-02
  Scenario: Impedir retiro por candidato ajeno

    * def preparada = call read('classpath:helpers/preparar-estado-postulacion.feature') { estadoObjetivo: 'revision' }

    * def postulacionId = preparada.postulacionId
    * def propietarioId = preparada.candidatoId

    * def ajeno = call read('classpath:helpers/preparar-candidato.feature')

    * assert ajeno.candidatoId != propietarioId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match antes.candidato_id == propietarioId
    * match antes.retirada_en == null

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones', postulacionId, 'retirar'
    And header Authorization = 'Bearer ' + ajeno.candidatoToken
    And request {}
    When method patch
    Then status 403
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'FORBIDDEN'

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)
    * match despues == antes

    * def cantidadesDespues = BusinessFixtureBridge.cantidades(runId)
    * match cantidadesDespues == cantidadesAntes


  @CP-20-03 @demo
  Scenario: Impedir nueva postulacion despues del retiro

    * def preparada = call read('classpath:helpers/preparar-estado-postulacion.feature') { estadoObjetivo: 'retirado' }

    * def candidatoToken = preparada.candidatoToken
    * def postulacionId = preparada.postulacionId
    * def vacanteId = preparada.vacanteId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.retirada_en == '#string'
    * match vacanteAntes.postulaciones == 1

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    And request { vacante_id: '#(vacanteId)' }
    When method post
    Then status 409
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'DUPLICATE_APPLICATION'

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)
    * match despues == antes

    * def cantidadesDespues = BusinessFixtureBridge.cantidades(runId)
    * match cantidadesDespues == cantidadesAntes

    * def vacanteDespues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match vacanteDespues == vacanteAntes


  @CP-20-04
  Scenario Outline: Impedir retiro desde estado terminal

    * def preparada = call read('classpath:helpers/preparar-estado-postulacion.feature') { estadoObjetivo: '#(estado)' }

    * def candidatoToken = preparada.candidatoToken
    * def postulacionId = preparada.postulacionId
    * def vacanteId = preparada.vacanteId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.estado_nombre == nombreEstado
    * match antes.retirada_en == null

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones', postulacionId, 'retirar'
    And header Authorization = 'Bearer ' + candidatoToken
    And request {}
    When method patch
    Then status 409
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'APPLICATION_NOT_WITHDRAWABLE'

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)
    * match despues == antes

    * def cantidadesDespues = BusinessFixtureBridge.cantidades(runId)
    * match cantidadesDespues == cantidadesAntes

    * def vacanteDespues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match vacanteDespues == vacanteAntes

    Examples:
      | estado     | nombreEstado |
      | contratado | Contratado   |
      | rechazado  | Rechazado    |
