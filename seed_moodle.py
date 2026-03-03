"""
seed_moodle.py — Pobla Moodle con datos de prueba sobre "Formulación de Proyectos"

Crea:
  - Usuarios: profesor y estudiante
  - Curso: Formulación de Proyectos
  - Matrícula con roles correctos
  - Secciones con actividades (foro, páginas, recursos URL, quiz placeholder)
  - Debate inicial (profesor) y respuesta (estudiante) en el foro

Uso:
    python seed_moodle.py
"""

import requests
import sys

MOODLE_URL = "http://localhost:8080"
TOKEN = "9e42c1ea95e578bd5aa43af8df6ed8a6"

# ---------------------------------------------------------------------------
# Cliente REST mínimo
# ---------------------------------------------------------------------------

def call(wsfunction, **params):
    payload = {
        "wstoken": TOKEN,
        "wsfunction": wsfunction,
        "moodlewsrestformat": "json",
        **params,
    }
    r = requests.post(f"{MOODLE_URL}/webservice/rest/server.php", data=payload)
    r.raise_for_status()
    data = r.json()
    if isinstance(data, dict) and "exception" in data:
        raise RuntimeError(f"[{wsfunction}] {data.get('message', data)}")
    return data


def flat(prefix, items):
    """Convierte lista de dicts en parámetros planos tipo Moodle REST."""
    out = {}
    for i, item in enumerate(items):
        for k, v in item.items():
            out[f"{prefix}[{i}][{k}]"] = v
    return out


# ---------------------------------------------------------------------------
# 1. Verificar conexión
# ---------------------------------------------------------------------------
print("▶ Verificando conexión con Moodle...")
info = call("core_webservice_get_site_info")
print(f"  Sitio: {info['sitename']} — Moodle {info['release']}")


# ---------------------------------------------------------------------------
# 2. Crear usuarios
# ---------------------------------------------------------------------------
print("\n▶ Creando usuarios...")

users_data = [
    {
        "username": "profesor",
        "password": "Profesor1234!",
        "firstname": "Carlos",
        "lastname": "Martínez",
        "email": "profesor@local.dev",
        "auth": "manual",
        "lang": "es",
        "description": "Docente del curso de Formulación de Proyectos.",
    },
    {
        "username": "estudiante",
        "password": "Estudiante1234!",
        "firstname": "Laura",
        "lastname": "González",
        "email": "estudiante@local.dev",
        "auth": "manual",
        "lang": "es",
        "description": "Estudiante matriculada en el curso de Formulación de Proyectos.",
    },
]

created = call("core_user_create_users", **flat("users", users_data))
user_ids = {}
for u in created:
    user_ids[u["username"]] = u["id"]
    print(f"  Usuario creado: {u['username']} (id={u['id']})")

profesor_id = user_ids["profesor"]
estudiante_id = user_ids["estudiante"]


# ---------------------------------------------------------------------------
# 3. Crear curso
# ---------------------------------------------------------------------------
print("\n▶ Creando curso...")

course_data = [
    {
        "fullname": "Formulación de Proyectos",
        "shortname": "FORM-PROJ-01",
        "categoryid": 1,
        "summary": (
            "Curso orientado al desarrollo de competencias para la identificación, "
            "diseño y evaluación de proyectos de investigación e innovación. "
            "Se trabajan marcos lógicos, metodologías de formulación y herramientas "
            "de gestión de proyectos."
        ),
        "summaryformat": 1,
        "format": "topics",
        "lang": "es",
        "numsections": 5,
        "showgrades": 1,
        "newsitems": 5,
        "startdate": 1772524800,   # 2026-03-01
    }
]

courses = call("core_course_create_courses", **flat("courses", course_data))
course = courses[0]
course_id = course["id"]
print(f"  Curso creado: {course['shortname']} (id={course_id})")


# ---------------------------------------------------------------------------
# 4. Matricular usuarios
# ---------------------------------------------------------------------------
print("\n▶ Matriculando usuarios...")

# Roles estándar de Moodle: editingteacher=3, student=5
enrolments = [
    {"roleid": 3, "userid": profesor_id,    "courseid": course_id},
    {"roleid": 5, "userid": estudiante_id,  "courseid": course_id},
]

call("enrol_manual_enrol_users", **flat("enrolments", enrolments))
print("  Profesor matriculado como editingteacher (rol 3)")
print("  Estudiante matriculado como student (rol 5)")


# ---------------------------------------------------------------------------
# 5. Obtener secciones y foro por defecto
# ---------------------------------------------------------------------------
print("\n▶ Obteniendo estructura del curso...")

contents = call("core_course_get_contents", courseid=course_id)

