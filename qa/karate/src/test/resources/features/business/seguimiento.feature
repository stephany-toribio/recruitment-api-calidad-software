@QA-16 @seguimiento
Feature: Seguimiento de postulaciones del candidato

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def preparada = call read('classpath:helpers/preparar-postulacion.feature')

    * def empresaToken = preparada.empresaToken
    * def empresaId = preparada.empresaId
    * def adminId = preparada.adminId
    * def adminToken = preparada.adminToken
    * def candidatoToken = preparada.candidatoToken
    * def candidatoId = preparada.candidatoId
    * def vacanteId = preparada.vacanteId
    * def tituloVacante = preparada.tituloVacante
    * def postulacionId = preparada.postulacionId

    * def itemSchema = read('classpath:schemas/mi-postulacion.json')


  @CP-21-01 @RF-09
  Scenario: Consultar postulaciones activas e historicas

    * def segundaVacante = call read('classpath:helpers/crear-vacante.feature')

    * def segundaVacanteId = segundaVacante.vacanteId
    * def segundoTitulo = segundaVacante.datos.titulo

    * assert segundaVacanteId != vacanteId

    * def segundaAprobada = call read('classpath:helpers/aprobar-vacante.feature') { vacanteId: '#(segundaVacanteId)' }

    * def segundaPagada = call read('classpath:helpers/pagar-vacante.feature') { vacanteId: '#(segundaVacanteId)' }

    * def segunda = call read('classpath:helpers/crear-postulacion.feature') { vacanteId: '#(segundaVacanteId)', tituloVacante: '#(segundoTitulo)' }

    * def segundaId = segunda.postulacionId

    * def retiro = call read('classpath:helpers/retirar-postulacion.feature') { postulacionId: '#(segundaId)' }

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response == { success: true, message: '#string', data: '#[2] itemSchema' }

    * def ids = karate.map(response.data, function(p){ return p.id })

    * def esperados = []
    * eval esperados.push(segundaId)
    * eval esperados.push(postulacionId)
    * match each esperados == '#number'
    * assert segundaId != postulacionId

    * match ids contains only esperados

    * def activas = karate.filter(response.data, function(p){ return p.id == postulacionId })

    * def retiradas = karate.filter(response.data, function(p){ return p.id == segundaId })

    * match activas == '#[1]'
    * match retiradas == '#[1]'

    * match activas[0].estado.codigo == 'recibido'
    * match activas[0].retirada == false
    * match activas[0].retirada_en == null
    * match activas[0].vacante.id == vacanteId
    * match activas[0].vacante.titulo == tituloVacante

    * match retiradas[0].retirada == true
    * match retiradas[0].retirada_en == '#string'
    * match retiradas[0].puede_retirar == false

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes


  @CP-21-02 @RF-09
  Scenario Outline: Consultar detalle historico de vacante no disponible

    * def operacion = '<operacion>'
    * def estadoEsperado = '<estado>'

    * def helperGestion = operacion == 'pausar' ? 'classpath:helpers/pausar-vacante.feature' : 'classpath:helpers/eliminar-vacante.feature'

    * def gestion = call read(helperGestion)

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones', postulacionId, 'vacante'
    And header Authorization = 'Bearer ' + candidatoToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.success == true
    * match response.message == 'Detalle histórico de vacante obtenido correctamente.'
    * match response.data.id == vacanteId
    * match response.data.titulo == tituloVacante
    * match response.data.estado == estadoEsperado
    * match response.data.modo_consulta == 'historico'
    * match response.data.puede_postular == false
    * match response.data.postulacion.id == postulacionId
    * match response.data.postulacion.retirada == false

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match despues == antes

    Examples:
      | operacion | estado    |
      | pausar    | pausada   |
      | eliminar  | eliminada |
