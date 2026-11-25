@ignore
Feature: Registrar vacante sintetica mediante API

  Scenario: Crear vacante pendiente de revision

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def fixture = BusinessFixtureBridge.datosVacante(runId)
    * match fixture.success == true
    * def datos = fixture.datos

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
    And path 'empresas', 'me', 'vacantes'
    And header Authorization = 'Bearer ' + empresaToken
    And request datos
    When method post

    * def camposError = responseStatus == 422 && response.errors ? karate.keysOf(response.errors) : []
    * if (responseStatus != 201) karate.fail('HTTP inesperado: ' + responseStatus + '; campos: ' + camposError.join(', '))

    Then status 201
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/vacante-creada.json')
    And match response == schema

    * def vacanteId = response.data.id

    * assert vacanteId > 0

    And match response.data.titulo == datos.titulo
    And match response.data.descripcion == datos.descripcion.trim()
    And match response.data.nivel_puesto.id == datos.nivel_puesto_id
    And match response.data.area_profesional.id == datos.area_estudio_id
    And match response.data.salario_min == datos.salario_min
    And match response.data.salario_max == datos.salario_max
    And match response.data.max_contrataciones == datos.max_contrataciones
    And match response.data.fecha_inicio_vigencia == datos.fecha_inicio_vigencia
    And match response.data.fecha_fin_vigencia == datos.fecha_fin_vigencia
    And match response.data.requisitos == datos.requisitos
    And match response.data.beneficios == datos.beneficios

    * def habilidadesRespuesta = karate.map(response.data.habilidades, function(h){ return { habilidad_id: h.id, requisito: h.requisito } })
    * match habilidadesRespuesta contains only datos.habilidades

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.success == true
    * match db.id == vacanteId
    * match db.empresa_id == empresaId
    * match db.titulo == datos.titulo
    * match db.descripcion == datos.descripcion.trim()
    * match db.estado_empleo == 'activa'
    * match db.salario_min == datos.salario_min
    * match db.salario_max == datos.salario_max
    * match db.max_contrataciones == datos.max_contrataciones
    * match db.nivel_puesto_id == datos.nivel_puesto_id
    * match db.area_estudio_id == datos.area_estudio_id
    * match db.fecha_inicio_vigencia == datos.fecha_inicio_vigencia
    * match db.fecha_fin_vigencia == datos.fecha_fin_vigencia
    * match db.habilidades contains only datos.habilidades
    * match db.revisiones == []
    * match db.pagos == []
    * match db.postulaciones == 0
    * match db.eliminado_en == null