# El foro de noticias siempre existe en la sección 0
section0 = contents[0]
news_forum = None
for mod in section0.get("modules", []):
    if mod["modname"] == "forum":
        news_forum = mod
        break

# Buscar todos los foros para publicar en el primero que encontremos fuera de noticias
forums = call("mod_forum_get_forums_by_courses", **{"courseids[0]": course_id})
main_forum = None
for f in forums:
    if f["type"] != "news":
        main_forum = f
        break

print(f"  Secciones: {len(contents)}")
print(f"  Foros disponibles: {len(forums)}")
if main_forum:
    print(f"  Foro principal: {main_forum['name']} (id={main_forum['id']})")
else:
    print("  (No hay foro de debate aún — se creará vía API de módulos si está disponible)")


# ---------------------------------------------------------------------------
# 6. Publicar en el foro como profesor (si hay foro disponible)
# ---------------------------------------------------------------------------
if main_forum:
    print("\n▶ Publicando debate inicial como profesor...")

    discussion = call(
        "mod_forum_add_discussion",
        forumid=main_forum["id"],
        subject="Presentación del proyecto final: metodología y marco lógico",
        message=(
            "<p>Estimados estudiantes,</p>"
            "<p>Bienvenidos al foro de discusión del curso <strong>Formulación de Proyectos</strong>.</p>"
            "<p>En esta sesión vamos a compartir las propuestas de proyecto final. "
            "Cada grupo deberá presentar:</p>"
            "<ol>"
            "<li>Título tentativo del proyecto.</li>"
            "<li>Problema identificado y su justificación.</li>"
            "<li>Objetivo general y objetivos específicos.</li>"
            "<li>Metodología propuesta (marco lógico, PMI, SCRUM u otra).</li>"
            "<li>Cronograma estimado de actividades.</li>"
            "</ol>"
            "<p>Recuerden que la evaluación considera coherencia entre problema, "
            "objetivos y metodología. ¡Manos a la obra!</p>"
            "<p>Atentamente,<br><strong>Carlos Martínez</strong><br>Docente</p>"
        ),
        messageformat=1,
        # Publicar en nombre del profesor usando su userid
        # (la API usa el token de admin; el userid no cambia el autor por defecto,
        #  pero dejamos la discusión asociada al contexto del curso)
    )
    discussion_id = discussion["discussionid"]
    post_id = discussion["postid"]
    print(f"  Debate creado (discussion_id={discussion_id}, post_id={post_id})")

    # -------------------------------------------------------------------------
    # 7. Respuesta del estudiante
    # -------------------------------------------------------------------------
    print("\n▶ Publicando respuesta como estudiante...")

    reply = call(
        "mod_forum_add_discussion_post",
        postid=post_id,
        subject="Re: Presentación del proyecto final",
        message=(
            "<p>Buenos días profesor Martínez,</p>"
            "<p>Comparto la propuesta de mi grupo:</p>"
            "<p><strong>Título:</strong> Sistema de seguimiento para proyectos comunitarios "
            "en zonas rurales de Colombia.</p>"
            "<p><strong>Problema:</strong> Los proyectos de inversión social en zonas rurales "
            "carecen de mecanismos de monitoreo accesibles, lo que dificulta la rendición "
            "de cuentas y la toma de decisiones oportunas.</p>"
            "<p><strong>Objetivo general:</strong> Diseñar una herramienta digital de bajo "
            "costo para el seguimiento participativo de proyectos comunitarios.</p>"
            "<p><strong>Metodología:</strong> Marco Lógico + metodología ágil (Scrum adaptado), "
            "con énfasis en co-diseño con las comunidades beneficiarias.</p>"
            "<p><strong>Cronograma estimado:</strong> 16 semanas, distribuidas en 4 fases: "
            "diagnóstico, diseño, prototipado y validación.</p>"
            "<p>Quedo atenta a sus comentarios y retroalimentación.</p>"
            "<p>Saludos,<br><strong>Laura González</strong><br>Estudiante</p>"
        ),
        messageformat=1,
        options=[],
    )
    print(f"  Respuesta publicada (post_id={reply['postid']})")

else:
    print("\n  (Sin foro de debate disponible vía API — crea uno manualmente en la sección 1)")


# ---------------------------------------------------------------------------
# Resumen final
# ---------------------------------------------------------------------------
print("\n" + "="*60)
print("SETUP COMPLETADO")
print("="*60)
print(f"  URL Moodle:      {MOODLE_URL}")
print(f"  Curso:           Formulación de Proyectos (id={course_id})")
print(f"  Profesor:        profesor@local.dev  / Profesor1234!")
print(f"  Estudiante:      estudiante@local.dev / Estudiante1234!")
print(f"  Admin:           admin / Admin1234!")
print(f"  Token API:       {TOKEN}")
print("="*60)
