@QA-16 @moderacion
Feature: Moderacion administrativa de vacantes

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def empresa = call read('classpath:helpers/preparar-empresa.feature')
    * def empresaId = empresa.empresaId
    * def empresaToken = empresa.empresaToken

    * def admin = call read('classpath:helpers/preparar-admin.feature')
    * def adminId = admin.adminId
    * def adminToken = admin.adminToken

    * def creada = call read('classpath:helpers/crear-vacante.feature')
    * def vacanteId = creada.vacanteId

    * def habilidadSchema =
      """
      {
        id: '#number',
        nombre: '#string',
        tipo_habilidad: '#? _ == "tecnica" || _ == "blanda"',
        requisito: '#? _ == "must_have" || _ == "nice_to_have"'
      }
      """


  @CP-08-01 @RF-05
  Scenario: Administrador aprueba vacante

    * def antes = BusinessFixtureBridge.cantidades(runId)

    * def aprobada = call read('classpath:helpers/aprobar-vacante.feature')

    * def despues = BusinessFixtureBridge.cantidades(runId)

    * match despues.revisiones == (antes.revisiones + 1)
    * match despues.pagos == antes.pagos
    * match despues.vacantes == antes.vacantes


  @CP-08-02 @RF-05
  Scenario: Administrador no aprueba con motivo

    * def motivo = 'Descripcion insuficiente de funciones del puesto'

    Given url apiUrl
    And path 'admin', 'vacantes', vacanteId, 'no-aprobar'
    And header Authorization = 'Bearer ' + adminToken
    And request { motivo: '#(motivo)' }
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/vacante-no-aprobada.json')

    * match response == schema
    * match response.data.id == vacanteId
    * match response.data.empresa.id == empresaId
    * match response.data.revision.motivo == motivo

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.revisiones == '#[1]'
    * match db.revisiones[0].admin_id == adminId
    * match db.revisiones[0].decision == 'no_aprobada'
    * match db.revisiones[0].motivo == motivo
    * match db.pagos == []

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit
    And match response.data.estado.general == 'no_aprobada'
    And match response.data.estado.motivo_no_aprobacion == motivo
    And match response.data.estado.publicada_en == null


  @CP-08-03 @RF-05
  Scenario Outline: Impedir rechazo administrativo sin motivo valido

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * def datos = <body>

    Given url apiUrl
    And path 'admin', 'vacantes', vacanteId, 'no-aprobar'
    And header Authorization = 'Bearer ' + adminToken
    And request datos
    When method patch
    Then status 422
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')

    * match response == schema
    * match response.errors.motivo == '#[] #string'
    * match karate.keysOf(response.errors) == ['motivo']

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match despues == antes

    Examples:
      | body!                 |
      | {}                    |
      | { motivo: '   ' }    |


  @CP-08-04 @RF-05 @RNF-01
  Scenario: Empresa no puede aprobar vacante

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    Given url apiUrl
    And path 'admin', 'vacantes', vacanteId, 'aprobar'
    And header Authorization = 'Bearer ' + empresaToken
    And request {}
    When method patch
    Then status 403
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/error-negocio.json')

    * match response == schema
    * match response.error_code == 'FORBIDDEN'

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match despues == antes
