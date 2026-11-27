@ignore
Feature: Rechazar administrativamente vacante

  Scenario: No aprobar vacante sintetica

    * def motivo = 'Descripcion insuficiente de funciones del puesto'

    Given url apiUrl
    And path 'admin', 'vacantes', vacanteId, 'no-aprobar'
    And header Authorization = 'Bearer ' + adminToken
    And request { motivo: '#(motivo)' }
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit

    * def habilidadSchema =
      """
      {
        id: '#number',
        nombre: '#string',
        tipo_habilidad: '#? _ == "tecnica" || _ == "blanda"',
        requisito: '#? _ == "must_have" || _ == "nice_to_have"'
      }
      """

    * def schema = read('classpath:schemas/vacante-no-aprobada.json')

    * match response == schema
    * match response.data.id == vacanteId
    * match response.data.revision.motivo == motivo

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.revisiones == '#[1]'
    * match db.revisiones[0].decision == 'no_aprobada'
    * match db.revisiones[0].admin_id == adminId
    * match db.pagos == []
