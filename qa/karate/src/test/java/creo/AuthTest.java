package creo;

import com.intuit.karate.Results;
import com.intuit.karate.Runner;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

class AuthTest {

    @Test
    void ejecutarAutenticacion() {

        String runId =
            System.getenv("KARATE_RUN_ID");

        assertTrue(
            runId != null && !runId.isBlank(),
            "Ejecutar con el script de fixtures QA-15"
        );

        String modo = System.getProperty(
            "qa.mode",
            "full"
        );

        assertTrue(
            modo.equals("full") || modo.equals("demo"),
            "Modo no autorizado"
        );

        String tags = modo.equals("demo")
            ? "@demo"
            : "~@ignore";

        Results resultados = Runner
            .path("classpath:features/auth")
            .tags("~@ignore", tags)
            .outputCucumberJson(true)
            .outputJunitXml(true)
            .parallel(1);

        int escenariosEsperados =
            modo.equals("demo") ? 8 : 39;

        assertEquals(
            escenariosEsperados,
            resultados.getScenariosTotal(),
            "Cantidad inesperada de escenarios QA-15"
        );

        assertEquals(
            0,
            resultados.getFailCount(),
            resultados.getErrorMessages()
        );
    }
}
