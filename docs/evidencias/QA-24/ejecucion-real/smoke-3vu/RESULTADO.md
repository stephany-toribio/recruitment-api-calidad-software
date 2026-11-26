# QA-24 — Diagnóstico real con 3 VU

Resultado: NO APROBADO por latencia.
Carga: 1 consulta, 1 login y 1 postulación.
Ramp-up: 5 s. Duración configurada: 45 s.
HTTP: 12 solicitudes; 2 respuestas 401 esperadas.
Errores inesperados: 0.
p95 lecturas: 12408 ms.
p95 login: 12550 ms.
Escritura: 8429 ms, una muestra.
Integridad SQL de la creación: verificada.
GET posterior y duplicada deliberada: no alcanzados.
No acredita capacidad sostenida de 25 VU.

Se detiene el escalamiento por superar los límites de latencia.
La causa del rendimiento sigue pendiente de identificar.
Consultar manifest.json para ambiente, commit y hashes.
