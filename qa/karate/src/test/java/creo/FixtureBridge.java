package creo;

import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.File;
import java.nio.charset.StandardCharsets;
import java.nio.file.Path;
import java.util.Map;
import java.util.LinkedHashMap;
import java.util.concurrent.TimeUnit;

public final class FixtureBridge {

    private static final ObjectMapper JSON =
        new ObjectMapper();

    private FixtureBridge() {
    }

    public static Map<?, ?> datos(
        String runId,
        String tipo
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "datos",
            "run_id", runId,
            "tipo", tipo
        ));
    }

    public static Map<?, ?> consultar(
        String runId,
        String correo,
        String password
    ) throws Exception {

        Map<String, Object> entrada =
            new LinkedHashMap<>();

        entrada.put("accion", "consultar");
        entrada.put("run_id", runId);
        entrada.put("correo", correo);

        if (password != null) {
            entrada.put("password", password);
        }

        return ejecutar(entrada);
    }

    public static Map<?, ?> cantidades(
        String runId
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "cantidades",
            "run_id", runId
        ));
    }

    public static Map<?, ?> crearAdmin(
        String runId,
        String password
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "crear_admin",
            "run_id", runId,
            "password", password
        ));
    }

    public static Map<?, ?> suspender(
        String runId,
        String correo
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "suspender",
            "run_id", runId,
            "correo", correo
        ));
    }

    public static Map<?, ?> expirarBloqueo(
        String runId,
        String correo
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "expirar_bloqueo",
            "run_id", runId,
            "correo", correo
        ));
    }

    private static Map<?, ?> ejecutar(
        Map<String, ?> entrada
    ) throws Exception {

        String env = System.getProperty(
            "karate.env",
            "local"
        );

        if (!"local".equals(env)) {
            throw new IllegalStateException(
                "Fixtures disponibles solo en ambiente local"
            );
        }

        File root = Path.of(
            System.getProperty("user.dir")
        )
            .resolve("../..")
            .normalize()
            .toFile();

        Process proceso = new ProcessBuilder(
            "docker", "compose", "exec", "-T", "app",
            "php", "qa/karate/scripts/fixture-qa15.php"
        )
            .directory(root)
            .redirectError(ProcessBuilder.Redirect.DISCARD)
            .start();

        try {
            try (var entradaProceso =
                     proceso.getOutputStream()) {

                JSON.writeValue(
                    entradaProceso,
                    entrada
                );
            }

            if (!proceso.waitFor(
                30,
                TimeUnit.SECONDS
            )) {
                throw new IllegalStateException(
                    "Tiempo excedido en fixture QA"
                );
            }

            if (proceso.exitValue() != 0) {
                throw new IllegalStateException(
                    "Error al ejecutar fixture QA"
                );
            }

            String salida = new String(
                proceso.getInputStream().readAllBytes(),
                StandardCharsets.UTF_8
            );

            return JSON.readValue(
                salida,
                Map.class
            );

        } finally {
            if (proceso.isAlive()) {
                proceso.destroyForcibly();
            }
        }
    }
}
