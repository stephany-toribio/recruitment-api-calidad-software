@ignore
Feature: Preparar vacante en condicion inicial

  Scenario: Crear precondicion mediante operaciones HTTP

    * def empresa = call read('classpath:helpers/preparar-empresa.feature')

    * def empresaId = empresa.empresaId
    * def empresaToken = empresa.empresaToken

    * def admin = call read('classpath:helpers/preparar-admin.feature')

    * def adminId = admin.adminId
    * def adminToken = admin.adminToken

    * def creada = call read('classpath:helpers/crear-vacante.feature')

    * def vacanteId = creada.vacanteId
    * def tituloVacante = creada.datos.titulo

    * assert ['sin_revision','no_aprobada','sin_pago','publicada'].includes(condicionInicial)

    * if (condicionInicial == 'no_aprobada') karate.call('classpath:helpers/no-aprobar-vacante.feature')

    * if (condicionInicial == 'sin_pago' || condicionInicial == 'publicada') karate.call('classpath:helpers/aprobar-vacante.feature')

    * if (condicionInicial == 'publicada') karate.call('classpath:helpers/pagar-vacante.feature')

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')

    * def db = BusinessFixtureBridge.consultarVacante(runId, vacanteId)

    * match db.empresa_id == empresaId
    * match db.postulaciones == 0

    * assert condicionInicial == 'sin_revision' ? db.revisiones.length == 0 : db.revisiones.length == 1

    * assert condicionInicial == 'publicada' ? db.pagos.length == 1 : db.pagos.length == 0
