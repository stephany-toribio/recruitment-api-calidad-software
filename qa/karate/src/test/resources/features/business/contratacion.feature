@QA-16 @contratacion
Feature: Transiciones validas del proceso de seleccion

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def preparada = call read('classpath:helpers/preparar-postulacion.feature')

    * def empresaToken = preparada.empresaToken
    * def vacanteId = preparada.vacanteId
    * def postulacionId = preparada.postulacionId

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

    * def contratoArgs =
      """
      {
        accion: 'contratar',
        origen: 'Entrevista',
        destino: 'Contratado',
        codigoDestino: 'contratado',
        motivoId: null,
        motivoEsperado: null
      }
      """


  @CP-16-01 @RF-08
  Scenario: Avanzar de Recibido a En Revision

    * def movimiento = call read('classpath:helpers/mover-postulacion.feature') revisionArgs

    * match movimiento.despues.estado_nombre == 'En Revisión'
    * assert movimiento.despues.historial.length == 1


  @CP-16-02 @RF-08
  Scenario: Avanzar de En Revision a Entrevista

    * def revision = call read('classpath:helpers/mover-postulacion.feature') revisionArgs

    * def entrevista = call read('classpath:helpers/mover-postulacion.feature') entrevistaArgs

    * match entrevista.despues.estado_nombre == 'Entrevista'
    * assert entrevista.despues.historial.length == 2

    * def estados = karate.map(entrevista.despues.historial, function(h){ return h.estado_nombre })

    * match estados == ['En Revisión', 'Entrevista']


  @CP-16-03 @RF-08
  Scenario: Contratar candidato desde Entrevista con cupo

    * def revision = call read('classpath:helpers/mover-postulacion.feature') revisionArgs

    * def entrevista = call read('classpath:helpers/mover-postulacion.feature') entrevistaArgs

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones'
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def cuposAntes = response.data.vacante.cupos_disponibles
    * def contratadosAntes = response.data.vacante.contratados

    * match contratadosAntes == 0
    * assert cuposAntes > 0

    * def contrato = call read('classpath:helpers/mover-postulacion.feature') contratoArgs

    * match contrato.despues.estado_nombre == 'Contratado'

    * def estados = karate.map(contrato.despues.historial, function(h){ return h.estado_nombre })

    * match estados == ['En Revisión', 'Entrevista', 'Contratado']

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones'
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.data.vacante.contratados == (contratadosAntes + 1)
    * match response.data.vacante.cupos_disponibles == (cuposAntes - 1)

    * def columna = karate.filter(response.data.columnas, function(c){ return c.estado.codigo == 'contratado' })

    * match columna == '#[1]'

    * def ids = karate.map(columna[0].postulaciones, function(p){ return p.id })

    * match ids contains postulacionId
