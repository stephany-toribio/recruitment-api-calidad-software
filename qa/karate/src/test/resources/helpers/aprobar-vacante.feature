@ignore
Feature: Aprobar vacante mediante administrador autenticado

  Scenario: Aprobar vacante pendiente

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def habilidadSchema =
      """
      {
        id: '#number',
        nombre: '#string',
        tipo_habilidad: '#? _ == "tecnica" || _ == "blanda"',
        requisito: '#? _ == "must_have" || _ == "nice_to_have"'
      }
      """

    Given url apiUrl
    And path 'admin', 'vacantes', vacanteId, 'aprobar'
    And header Authorization = 'Bearer ' + adminToken
    And request {}
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/vacante-aprobada.json')
    * match response == schema
    * match response.data.id == vacanteId
    * match response.data.empresa.id == empresaId

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.revisiones == '#[1]'
    * match db.revisiones[0].decision == 'aprobada'
    * match db.revisiones[0].admin_id == adminId
    * match db.revisiones[0].motivo == null
    * match db.revisiones[0].invalidada_en == null
    * match db.pagos == []
    * match db.estado_empleo == 'activa'

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit
    And match response.data.estado.general == 'pendiente_pago'
    And match response.data.estado.revision == 'aprobada'
    And match response.data.estado.pago == 'sin_pago'
    And match response.data.estado.publicada_en == null
