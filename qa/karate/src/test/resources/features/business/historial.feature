@QA-16 @historial
Feature: Auditoria e integridad del historial de postulaciones

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def preparada = call read('classpath:helpers/preparar-postulacion.feature')

    * def empresaToken = preparada.empresaToken
    * def candidatoToken = preparada.candidatoToken
    * def vacanteId = preparada.vacanteId
    * def tituloVacante = preparada.tituloVacante
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

    * def revision = call read('classpath:helpers/mover-postulacion.feature') revisionArgs


  @CP-22-01 @RF-10
  Scenario: Empresa consulta historial completo con activos y retirados

    * def segundo = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoTokenOriginal = candidatoToken
    * def candidatoIdOriginal = preparada.candidatoId

    * def candidatoToken = segundo.candidatoToken
    * def candidatoId = segundo.candidatoId

    * def segundaPostulacion = call read('classpath:helpers/crear-postulacion.feature')

    * def segundoId = segundaPostulacion.postulacionId

    * def segundoArgs = karate.merge(revisionArgs, { postulacionId: segundoId })

    * def segundaRevision = call read('classpath:helpers/mover-postulacion.feature') segundoArgs

    * def retiro = call read('classpath:helpers/retirar-postulacion.feature') { postulacionId: '#(segundoId)' }

    * def candidatoToken = candidatoTokenOriginal
    * def candidatoId = candidatoIdOriginal

    * def consulta = call read('classpath:helpers/consultar-historial.feature')

    * match consulta.historial.postulaciones == '#[2]'

    * def activas = karate.filter(consulta.historial.postulaciones, function(p){ return p.id == postulacionId })

    * def retiradas = karate.filter(consulta.historial.postulaciones, function(p){ return p.id == segundoId })

    * match activas == '#[1]'
    * match retiradas == '#[1]'

    * match activas[0].retirada == false
    * match retiradas[0].retirada == true

    * match activas[0].movimientos == '#[1]'
    * match retiradas[0].movimientos == '#[1]'

    * def dbActiva = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * def dbRetirada = BusinessFixtureBridge.consultarPostulacion(runId, segundoId)

    * match activas[0].movimientos[0].id == dbActiva.historial[0].id

    * match retiradas[0].movimientos[0].id == dbRetirada.historial[0].id


  @CP-22-02 @RF-10
  Scenario: Conservar motivo de rechazo en historial

    * def entrevista = call read('classpath:helpers/mover-postulacion.feature') entrevistaArgs

    * def catalogos = BusinessFixtureBridge.catalogosNegocio(runId)

    * def motivo = catalogos.motivos[0]

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

    * def consulta = call read('classpath:helpers/consultar-historial.feature')

    * match consulta.historial.postulaciones == '#[1]'

    * def registro = consulta.historial.postulaciones[0]

    * match registro.id == postulacionId
    * match registro.estado.codigo == 'rechazado'
    * match registro.movimientos == '#[3]'

    * def estados = karate.map(registro.movimientos, function(m){ return m.estado.nombre })

    * match estados == ['En Revisión', 'Entrevista', 'Rechazado']

    * match registro.movimientos[0].motivo_rechazo == null
    * match registro.movimientos[1].motivo_rechazo == null
    * match registro.movimientos[2].motivo_rechazo == motivo.motivo

    * def db = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match db.historial[2].motivo_rechazo_id == motivo.id

    * def idsApi = karate.map(registro.movimientos, function(m){ return m.id })

    * def idsDb = karate.map(db.historial, function(m){ return m.id })

    * match idsApi == idsDb


  @CP-22-03 @RF-10
  Scenario: Conservar movimientos anteriores al retiro

    * def entrevista = call read('classpath:helpers/mover-postulacion.feature') entrevistaArgs

    * def historialAntes = call read('classpath:helpers/consultar-historial.feature')

    * def movimientosAntes = historialAntes.historial.postulaciones[0].movimientos

    * match movimientosAntes == '#[2]'

    * def retiro = call read('classpath:helpers/retirar-postulacion.feature')

    * def historialDespues = call read('classpath:helpers/consultar-historial.feature')

    * match historialDespues.historial.postulaciones == '#[1]'

    * def registro = historialDespues.historial.postulaciones[0]

    * match registro.id == postulacionId
    * match registro.retirada == true
    * match registro.retirada_en == '#string'
    * match registro.estado.codigo == 'entrevista'
    * match registro.movimientos == movimientosAntes

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones'
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def ids = karate.jsonPath(response.data.columnas, '$[*].postulaciones[*].id')

    * match ids !contains postulacionId
