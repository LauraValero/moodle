# Accesos y configuración — Moodle Local (MIRA)

## URL de acceso

```
http://localhost:8080
```

---

## Credenciales de usuarios

| Rol | Nombre completo | Usuario | Contraseña | Email |
|-----|----------------|---------|-----------|-------|
| Administrador | — | `admin` | `Admin1234!` | admin@local.dev |
| Profesor | Carlos Martínez | `profesor_demo` | `Profesor1234!` | profesor@demo.local |
| Estudiante | Laura Valero | `estudiante_demo` | `Estudiante1234!` | estudiante@demo.local |

---

## Token de acceso a la API REST

```
3175d43ec32ad596dc0f3aa5fe35b567
```

### Cómo encontrar o crear el token en Moodle

1. Iniciar sesión como **admin** en http://localhost:8080
2. Ir a **Administración del sitio** → **Plugins** → **Webservices** → **Gestionar tokens**
   - Acceso directo: http://localhost:8080/admin/webservice/tokens.php
3. En esa página aparece el token generado para el servicio **MIRA Setup**.
4. Para crear uno nuevo: clic en **Agregar**, seleccionar usuario `admin` y servicio `MIRA Setup`, luego guardar.

---

## Cómo habilitar Webservices en Moodle

Si en algún momento los webservices se desactivan (por ejemplo, tras reinstalar Moodle), seguir estos pasos:

### Opción A — Desde la interfaz web (recomendada)

1. Iniciar sesión como **admin**.
2. Ir a **Administración del sitio** → **Avanzado** → **Funciones experimentales**
   - O directamente: http://localhost:8080/admin/settings.php?section=optionalsubsystems
3. Activar **Habilitar servicios web** → Guardar.
4. Ir a **Administración del sitio** → **Plugins** → **Webservices** → **Gestionar protocolos**
   - O directamente: http://localhost:8080/admin/settings.php?section=webserviceprotocols
5. Activar el protocolo **REST** → Guardar.
6. Ir a **Gestionar servicios externos**:
   - http://localhost:8080/admin/webservice/service.php
   - Verificar que el servicio **MIRA Setup** esté habilitado.
7. Ir a **Gestionar tokens** y crear o copiar el token:
   - http://localhost:8080/admin/webservice/tokens.php

### Opción B — Desde Docker CLI (más rápido)

```bash
# Habilitar webservices y protocolo REST
docker exec moodle_app bash -c "php /bitnami/moodle/admin/cli/cfg.php --name=enablewebservices --set=1"
docker exec moodle_app bash -c "php /bitnami/moodle/admin/cli/cfg.php --name=webserviceprotocols --set=rest"
```

Luego ejecutar el script de setup para regenerar el token:

```bash
docker cp moodle/setup_ws.php moodle_app:/tmp/setup_ws.php
docker exec moodle_app bash -c "php /tmp/setup_ws.php"
```

---

## Funciones habilitadas en el servicio MIRA Setup

| Función | Descripción |
|---------|-------------|
| `core_webservice_get_site_info` | Información del sitio y verificación de conexión |
| `core_user_create_users` | Crear usuarios |
| `core_user_get_users_by_field` | Buscar usuarios |
| `core_course_create_courses` | Crear cursos |
| `core_course_get_courses` | Listar cursos |
| `core_course_get_contents` | Obtener contenido de un curso |
| `enrol_manual_enrol_users` | Matricular usuarios con rol |
| `core_enrol_get_enrolled_users` | Listar usuarios matriculados |
| `mod_forum_get_forums_by_courses` | Listar foros de un curso |
| `mod_forum_get_forum_discussions` | Listar debates de un foro |
| `mod_forum_add_discussion` | Publicar debate en foro |
| `mod_forum_add_discussion_post` | Responder en un debate |

---

## Levantar y detener Moodle

```bash
# Levantar (desde la carpeta moodle)
cd moodle
docker compose up -d

# Ver estado
docker compose ps

# Ver logs en tiempo real
docker logs -f moodle_app

# Detener (conserva datos)
docker compose down

# Detener y borrar todos los datos
docker compose down -v
```

> La primera vez tarda aproximadamente 5 minutos en inicializar la base de datos.

---

## Uso del token en la API REST

```bash
# Ejemplo: verificar conexión
curl "http://localhost:8080/webservice/rest/server.php" \
  --data "wstoken=3175d43ec32ad596dc0f3aa5fe35b567&wsfunction=core_webservice_get_site_info&moodlewsrestformat=json"
```

```python
# Ejemplo en Python
import requests

TOKEN = "3175d43ec32ad596dc0f3aa5fe35b567"
MOODLE_URL = "http://localhost:8080"

r = requests.post(f"{MOODLE_URL}/webservice/rest/server.php", data={
    "wstoken": TOKEN,
    "wsfunction": "core_webservice_get_site_info",
    "moodlewsrestformat": "json",
})
print(r.json())
```

---

*Generado: 2026-03-03 — Proyecto MIRA / Trabajo Integrador Univalle*
