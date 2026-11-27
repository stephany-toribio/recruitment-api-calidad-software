@QA-17 @transiciones
Feature: Rechazar transiciones invalidas de postulaciones

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def schemaError = read('classpath:schemas/error-negocio.json')


  @CP-18-01 @demo
  Scenario: Impedir salto directo de Recibido a Contratado

    * def preparada = call read('classpath:helpers/preparar-estado-postulacion.feature') { estadoObjetivo: 'recibido' }

    * def empresaToken = preparada.empresaToken
    * def postulacionId = preparada.postulacionId
    * def vacanteId = preparada.vacanteId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.estado_nombre == 'Recibido'
    * match antes.retirada_en == null

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones', postulacionId, 'contratar'
    And header Authorization = 'Bearer ' + empresaToken
    And request {}
    When method patch
    Then status 409
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'INVALID_TRANSITION'

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match despues == antes

    * def cantidadesDespues = BusinessFixtureBridge.cantidades(runId)
    * match cantidadesDespues == cantidadesAntes

    * def vacanteDespues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match vacanteDespues == vacanteAntes


  @CP-18-02
  Scenario Outline: Bloquear modificaciones en estados terminales

    * def preparada = call read('classpath:helpers/preparar-estado-postulacion.feature') { estadoObjetivo: '#(estado)' }

    * def empresaToken = preparada.empresaToken
    * def postulacionId = preparada.postulacionId
    * def vacanteId = preparada.vacanteId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.estado_nombre == nombreEstado
    * match antes.retirada_en == null

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones', postulacionId, 'avanzar'
    And header Authorization = 'Bearer ' + empresaToken
    And request {}
    When method patch
    Then status 409
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'INVALID_TRANSITION'

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


  @CP-18-03
  Scenario: Impedir modificar postulacion retirada

    * def preparada = call read('classpath:helpers/preparar-estado-postulacion.feature') { estadoObjetivo: 'retirado' }

    * def empresaToken = preparada.empresaToken
    * def postulacionId = preparada.postulacionId
    * def vacanteId = preparada.vacanteId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.retirada_en == '#string'
    * match antes.estado_nombre == 'En Revisión'

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones', postulacionId, 'avanzar'
    And header Authorization = 'Bearer ' + empresaToken
    And request {}
    When method patch
    Then status 409
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'APPLICATION_WITHDRAWN'

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)
    * match despues == antes

    * def cantidadesDespues = BusinessFixtureBridge.cantidades(runId)
    * match cantidadesDespues == cantidadesAntes

    * def vacanteDespues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match vacanteDespues == vacanteAntes
