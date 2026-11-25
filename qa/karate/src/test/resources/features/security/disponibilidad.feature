@QA-17 @disponibilidad
Feature: Reglas de disponibilidad de vacantes

  Background:
    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')


  @CP-10-01
  Scenario: Impedir postulacion a vacante sin revision

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'sin_revision' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * match preparada.db.revisiones == []
    * match preparada.db.pagos == []

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-02
  Scenario: Impedir postulacion a vacante no aprobada

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'no_aprobada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * match preparada.db.revisiones[0].decision == 'no_aprobada'
    * match preparada.db.pagos == []

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-03
  Scenario: Impedir postulacion a vacante aprobada sin pago

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'sin_pago' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * match preparada.db.revisiones[0].decision == 'aprobada'
    * match preparada.db.pagos == []

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-04
  Scenario: Impedir postulacion con unico pago fallido

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * def db = BusinessFixtureBridge.prepararIndisponibilidad(runId, vacanteId, 'pago_fallido', [])

    * match db.pagos == '#[1]'
    * match db.pagos[0].resultado == 'fallido'
    * match db.revisiones[0].decision == 'aprobada'

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-05
  Scenario: Impedir postulacion a vacante pausada

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * def pausa = call read('classpath:helpers/pausar-vacante.feature')

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.estado_empleo == 'pausada'

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-06
  Scenario: Impedir postulacion a vacante finalizada

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'finalizar'
    And header Authorization = 'Bearer ' + empresaToken
    And request {}
    When method patch
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.success == true
    * match response.data.id == vacanteId
    * match response.data.estado.general == 'finalizada'

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.estado_empleo == 'finalizada'

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-07
  Scenario: Impedir postulacion a vacante vencida

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * def db = BusinessFixtureBridge.prepararIndisponibilidad(runId, vacanteId, 'vencida', [])

    * def ZoneId = Java.type('java.time.ZoneId')
    * def LocalDate = Java.type('java.time.LocalDate')
    * def hoy = LocalDate.now(ZoneId.of('America/Lima')).toString()

    * assert db.fecha_fin_vigencia < hoy

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-08
  Scenario: Impedir postulacion antes del inicio de vigencia

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * def db = BusinessFixtureBridge.prepararIndisponibilidad(runId, vacanteId, 'futura', [])

    * def ZoneId = Java.type('java.time.ZoneId')
    * def LocalDate = Java.type('java.time.LocalDate')
    * def hoy = LocalDate.now(ZoneId.of('America/Lima')).toString()

    * assert db.fecha_inicio_vigencia > hoy

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-09
  Scenario: Impedir postulacion con todos los cupos ocupados

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * def contratadoUno = call read('classpath:helpers/preparar-candidato.feature')
    * def contratadoDos = call read('classpath:helpers/preparar-candidato.feature')

    * assert contratadoUno.candidatoId != contratadoDos.candidatoId

    * def ids = []
    * eval ids.push(contratadoUno.candidatoId)
    * eval ids.push(contratadoDos.candidatoId)

    * match each ids == '#number'

    * def db = BusinessFixtureBridge.prepararIndisponibilidad(runId, vacanteId, 'completa', ids)

    * match db.max_contrataciones == 2
    * match db.postulaciones == 2

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes', vacanteId, 'postulaciones'
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 200
    And assert responseTime < responseTimeLimit

    * match response.success == true
    * match response.data.vacante.contratados == 2
    * match response.data.vacante.cupos_disponibles == 0

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-10
  Scenario: Impedir postulacion a empresa suspendida

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * def db = BusinessFixtureBridge.prepararIndisponibilidad(runId, vacanteId, 'empresa_suspendida', [])

    * match db.estado_empleo == 'activa'

    Given url apiUrl
    And path 'empresas', 'me', 'vacantes'
    And header Authorization = 'Bearer ' + empresaToken
    When method get
    Then status 403
    And assert responseTime < responseTimeLimit

    * def schema = read('classpath:schemas/account-not-active.json')
    * match response == schema
    * match response.reason == 'ACCOUNT_NOT_ACTIVE'

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')


  @CP-10-11
  Scenario: Impedir postulacion a vacante eliminada

    * def preparada = call read('classpath:helpers/preparar-vacante-condicion.feature') { condicionInicial: 'publicada' }

    * def vacanteId = preparada.vacanteId
    * def empresaToken = preparada.empresaToken


    * def eliminada = call read('classpath:helpers/eliminar-vacante.feature')

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.eliminado_en == '#string'

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')
    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken

    * def rechazo = call read('classpath:helpers/postulacion-no-disponible.feature')
