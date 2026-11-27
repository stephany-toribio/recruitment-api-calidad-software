@ignore
Feature: Preparar candidato sintetico autenticado

  Scenario: Registrar candidato y obtener token

    * def candidato = call read('classpath:helpers/registrar-candidato.feature')

    * def candidatoId = candidato.candidatoId
    * def candidatoUsuarioId = candidato.usuarioId
    * def candidatoCorreo = candidato.correo
    * def correo = candidatoCorreo

    * def sesion = call read('classpath:helpers/login.feature') { correo: '#(correo)', rol: 'candidato' }

    * def candidatoToken = sesion.token

    * match candidatoId == '#number'
    * assert candidatoId > 0
    * match candidatoToken == '#string'
    * assert candidatoToken.length > 0
