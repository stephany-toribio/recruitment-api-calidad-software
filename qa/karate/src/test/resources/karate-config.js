function fn() {
  var env = karate.env || 'local';

  var System = Java.type('java.lang.System');

  var config = {
    env: env,
    runId: System.getenv('KARATE_RUN_ID') ||
      Java.type('java.util.UUID')
        .randomUUID().toString(),
    responseTimeLimit: 3000
  };

  if (env === 'local') {
    config.baseUrl = 'http://127.0.0.1:8002';
  } else if (env === 'qa') {
    config.baseUrl = System.getenv('QA_BASE_URL');

    if (!config.baseUrl) {
      throw new Error('QA_BASE_URL es obligatoria');
    }

    var URI = Java.type('java.net.URI');
    var destino = new URI(config.baseUrl);

    if (
      destino.getScheme() !== 'https' ||
      destino.getHost() == null ||
      destino.getUserInfo() != null ||
      destino.getQuery() != null ||
      destino.getFragment() != null ||
      (
        destino.getPath() !== '' &&
        destino.getPath() !== '/'
      )
    ) {
      throw new Error(
        'QA_BASE_URL debe ser una URL HTTPS base sin credenciales ni parametros'
      );
    }
  } else {
    throw new Error('Ambiente no autorizado: ' + env);
  }

  config.baseUrl = config.baseUrl.replace(/\/+$/, '');
  config.apiUrl = config.baseUrl + '/api/v1';

  karate.configure('connectTimeout', 5000);
  karate.configure('readTimeout', 10000);
  karate.configure('ssl', false);

  karate.configure('headers', {
    Accept: 'application/json'
  });

  var SensitiveLogModifier =
    Java.type('creo.SensitiveLogModifier');

  karate.configure(
    'logModifier',
    new SensitiveLogModifier()
  );

  karate.configure('printEnabled', false);

  karate.configure('report', {
    showLog: true,
    showAllSteps: true,
    showCallArg: false
  });

  karate.configure('logPrettyRequest', false);
  karate.configure('logPrettyResponse', false);

  return config;
}
