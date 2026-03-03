<?php
/**
 * Crea debate inicial (profesor) y respuesta (estudiante) en el foro del curso.
 * Ejecutar: php /tmp/create_posts.php
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

$course   = $DB->get_record('course', array('shortname' => 'FORM-PROJ-01'), '*', MUST_EXIST);
$courseid = $course->id;

$profesor   = $DB->get_record('user', array('username' => 'profesor_demo'),   '*', MUST_EXIST);
$estudiante = $DB->get_record('user', array('username' => 'estudiante_demo'), '*', MUST_EXIST);

// Obtener el foro general del curso (tipo != news)
$forums = $DB->get_records('forum', array('course' => $courseid));
$forumid = null;
foreach ($forums as $f) {
    if ($f->type !== 'news') {
        $forumid = $f->id;
        break;
    }
}
if (!$forumid) {
    echo "ERROR: No se encontro foro de debate\n";
    exit(1);
}
echo "Foro ID: $forumid\n";

// Verificar que no exista ya la discusión
$existing = $DB->get_records('forum_discussions', array('forum' => $forumid));
if (!empty($existing)) {
    echo "Ya existen " . count($existing) . " discusiones en el foro. Nada que hacer.\n";

    foreach ($existing as $d) {
        $posts = $DB->get_records('forum_posts', array('discussion' => $d->id));
        echo "  Discusion [{$d->id}]: {$d->name} — " . count($posts) . " posts\n";
        foreach ($posts as $p) {
            $user = $DB->get_record('user', array('id' => $p->userid), 'username');
            echo "    Post [{$p->id}] by {$user->username}: " . substr(strip_tags($p->message), 0, 60) . "...\n";
        }
    }
    exit(0);
}

// Crear discusión
$discussion = new stdClass();
$discussion->course       = $courseid;
$discussion->forum        = $forumid;
$discussion->name         = 'Presentación del proyecto final: metodología y marco lógico';
$discussion->firstpost    = 0;
$discussion->userid       = $profesor->id;
$discussion->groupid      = -1;
$discussion->assessed     = 0;
$discussion->timemodified = time();
$discussion->usermodified = $profesor->id;
$discussion->timestart    = 0;
$discussion->timeend      = 0;
$discussionid = $DB->insert_record('forum_discussions', $discussion);
echo "Discusion creada id=$discussionid\n";

// Post del profesor
$post = new stdClass();
$post->discussion   = $discussionid;
$post->parent       = 0;
$post->userid       = $profesor->id;
$post->created      = time();
$post->modified     = time();
$post->mailed       = 1;
$post->subject      = 'Presentación del proyecto final: metodología y marco lógico';
$post->message      = '<p>Estimados estudiantes,</p>
<p>Bienvenidos al foro de discusión del curso <strong>Formulación de Proyectos</strong>.</p>
<p>En esta sesión vamos a compartir las propuestas de proyecto final. Cada grupo deberá presentar:</p>
<ol>
  <li>Título tentativo del proyecto.</li>
  <li>Problema identificado y su justificación (árbol de problemas).</li>
  <li>Objetivo general y objetivos específicos (árbol de objetivos).</li>
  <li>Metodología propuesta: Marco Lógico, PMI, SCRUM u otra debidamente justificada.</li>
  <li>Cronograma estimado de actividades (mínimo 4 fases).</li>
  <li>Fuentes de financiamiento posibles o entidades aliadas.</li>
</ol>
<p>Recuerden que la evaluación considera coherencia entre problema, objetivos y metodología, claridad en los indicadores y viabilidad de la propuesta.</p>
<p>Fecha límite de publicación: <strong>viernes de esta semana</strong>.</p>
<p>Atentamente,<br><strong>Carlos Martínez</strong><br>Docente — Formulación de Proyectos</p>';
$post->messageformat = 1;
$post->messagetrust  = 0;
$post->attachment    = '';
$post->totalscore    = 0;
$post->mailnow       = 0;
$postid = $DB->insert_record('forum_posts', $post);
$DB->set_field('forum_discussions', 'firstpost', $postid, array('id' => $discussionid));
echo "Post del profesor creado id=$postid\n";

// Respuesta del estudiante
$reply = new stdClass();
$reply->discussion   = $discussionid;
$reply->parent       = $postid;
$reply->userid       = $estudiante->id;
$reply->created      = time() + 3600;
$reply->modified     = time() + 3600;
$reply->mailed       = 1;
$reply->subject      = 'Re: Presentación del proyecto final — Propuesta Grupo 1';
$reply->message      = '<p>Buenos días profesor Martínez,</p>
<p>Comparto la propuesta de mi grupo para su revisión:</p>
<hr>
<h3>Proyecto: Sistema participativo de seguimiento para proyectos comunitarios en zonas rurales de Colombia</h3>
<p><strong>1. Problema identificado:</strong><br>
Los proyectos de inversión social en zonas rurales carecen de mecanismos de monitoreo accesibles y comprensibles para las comunidades beneficiarias, lo que dificulta la rendición de cuentas, la participación ciudadana y la toma de decisiones oportunas por parte de los gestores.</p>
<p><strong>Causas principales (árbol de problemas):</strong></p>
<ul>
  <li>Baja alfabetización digital en comunidades rurales.</li>
  <li>Escasa infraestructura tecnológica (conectividad limitada).</li>
  <li>Falta de herramientas adaptadas al contexto cultural local.</li>
  <li>Ausencia de procesos de co-diseño con las comunidades.</li>
</ul>
<p><strong>2. Objetivo general:</strong><br>
Diseñar e implementar una herramienta digital de bajo costo para el seguimiento participativo de proyectos comunitarios en municipios rurales de Colombia, que mejore la rendición de cuentas y la participación ciudadana.</p>
<p><strong>Objetivos específicos:</strong></p>
<ol>
  <li>Diagnosticar las necesidades de información de las comunidades beneficiarias en tres municipios piloto.</li>
  <li>Co-diseñar la interfaz de la herramienta con representantes comunitarios.</li>
  <li>Desarrollar un prototipo funcional accesible desde dispositivos móviles de bajo gama.</li>
  <li>Validar la herramienta con al menos 30 usuarios en contexto real.</li>
</ol>
<p><strong>3. Metodología:</strong><br>
Combinaremos el <strong>Marco Lógico</strong> para la planificación general con <strong>Scrum adaptado</strong> para el desarrollo del software, incorporando técnicas de diseño participativo (co-creación) en cada sprint.</p>
<p><strong>4. Cronograma estimado (16 semanas):</strong></p>
<ul>
  <li>Semanas 1-4: Diagnóstico participativo y levantamiento de requerimientos.</li>
  <li>Semanas 5-8: Diseño de interfaz y arquitectura del sistema.</li>
  <li>Semanas 9-12: Desarrollo del prototipo y pruebas internas.</li>
  <li>Semanas 13-16: Validación con comunidades y documentación final.</li>
</ul>
<p>Quedo atenta a sus comentarios y retroalimentación.</p>
<p>Saludos,<br><strong>Laura González</strong><br>Estudiante — Formulación de Proyectos</p>';
$reply->messageformat = 1;
$reply->messagetrust  = 0;
$reply->attachment    = '';
$reply->totalscore    = 0;
$reply->mailnow       = 0;
$replyid = $DB->insert_record('forum_posts', $reply);
$DB->set_field('forum_discussions', 'timemodified', time() + 3600, array('id' => $discussionid));
echo "Respuesta del estudiante creada id=$replyid\n";

rebuild_course_cache($courseid, true);
echo "\n=== POSTS CREADOS ===\n";
echo "Discussion:       $discussionid\n";
echo "Post profesor:    $postid\n";
echo "Post estudiante:  $replyid\n";
echo "Ver en: http://localhost:8080/mod/forum/discuss.php?d=$discussionid\n";
