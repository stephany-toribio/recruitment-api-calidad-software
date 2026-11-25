package creo;

import com.intuit.karate.Results;
import com.intuit.karate.Runner;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

class BusinessTest {

    @Test
    void ejecutarNegocio() {

        String runId =
            System.getenv("KARATE_RUN_ID");

        assertTrue(
            runId != null && !runId.isBlank(),
            "Ejecutar con run-qa16.sh"
        );

        String modo = System.getProperty(
            "qa.mode",
            "full"
        );

        assertTrue(
            modo.equals("full") || modo.equals("demo"),
            "Modo no autorizado"
        );

        String filtro = System.getProperty(
            "qa.tags",
            "@QA-16"
        );

        boolean suiteCompleta =
            filtro.equals("@QA-16");

        assertTrue(
            !modo.equals("demo") || suiteCompleta,
            "El modo demo no admite filtros adicionales"
        );

        String tags = modo.equals("demo")
            ? "@demo"
            : "~@ignore";

        Results resultados = Runner
            .path("classpath:features/business")
            .tags("~@ignore", tags, filtro)
            .outputCucumberJson(true)
            .outputJunitXml(true)
            .parallel(1);

        if (suiteCompleta) {

            int esperados = modo.equals("demo")
                ? 4
                : 34;

            assertEquals(
                esperados,
                resultados.getScenariosTotal(),
                "Cantidad inesperada de escenarios QA-16"
            );

        } else {

            assertTrue(
                resultados.getScenariosTotal() > 0,
                "El filtro no selecciono escenarios"
            );
        }

        assertEquals(
            0,
            resultados.getFailCount(),
            resultados.getErrorMessages()
        );
    }
}
