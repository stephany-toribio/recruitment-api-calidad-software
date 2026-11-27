@QA-16 @pagos
Feature: Validacion de pagos simulados de vacantes

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

    * def fixturePago = BusinessFixtureBridge.datosPago(runId)
    * def datosPago = fixturePago.datos


  @CP-09-01 @RF-05
  Scenario: Completar pago simulado exitoso

    * def aprobada = call read('classpath:helpers/aprobar-vacante.feature')

    * def antes = BusinessFixtureBridge.cantidades(runId)

    * def pagada = call read('classpath:helpers/pagar-vacante.feature')

    * def despues = BusinessFixtureBridge.cantidades(runId)

    * match despues.pagos == (antes.pagos + 1)
    * match despues.vacantes == antes.vacantes
    * match despues.revisiones == antes.revisiones


  @CP-09-02 @RF-05
  Scenario: Impedir pago de vacante pendiente

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pago'
    And header Authorization = 'Bearer ' + empresaToken
    And request datosPago
    When method post
    Then status 409
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/error-negocio.json')
    * match response == schema
    * match response.error_code == 'VACANCY_NOT_APPROVED'

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match despues == antes


  @CP-09-02 @RF-05
  Scenario: Impedir pago de vacante no aprobada

    Given url apiUrl
    And path 'admin', 'vacantes', vacanteId, 'no-aprobar'
    And header Authorization = 'Bearer ' + adminToken
    And request { motivo: 'Vacante rechazada para prueba QA16' }
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit
    And match response.data.revision.estado == 'no_aprobada'

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pago'
    And header Authorization = 'Bearer ' + empresaToken
    And request datosPago
    When method post
    Then status 409
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/error-negocio.json')
    * match response == schema
    * match response.error_code == 'VACANCY_NOT_APPROVED'

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match despues == antes


  @CP-09-03 @RF-05
  Scenario Outline: Rechazar datos invalidos de tarjeta

    * def aprobada = call read('classpath:helpers/aprobar-vacante.feature')

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * def campo = '<campo>'
    * def valor = <valor>
    * eval datosPago[campo] = valor

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pago'
    And header Authorization = 'Bearer ' + empresaToken
    And request datosPago
    When method post
    Then status 422
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/validation-error.json')

    * match response == schema

    * def errores = response.errors[campo]
    * match errores == '#[] #string'
    * assert errores.length > 0

    * def camposEsperados = []
    * eval camposEsperados.push(campo)
    * match karate.keysOf(response.errors) == camposEsperados

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match despues == antes

    Examples:
      | campo          | valor!                         |
      | numero_tarjeta | '42424242'                     |
      | numero_tarjeta | '4242424242424241'             |
      | vencimiento    | fixturePago.vencimiento_pasado  |
      | cvv            | '12'                           |


  @CP-09-04 @RF-05 @RNF-01
  Scenario: Empresa no puede pagar vacante ajena

    * def aprobada = call read('classpath:helpers/aprobar-vacante.feature')

    * def externa = call read('classpath:helpers/preparar-empresa.feature')

    * assert externa.empresaId != empresaId

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pago'
    And header Authorization = 'Bearer ' + externa.empresaToken
    And request datosPago
    When method post
    Then status 403
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/error-negocio.json')
    * match response == schema
    * match response.error_code == 'FORBIDDEN'

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match despues == antes


  @CP-09-05 @RF-05 @demo
  Scenario: Impedir segundo pago sobre vacante publicada

    * def aprobada = call read('classpath:helpers/aprobar-vacante.feature')

    * def pagada = call read('classpath:helpers/pagar-vacante.feature')

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.pagos == '#[1]'

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pago'
    And header Authorization = 'Bearer ' + empresaToken
    And request datosPago
    When method post
    Then status 409
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/error-negocio.json')
    * match response == schema
    * match response.error_code == 'VACANCY_ALREADY_PAID'
    * match response.message == 'La vacante ya tiene un pago exitoso.'

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match despues == antes
    * match despues.pagos == '#[1]'
    * match despues.pagos[0].resultado == 'exitoso'
