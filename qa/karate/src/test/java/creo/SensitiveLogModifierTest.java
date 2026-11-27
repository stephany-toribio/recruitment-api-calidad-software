package creo;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class SensitiveLogModifierTest {

    private final SensitiveLogModifier modifier =
        new SensitiveLogModifier();

    @Test
    void protegeAuthorization() {
        assertEquals(
            "[REDACTED]",
            modifier.header(
                "Authorization",
                "Bearer TOKEN-FICTICIO"
            )
        );
    }

    @Test
    void protegeCookiesSinDistinguirMayusculas() {
        assertEquals(
            "[REDACTED]",
            modifier.header(
                "SET-COOKIE",
                "session=SECRETO-FICTICIO"
            )
        );
    }

    @Test
    void conservaCabecerasNoSensibles() {
        assertEquals(
            "application/json",
            modifier.header(
                "Content-Type",
                "application/json"
            )
        );
    }

    @Test
    void protegePasswordDelRequest() {
        String resultado = modifier.request(
            "/api/v1/auth/login",
            "{\"password\":\"CLAVE-FICTICIA\"}"
        );

        assertFalse(
            resultado.contains("CLAVE-FICTICIA")
        );
    }

    @Test
    void protegeTokenDelResponse() {
        String resultado = modifier.response(
            "/api/v1/auth/login",
            "{\"data\":{\"token\":\"TOKEN-FICTICIO\"}}"
        );

        assertFalse(
            resultado.contains("TOKEN-FICTICIO")
        );
    }

    @Test
    void protegeParametrosDeConsulta() {
        assertEquals(
            "https://qa.example.test/api/v1? [REDACTED]"
                .replace("? ", "?"),
            modifier.uri(
                "https://qa.example.test/api/v1?token=secreto"
            )
        );
    }
}
