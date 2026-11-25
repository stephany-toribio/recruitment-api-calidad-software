@QA-16 @rechazo
Feature: Rechazo de postulaciones con motivo obligatorio

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def preparada = call read('classpath:helpers/preparar-postulacion.feature')

    * def empresaToken = preparada.empresaToken
    * def vacanteId = preparada.vacanteId
    * def postulacionId = preparada.postulacionId

    * def catalogos = BusinessFixtureBridge.catalogosNegocio(runId)

    * def revisionArgs =
      """
      {
        accion: 'avanzar',
        origen: 'Recibido',
        destino: 'En Revisión',
        codigoDestino: 'en_revision',
        motivoId: null,
        motivoEsperado: null
      }
      """

    * def entrevistaArgs =
      """
      {
        accion: 'avanzar',
        origen: 'En Revisión',
        destino: 'Entrevista',
        codigoDestino: 'entrevista',
        motivoId: null,
        motivoEsperado: null
      }
      """

    * def revision = call read('classpath:helpers/mover-postulacion.feature') revisionArgs


  @CP-17-01 @RF-08
  Scenario: Rechazar candidato desde En Revision

    * def motivo = catalogos.motivos[0]

    * def argumentos =
      """
      {
        accion: 'rechazar',
        origen: 'En Revisión',
        destino: 'Rechazado',
        codigoDestino: 'rechazado',
        motivoId: '#(motivo.id)',
        motivoEsperado: '#(motivo.motivo)'
      }
      """

    * def rechazo = call read('classpath:helpers/mover-postulacion.feature') argumentos

    * match rechazo.despues.estado_nombre == 'Rechazado'

    * def estados = karate.map(rechazo.despues.historial, function(h){ return h.estado_nombre })

    * match estados == ['En Revisión', 'Rechazado']


  @CP-17-02 @RF-08
  Scenario: Rechazar candidato desde Entrevista

    * def entrevista = call read('classpath:helpers/mover-postulacion.feature') entrevistaArgs

    * def motivo = catalogos.motivos[1]

    * def argumentos =
      """
      {
        accion: 'rechazar',
        origen: 'Entrevista',
        destino: 'Rechazado',
        codigoDestino: 'rechazado',
        motivoId: '#(motivo.id)',
        motivoEsperado: '#(motivo.motivo)'
      }
      """

    * def rechazo = call read('classpath:helpers/mover-postulacion.feature') argumentos

    * match rechazo.despues.estado_nombre == 'Rechazado'

    * def estados = karate.map(rechazo.despues.historial, function(h){ return h.estado_nombre })

    * match estados == ['En Revisión', 'Entrevista', 'Rechazado']


  @CP-17-03 @RF-08
  Scenario Outline: Impedir rechazo sin motivo valido

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def datos = tipo == 'ausente' ? {} : { motivo_rechazo_id: catalogos.motivo_inexistente }

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones', postulacionId, 'rechazar'
    And header Authorization = 'Bearer ' + empresaToken
    And request datos
    When method patch
    Then status 422
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')

    * match response == schema
    * match response.errors.motivo_rechazo_id == '#[] #string'
    * match karate.keysOf(response.errors) == ['motivo_rechazo_id']

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match despues == antes
    * match despues.estado_nombre == 'En Revisión'

    Examples:
      | tipo        |
      | ausente     |
      | inexistente |
