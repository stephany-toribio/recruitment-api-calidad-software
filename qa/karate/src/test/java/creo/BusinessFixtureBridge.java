package creo;

import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.File;
import java.nio.charset.StandardCharsets;
import java.nio.file.Path;
import java.util.Map;
import java.util.LinkedHashMap;
import java.util.concurrent.TimeUnit;

public final class BusinessFixtureBridge {

    private static final ObjectMapper JSON =
        new ObjectMapper();

    private BusinessFixtureBridge() {
    }

    public static Map<?, ?> datosVacante(
        String runId
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "datos_vacante",
            "run_id", runId
        ));
    }

    public static Map<?, ?> cantidades(
        String runId
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "cantidades",
            "run_id", runId
        ));
    }

    public static Map<?, ?> consultarVacante(
        String runId,
        int vacanteId
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "consultar_vacante",
            "run_id", runId,
            "vacante_id", vacanteId
        ));
    }

    public static Map<?, ?> datosPago(
        String runId
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "datos_pago",
            "run_id", runId
        ));
    }

    public static Map<?, ?> consultarPostulacion(
        String runId,
        int postulacionId
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "consultar_postulacion",
            "run_id", runId,
            "postulacion_id", postulacionId
        ));
    }

    public static Map<?, ?> catalogosNegocio(
        String runId
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "catalogos_negocio",
            "run_id", runId
        ));
    }


    public static Map<?, ?> consultarCandidato(
        String runId,
        int candidatoId
    ) throws Exception {
        return ejecutar(Map.of(
            "accion", "consultar_candidato",
            "run_id", runId,
            "candidato_id", candidatoId
        ));
    }

    public static Map<?, ?> postulacionInexistente(
        String runId
    ) throws Exception {
        return ejecutar(Map.of(
            "accion", "postulacion_inexistente",
            "run_id", runId
        ));
    }


    public static Map<?, ?> prepararIndisponibilidad(
        String runId,
        int vacanteId,
        String condicion,
        java.util.List<Integer> candidatos
    ) throws Exception {

        return ejecutar(Map.of(
            "accion", "preparar_indisponibilidad",
            "run_id", runId,
            "vacante_id", vacanteId,
            "condicion", condicion,
            "candidatos", candidatos
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
            "php", "qa/karate/scripts/fixture-qa16.php"
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
