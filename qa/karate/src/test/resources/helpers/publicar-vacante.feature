@ignore
Feature: Preparar vacante disponible mediante flujo real

  Scenario: Crear, aprobar y pagar vacante

    * def empresa = call read('classpath:helpers/preparar-empresa.feature')
    * def empresaId = empresa.empresaId
    * def empresaToken = empresa.empresaToken

    * def admin = call read('classpath:helpers/preparar-admin.feature')
    * def adminId = admin.adminId
    * def adminToken = admin.adminToken

    * def creada = call read('classpath:helpers/crear-vacante.feature')
    * def vacanteId = creada.vacanteId
    * def tituloVacante = creada.datos.titulo

    * def aprobada = call read('classpath:helpers/aprobar-vacante.feature')

    * def pagada = call read('classpath:helpers/pagar-vacante.feature')
    * def pagoId = pagada.pagoId
