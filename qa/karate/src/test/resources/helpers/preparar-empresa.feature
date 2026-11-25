@ignore
Feature: Preparar empresa sintetica autenticada

  Scenario: Registrar empresa y obtener token

    * def empresa = call read('classpath:helpers/registrar-empresa.feature')
    * def correo = empresa.correo
    * def empresaId = empresa.empresaId

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'empresa' }

    * def empresaToken = sesion.token

    * match empresaId == '#number'
    * assert empresaId > 0
    * match empresaToken == '#string'
    * assert empresaToken.length > 0
