@QA-17 @perfiles
Feature: Perfil propio y control de pertenencia

  Background:
    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')
    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def schemaPerfil = read('classpath:schemas/registro-candidato.json')
    * def schemaError = read('classpath:schemas/error-negocio.json')


  @CP-06-01
  Scenario: Consultar perfil propio autenticado

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def antes = BusinessFixtureBridge.consultarCandidato(runId, candidatoId)

    Given url apiUrl
    And path 'candidatos', 'me'
    And header Authorization = 'Bearer ' + candidatoToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response == schemaPerfil
    * match response.data.id == candidatoId
    * match response.data.usuario_id == antes.usuario.id
    * match response.data.correo == antes.usuario.correo
    * match response.data.nombre == antes.perfil.nombre
    * match response.data.apellidos == antes.perfil.apellidos
    * match response.data.dni == antes.perfil.dni
    * match response.data.celular == antes.perfil.celular

    * def despues = BusinessFixtureBridge.consultarCandidato(runId, candidatoId)
    * match despues == antes


  @CP-06-02
  Scenario: Actualizar celular del candidato propietario

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def antes = BusinessFixtureBridge.consultarCandidato(runId, candidatoId)

    * def datosNuevos = FixtureBridge.datos(runId, 'candidato')
    * def celularNuevo = datosNuevos.datos.celular

    * assert celularNuevo != antes.perfil.celular

    Given url apiUrl
    And path 'candidatos', 'me'
    And header Authorization = 'Bearer ' + candidatoToken
    And request { celular: '#(celularNuevo)' }
    When method put
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response == schemaPerfil
    * match response.data.id == candidatoId
    * match response.data.celular == celularNuevo

    * def despues = BusinessFixtureBridge.consultarCandidato(runId, candidatoId)

    * match despues.perfil.celular == celularNuevo
    * match despues.usuario == antes.usuario
    * match despues.perfil.dni == antes.perfil.dni
    * match despues.perfil.nombre == antes.perfil.nombre
    * match despues.estudios == antes.estudios
    * match despues.idiomas == antes.idiomas
    * match despues.habilidades == antes.habilidades


  @CP-06-03
  Scenario: Rechazar consulta de postulacion inexistente

    * def empresa = call read('classpath:helpers/preparar-empresa.feature')

    * def inexistente = BusinessFixtureBridge.postulacionInexistente(runId)

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas', 'me', 'postulaciones', inexistente.id, 'perfil'
    And header Authorization = 'Bearer ' + empresa.empresaToken
    When method get
    Then status 404
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'RESOURCE_NOT_FOUND'

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes


  @CP-06-03
  Scenario: Impedir consulta empresarial de postulante ajeno

    * def preparada = call read('classpath:helpers/preparar-postulacion.feature')

    * def externa = call read('classpath:helpers/preparar-empresa.feature')

    * assert externa.empresaId != preparada.empresaId

    * def antes = BusinessFixtureBridge.consultarPostulacion(runId, preparada.postulacionId)
    * def perfilAntes = BusinessFixtureBridge.consultarCandidato(runId, preparada.candidatoId)

    * def cantidadesAntes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas', 'me', 'postulaciones', preparada.postulacionId, 'perfil'
    And header Authorization = 'Bearer ' + externa.empresaToken
    When method get
    Then status 403
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'FORBIDDEN'

    * def despues = BusinessFixtureBridge.consultarPostulacion(runId, preparada.postulacionId)
    * def perfilDespues = BusinessFixtureBridge.consultarCandidato(runId, preparada.candidatoId)

    * match despues == antes
    * match perfilDespues == perfilAntes

    * def cantidadesDespues = BusinessFixtureBridge.cantidades(runId)
    * match cantidadesDespues == cantidadesAntes

    # Control positivo con la empresa propietaria.
    Given url apiUrl
    And path 'empresas', 'me', 'postulaciones', preparada.postulacionId, 'perfil'
    And header Authorization = 'Bearer ' + preparada.empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.success == true
    * match response.data == '#object'


  @CP-06-04
  Scenario: Ignorar identificadores ajenos e intento de elevar rol

    * def propio = call read('classpath:helpers/preparar-candidato.feature')
    * def ajeno = call read('classpath:helpers/preparar-candidato.feature')
    * def admin = call read('classpath:helpers/preparar-admin.feature')

    * assert propio.candidatoId != ajeno.candidatoId

    * def antesPropio = BusinessFixtureBridge.consultarCandidato(runId, propio.candidatoId)
    * def antesAjeno = BusinessFixtureBridge.consultarCandidato(runId, ajeno.candidatoId)

    * def datosNuevos = FixtureBridge.datos(runId, 'candidato')
    * def celularNuevo = datosNuevos.datos.celular

    * assert celularNuevo != antesPropio.perfil.celular
    * assert celularNuevo != antesAjeno.perfil.celular

    * def payload =
      """
      {
        celular: '#(celularNuevo)',
        id: '#(ajeno.candidatoId)',
        candidato_id: '#(ajeno.candidatoId)',
        usuario_id: '#(antesAjeno.usuario.id)',
        rol: 'admin',
        rol_id: '#(antesPropio.usuario.rol_id + 1000)'
      }
      """

    Given url apiUrl
    And path 'candidatos', 'me'
    And header Authorization = 'Bearer ' + propio.candidatoToken
    And request payload
    When method put
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response == schemaPerfil
    * match response.data.id == propio.candidatoId
    * match response.data.usuario_id == antesPropio.usuario.id
    * match response.data.celular == celularNuevo

    * def despuesPropio = BusinessFixtureBridge.consultarCandidato(runId, propio.candidatoId)
    * def despuesAjeno = BusinessFixtureBridge.consultarCandidato(runId, ajeno.candidatoId)

    * match despuesPropio.perfil.celular == celularNuevo
    * match despuesPropio.usuario == antesPropio.usuario
    * match despuesPropio.perfil.usuario_id == antesPropio.perfil.usuario_id
    * match despuesPropio.perfil.dni == antesPropio.perfil.dni
    * match despuesAjeno == antesAjeno

    # Comprobar que el token conserva el rol candidato.
    Given url apiUrl
    And path 'empresas', 'me', 'vacantes'
    And header Authorization = 'Bearer ' + propio.candidatoToken
    When method get
    Then status 403
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'FORBIDDEN'
