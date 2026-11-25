@QA-17 @autorizacion
Feature: Autorizacion de servidor y proteccion de identidad

  Background:
    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')
    * def schema401 = read('classpath:schemas/unauthenticated.json')
    * def schemaError = read('classpath:schemas/error-negocio.json')
    * def schema422 = read('classpath:schemas/validation-error.json')


  @CP-15-01 @RNF-01 @demo
  Scenario: Bloquear consulta protegida sin token

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    When method get
    Then status 401
    And assert responseTime < responseTimeLimit

    * match response == schema401
    * match response.error_code == 'UNAUTHENTICATED'

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes


  @CP-15-01 @RNF-01
  Scenario: Bloquear consulta protegida con token invalido

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer token-invalido-qa17'
    When method get
    Then status 401
    And assert responseTime < responseTimeLimit

    * match response == schema401
    * match response.error_code == 'UNAUTHENTICATED'

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes


  @CP-15-02 @RNF-01
  Scenario: Candidato no puede consultar vacantes empresariales

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoToken = candidato.candidatoToken

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes'
    And header Authorization = 'Bearer ' + candidatoToken
    When method get
    Then status 403
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'FORBIDDEN'

    * def despues = BusinessFixtureBridge.cantidades(runId)
    * match despues == antes


  @CP-15-02 @RNF-01
  Scenario: Candidato no puede pausar una vacante

    * def publicada = call read('classpath:helpers/publicar-vacante.feature')

    * def vacanteId = publicada.vacanteId

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoToken = candidato.candidatoToken

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pausar'
    And header Authorization = 'Bearer ' + candidatoToken
    And request {}
    When method patch
    Then status 403
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'FORBIDDEN'

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)
    * match despues == antes
    * match despues.estado_empleo == 'activa'


  @CP-15-03 @RNF-01 @demo
  Scenario: Empresa no puede pausar vacante ajena

    * def publicada = call read('classpath:helpers/publicar-vacante.feature')

    * def vacanteId = publicada.vacanteId

    * def propietariaId = publicada.empresaId

    * def externa = call read('classpath:helpers/preparar-empresa.feature')

    * assert externa.empresaId != propietariaId

    * def antes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match antes.empresa_id == propietariaId

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'pausar'
    And header Authorization = 'Bearer ' + externa.empresaToken
    And request {}
    When method patch
    Then status 403
    And assert responseTime < responseTimeLimit

    * match response == schemaError
    * match response.error_code == 'FORBIDDEN'

    * def despues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match despues == antes
    * match despues.estado_empleo == 'activa'
    * match despues.empresa_id == propietariaId


  @CP-15-04 @RNF-01
  Scenario: Rechazar inyeccion de identidad en una postulacion

    * def publicada = call read('classpath:helpers/publicar-vacante.feature')

    * def vacanteId = publicada.vacanteId
    * def tituloVacante = publicada.tituloVacante

    * def solicitante = call read('classpath:helpers/preparar-candidato.feature')

    * def ajeno = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoId = solicitante.candidatoId
    * def candidatoToken = solicitante.candidatoToken
    * def candidatoAjenoId = ajeno.candidatoId

    * assert candidatoId != candidatoAjenoId

    * def antes = BusinessFixtureBridge.cantidades(runId)

    * def vacanteAntes = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match vacanteAntes.postulaciones == 0

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    And request { vacante_id: '#(vacanteId)', candidato_id: '#(candidatoAjenoId)' }
    When method post
    Then status 422
    And assert responseTime < responseTimeLimit

    * match response == schema422
    * match response.errors.candidato_id == '#[] #string'
    * match karate.keysOf(response.errors) == ['candidato_id']

    * def despues = BusinessFixtureBridge.cantidades(runId)

    * match despues == antes

    * def vacanteDespues = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match vacanteDespues == vacanteAntes

    # Control positivo: el token determina la identidad.
    * def creada = call read('classpath:helpers/crear-postulacion.feature')

    * def db = BusinessFixtureBridge.consultarPostulacion(runId, creada.postulacionId)

    * match db.candidato_id == candidatoId
    * assert db.candidato_id != candidatoAjenoId
    * match db.empleo_id == vacanteId

    * def resultado = BusinessFixtureBridge.cantidades(runId)

    * match resultado.postulaciones == (antes.postulaciones + 1)
