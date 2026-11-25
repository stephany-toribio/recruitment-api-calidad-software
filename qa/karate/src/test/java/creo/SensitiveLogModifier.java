package creo;

import com.intuit.karate.http.HttpLogModifier;
import java.util.Locale;
import java.util.Set;

public final class SensitiveLogModifier implements HttpLogModifier {

    private static final String MASK = "[REDACTED]";

    private static final Set<String> SENSITIVE_HEADERS = Set.of(
        "authorization",
        "proxy-authorization",
        "cookie",
        "set-cookie",
        "x-api-key",
        "x-auth-token",
        "x-csrf-token",
        "x-xsrf-token"
    );

    @Override
    public boolean enableForUri(String uri) {
        return true;
    }

    @Override
    public String uri(String uri) {
        if (uri == null) {
            return null;
        }

        int query = uri.indexOf('?');

        return query < 0
            ? uri
            : uri.substring(0, query) + "?" + MASK;
    }

    @Override
    public String header(String name, String value) {
        if (
            name != null &&
            SENSITIVE_HEADERS.contains(
                name.toLowerCase(Locale.ROOT)
            )
        ) {
            return MASK;
        }

        return value;
    }

    @Override
    public String request(String uri, String body) {
        return body == null ? null : MASK;
    }

    @Override
    public String response(String uri, String body) {
        return body == null ? null : MASK;
    }
}
