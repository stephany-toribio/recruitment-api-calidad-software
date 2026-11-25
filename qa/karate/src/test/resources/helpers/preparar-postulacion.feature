@ignore
Feature: Preparar postulacion mediante flujo real de negocio

  Scenario: Crear vacante publicada y candidato postulante

    * def publicada = call read('classpath:helpers/publicar-vacante.feature')

    * def empresaId = publicada.empresaId
    * def empresaToken = publicada.empresaToken
    * def adminId = publicada.adminId
    * def adminToken = publicada.adminToken
    * def vacanteId = publicada.vacanteId
    * def tituloVacante = publicada.tituloVacante

    * def candidato = call read('classpath:helpers/preparar-candidato.feature')

    * def candidatoId = candidato.candidatoId
    * def candidatoToken = candidato.candidatoToken
    * def candidatoCorreo = candidato.candidatoCorreo

    * def creada = call read('classpath:helpers/crear-postulacion.feature')

    * def postulacionId = creada.postulacionId
