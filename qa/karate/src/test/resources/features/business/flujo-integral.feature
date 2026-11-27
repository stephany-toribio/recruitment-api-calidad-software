@QA-16 @integral
Feature: Flujo integral de seleccion CREO+

  @QA16-E2E-01 @RUB-K01 @RUB-K02 @RUB-K03 @RUB-K04 @RUB-K05 @demo
  Scenario: Desde registro de vacante hasta contratacion y auditoria

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def publicada = call read('classpath:helpers/publicar-vacante.feature')

    * def empresaToken = publicada.empresaToken
    * def vacanteId = publicada.vacanteId
    * def tituloVacante = publicada.tituloVacante

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoToken = candidato.candidatoToken
    * def candidatoId = candidato.candidatoId

    Given url apiUrl
    And path 'vacantes'
    And param titulo = tituloVacante
    And header Authorization = 'Bearer ' + candidatoToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.success == true

    * match response.meta ==
      """
      {
        current_page: '#number',
        per_page: '#number',
        total: '#number',
        last_page: '#number'
      }
      """

    * def encontradas = karate.filter(response.data, function(v){ return v.id == vacanteId })

    * match encontradas == '#[1]'
    * match encontradas[0].titulo == tituloVacante

    Given url apiUrl
    And path 'vacantes', vacanteId
    And header Authorization = 'Bearer ' + candidatoToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.data.id == vacanteId
    * match response.data.puede_postular == true
    * match response.data.postulacion == null

    * def creada = call read('classpath:helpers/crear-postulacion.feature')

    * def postulacionId = creada.postulacionId

    * def revisionArgs = { accion: 'avanzar', origen: 'Recibido', destino: 'En Revisión', codigoDestino: 'en_revision', motivoId: null, motivoEsperado: null }

    * def entrevistaArgs = { accion: 'avanzar', origen: 'En Revisión', destino: 'Entrevista', codigoDestino: 'entrevista', motivoId: null, motivoEsperado: null }

    * def contratoArgs = { accion: 'contratar', origen: 'Entrevista', destino: 'Contratado', codigoDestino: 'contratado', motivoId: null, motivoEsperado: null }

    * def revision = call read('classpath:helpers/mover-postulacion.feature') revisionArgs

    * def entrevista = call read('classpath:helpers/mover-postulacion.feature') entrevistaArgs

    * def contrato = call read('classpath:helpers/mover-postulacion.feature') contratoArgs

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * def itemSchema = read('classpath:schemas/mi-postulacion.json')

    * match response == { success: true, message: '#string', data: '#[1] itemSchema' }

    * match response.data[0].id == postulacionId
    * match response.data[0].estado.codigo == 'contratado'
    * match response.data[0].retirada == false
    * match response.data[0].puede_retirar == false
    * match response.data[0].vacante.id == vacanteId

    * def consulta = call read('classpath:helpers/consultar-historial.feature')

    * match consulta.historial.vacante.contratados == 1
    * match consulta.historial.vacante.cupos_disponibles == 1
    * match consulta.historial.postulaciones == '#[1]'

    * def registro = consulta.historial.postulaciones[0]

    * match registro.id == postulacionId
    * match registro.estado.codigo == 'contratado'

    * def estados = karate.map(registro.movimientos, function(m){ return m.estado.nombre })

    * match estados == ['En Revisión', 'Entrevista', 'Contratado']

    * def db = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match db.estado_nombre == 'Contratado'

    * def idsApi = karate.map(registro.movimientos, function(m){ return m.id })

    * def idsDb = karate.map(db.historial, function(m){ return m.id })

    * match idsApi == idsDb
