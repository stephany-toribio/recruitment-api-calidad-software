@ignore
Feature: Ejecutar y verificar movimiento de postulacion

  Scenario: Aplicar transicion valida

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def catalogos = BusinessFixtureBridge.catalogosNegocio(runId)

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match antes.estado_nombre == origen
    * match antes.retirada_en == null

    * def datosMovimiento = accion == 'rechazar' ? { motivo_rechazo_id: motivoId } : {}

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones', postulacionId, accion
    And header Authorization = 'Bearer ' + empresaToken
    And request datosMovimiento
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/postulacion-movimiento.json')

    * match response == schema
    * match response.data.id == postulacionId
    * match response.data.estado.codigo == codigoDestino
    * match response.data.estado.nombre == destino
    * match response.data.motivo_rechazo == motivoEsperado

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match despues.id == antes.id
    * match despues.candidato_id == antes.candidato_id
    * match despues.empleo_id == antes.empleo_id
    * match despues.fecha_postulacion == antes.fecha_postulacion
    * match despues.retirada_en == antes.retirada_en
    * match despues.estado_nombre == destino
    * match despues.estado_postulacion_id == catalogos.estados[destino]

    * assert despues.historial.length == antes.historial.length + 1

    * def previo = despues.historial.slice(0, antes.historial.length)

    * match previo == antes.historial

    * def movimiento = despues.historial[despues.historial.length - 1]

    * match movimiento.estado_nombre == destino
    * match movimiento.postulacion_id == postulacionId
    * match movimiento.estado_postulacion_id == catalogos.estados[destino]
    * match movimiento.fecha_cambio == '#string'

    * def motivoPersistido = accion == 'rechazar' ? motivoId : null

    * match movimiento.motivo_rechazo_id == motivoPersistido
