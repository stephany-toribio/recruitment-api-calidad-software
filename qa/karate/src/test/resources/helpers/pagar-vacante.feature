@ignore
Feature: Realizar pago simulado de vacante aprobada

  Scenario: Registrar pago exitoso y publicar vacante

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def fixturePago = BusinessFixtureBridge.datosPago(runId)
    * match fixturePago.success == true

    * def datosPago = fixturePago.datos

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.pagos == []

    * assert fixturePago.tarifa > 0
    * assert fixturePago.unidad == 'puesto' || fixturePago.unidad == 'vacante'

    * def montoEsperado = fixturePago.unidad == 'puesto' ? fixturePago.tarifa * antes.max_contrataciones : fixturePago.tarifa

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
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pago'
    And header Authorization = 'Bearer ' + empresaToken
    And request datosPago
    When method post
    Then status 201
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/pago-exitoso.json')

    * match response == schema
    * match response.data.vacante.id == vacanteId
    * match response.data.pago.monto == montoEsperado

    * def pagoId = response.data.pago.id

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match despues.pagos == '#[1]'
    * match despues.pagos[0].id == pagoId
    * match despues.pagos[0].resultado == 'exitoso'
    * match Number(despues.pagos[0].monto) == montoEsperado
    * match despues.pagos[0].moneda == fixturePago.moneda
    * match despues.pagos[0].tarjeta_ultimos4 == '4242'
    * match despues.revisiones == antes.revisiones

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit
    And match response.data.estado.general == 'publicada'
    And match response.data.estado.pago == 'pagado'
    And match response.data.estado.revision == 'aprobada'
    And match response.data.estado.publicada_en == '#string'
