# QA-24 — Verificación funcional con 1 VU

Commit base: b0f9df34a57078b02ed28db05908d3c371384917
SHA256 JMX: 8188C368933B31FB7BED40D40120AA8097560DA02B02263B6765E315378FA20C
Configuración: solo postulación, 1 VU, ramp-up 1 s, duración 90 s.
Resultado funcional: PASA.
HTTP: 17 solicitudes, cero errores inesperados.
Creaciones: 3; verificaciones GET: 3.
Duplicada deliberada: 409 esperado.
GET posterior a duplicada: correcto.
Integridad SQL: VERIFICADA.
p95 lecturas: 6151 ms; login: 3497 ms; escrituras: 3269 ms.
Rendimiento: NO CUMPLE los objetivos iniciales.
No acredita capacidad de 25 VU; escalamiento detenido.
