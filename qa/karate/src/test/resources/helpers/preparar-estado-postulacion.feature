@ignore
Feature: Preparar estados de postulacion mediante API

  Scenario: Generar precondicion funcional independiente

    * def preparada = call read('classpath:helpers/preparar-postulacion.feature')

    * def empresaToken = preparada.empresaToken
    * def empresaId = preparada.empresaId
    * def candidatoToken = preparada.candidatoToken
    * def candidatoId = preparada.candidatoId
    * def vacanteId = preparada.vacanteId
    * def postulacionId = preparada.postulacionId
    * def tituloVacante = preparada.tituloVacante

    * def BusinessFixtureBridge = Java.type('creo.BusinessFixtureBridge')
    * def catalogos = BusinessFixtureBridge.catalogosNegocio(runId)

    * def revisionArgs =
      """
      {
        accion: 'avanzar',
        origen: 'Recibido',
        destino: 'En Revisión',
        codigoDestino: 'en_revision',
        motivoId: null,
        motivoEsperado: null
      }
      """

    * def entrevistaArgs =
      """
      {
        accion: 'avanzar',
        origen: 'En Revisión',
        destino: 'Entrevista',
        codigoDestino: 'entrevista',
        motivoId: null,
        motivoEsperado: null
      }
      """

    * def contratoArgs =
      """
      {
        accion: 'contratar',
        origen: 'Entrevista',
        destino: 'Contratado',
        codigoDestino: 'contratado',
        motivoId: null,
        motivoEsperado: null
      }
      """

    * def motivo = catalogos.motivos[0]

    * def rechazoArgs =
      """
      {
        accion: 'rechazar',
        origen: 'En Revisión',
        destino: 'Rechazado',
        codigoDestino: 'rechazado',
        motivoId: '#(motivo.id)',
        motivoEsperado: '#(motivo.motivo)'
      }
      """

    * assert ['recibido','revision','contratado','rechazado','retirado'].includes(estadoObjetivo)

    * if (estadoObjetivo != 'recibido') karate.call('classpath:helpers/mover-postulacion.feature', revisionArgs)

    * if (estadoObjetivo == 'contratado') karate.call('classpath:helpers/mover-postulacion.feature', entrevistaArgs)

    * if (estadoObjetivo == 'contratado') karate.call('classpath:helpers/mover-postulacion.feature', contratoArgs)

    * if (estadoObjetivo == 'rechazado') karate.call('classpath:helpers/mover-postulacion.feature', rechazoArgs)

    * if (estadoObjetivo == 'retirado') karate.call('classpath:helpers/retirar-postulacion.feature')

    * def estadosEsperados =
      """
      {
        recibido: 'Recibido',
        revision: 'En Revisión',
        contratado: 'Contratado',
        rechazado: 'Rechazado',
        retirado: 'En Revisión'
      }
      """

    * def db = BusinessFixtureBridge.consultarPostulacion(runId, postulacionId)

    * match db.estado_nombre == estadosEsperados[estadoObjetivo]

    * assert estadoObjetivo == 'retirado' ? db.retirada_en != null : db.retirada_en == null
