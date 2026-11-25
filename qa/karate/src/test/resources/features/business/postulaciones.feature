@QA-16 @postulaciones
Feature: Registro de postulaciones de candidatos

  Background:

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')
    * def FixtureBridge = Java.type('creo.FixtureBridge')

    * def publicada = call read('classpath:helpers/publicar-vacante.feature')
    * def vacanteId = publicada.vacanteId
    * def tituloVacante = publicada.tituloVacante

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken
    * def candidatoCorreo = candidato.candidatoCorreo


  @CP-12-01 @RF-07
  Scenario: Candidato postula exitosamente

    * def creada = call read('classpath:helpers/crear-postulacion.feature')

    * def db = BusinessFixtureBridge.consultarPostulacion(runId, creada.postulacionId)

    * match db.estado_nombre == 'Recibido'
    * match db.candidato_id == candidatoId
    * match db.empleo_id == vacanteId


  @CP-12-02 @RF-07 @RNF-01 @demo
  Scenario: Impedir postulacion de candidato suspendido

    * def suspendido = FixtureBridge.suspender(runId, candidatoCorreo)

    * match suspendido.success == true
    * match suspendido.estado == 'suspendido'

    * def antes = BusinessFixtureBridge.cantidades(runId)

    Given url apiUrl
    And path 'candidatos', 'me', 'postulaciones'
    And header Authorization = 'Bearer ' + candidatoToken
    And request { vacante_id: '#(vacanteId)' }
    When method post
    Then status 403
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/account-not-active.json')

    * match response == schema

    * def despues = BusinessFixtureBridge.cantidades(runId)

    * match despues == antes
