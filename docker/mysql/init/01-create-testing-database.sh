#!/bin/sh

set -eu

MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" \
mysql --protocol=socket -uroot <<-EOSQL

    CREATE DATABASE IF NOT EXISTS portalempleoshu04testing
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci;

    GRANT ALL PRIVILEGES
        ON portalempleoshu04testing.*
        TO '${MYSQL_USER}'@'%';

EOSQL

echo "Base MySQL de pruebas configurada correctamente."
