# 📬 Guía de Postman para el Equipo (100% Gratis)

Esta carpeta contiene la configuración necesaria para que cualquier desarrollador pueda probar la API en Postman sin pagar suscripciones ni depender de la nube.

---

## 🚀 Paso a Paso para Nuevos Desarrolladores

### 1. Levantar el proyecto con Docker
Asegúrate de tener los contenedores encendidos:
```bash
docker compose up -d
```
Verifica que la API responda en: `http://localhost:8000`

---

### 2. Importar el Entorno (Environment) en Postman
1. Abre tu Postman (cualquier cuenta gratuita o sin iniciar sesión).
2. Haz clic en el botón **Import** (arriba a la izquierda).
3. Selecciona el archivo:
   `postman/environment.local.json`
4. En la esquina superior derecha de Postman, abre el menú desplegable de entornos y selecciona:
   **`Portal Empleos - Local (Docker)`**

---

### 3. Importar la Colección de Endpoints (vía Scramble)
1. En Postman, haz clic en **Import**.
2. En la barra de búsqueda/URL escribe:
   ```text
   http://localhost:8000/docs/api.json
   ```
3. Haz clic en **Import**.
4. ¡Listo! Ya tienes todos los endpoints del backend con sus parámetros requeridos.

---

### 🔄 ¿Cómo actualizar la colección cuando alguien cree nuevas rutas?
Cuando hagas `git pull` y haya nuevos endpoints:
1. Asegúrate de tener Docker corriendo.
2. Vuelve a hacer **Import** de la URL: `http://localhost:8000/docs/api.json`.
3. Selecciona **Update** (o Replace). Postman actualizará los endpoints automáticamente.
