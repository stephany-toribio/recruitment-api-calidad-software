#!/bin/sh
set -eu

case "${1:-}" in
    backup)
        umask 077
        test "$MYSQL_DATABASE" = "portal_empleos_calidad_qa"
        export MYSQL_PWD="$MYSQL_PASSWORD"
        exec mysqldump --user="$MYSQL_USER" \
            --single-transaction --no-tablespaces \
            --set-gtid-purged=OFF --hex-blob \
            --result-file=/tmp/qa13-respaldo.sql "$MYSQL_DATABASE"
        ;;
    ready)
        exec mysql --protocol=TCP -h127.0.0.1 -uroot qa13_restore \
            -e 'SELECT 1' >/dev/null 2>&1
        ;;
    restore)
        exec mysql -uroot qa13_restore < /tmp/qa13-respaldo.sql
        ;;
    counts)
        mysql -uroot --batch qa13_restore <<'SQL'
SELECT
    (SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE()) AS tablas,
    (SELECT COUNT(*) FROM migrations) AS migraciones,
    (SELECT COUNT(*) FROM roles) AS roles,
    (SELECT COUNT(*) FROM usuarios) AS usuarios,
    (SELECT COUNT(*) FROM candidatos) AS candidatos,
    (SELECT COUNT(*) FROM empresas) AS empresas;
SQL
        ;;
    *)
        echo "Uso: mantenimiento.sh backup|ready|restore|counts" >&2
        exit 2
        ;;
esac
