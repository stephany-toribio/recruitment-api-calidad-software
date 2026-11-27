package creo;

import com.intuit.karate.Results;
import com.intuit.karate.Runner;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

class SmokeTest {

    @Test
    void ejecutarSmoke() {
        Results resultados = Runner
            .path("classpath:features/smoke")
            .tags(
                "~@ignore",
                System.getenv("KARATE_FIXTURE_EMAIL") == null
                    ? "~@fixture-auth"
                    : "@smoke"
            )
            .outputCucumberJson(true)
            .outputJunitXml(true)
            .parallel(1);

        assertTrue(
            resultados.getScenariosTotal() > 0,
            "No se ejecutaron escenarios Karate"
        );

        assertEquals(
            0,
            resultados.getFailCount(),
            resultados.getErrorMessages()
        );
    }
}
