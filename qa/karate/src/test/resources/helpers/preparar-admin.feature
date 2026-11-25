@ignore
Feature: Preparar administrador sintetico autenticado

  Scenario: Crear administrador y obtener token

    * def FixtureBridge = Java.type('creo.FixtureBridge')
    * def System = Java.type('java.lang.System')
    * def password = System.getenv('KARATE_FIXTURE_PASSWORD')

    * def admin = FixtureBridge.crearAdmin(runId, password)
    * match admin.success == true

    * def correo = admin.correo
    * def adminId = admin.usuario_id

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'admin' }

    * def adminToken = sesion.token

    * match adminId == '#number'
    * assert adminId > 0
    * match adminToken == '#string'
    * assert adminToken.length > 0
