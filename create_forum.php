<?php
/**
 * Crea foro, páginas y debate en el curso "Formulación de Proyectos".
 * Ejecutar: php /tmp/create_forum.php
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

$course = $DB->get_record('course', array('shortname' => 'FORM-PROJ-01'), '*', MUST_EXIST);
$courseid = $course->id;
echo "Curso ID: $courseid\n";

// Obtener secciones
$sections = $DB->get_records('course_sections', array('course' => $courseid), 'section ASC');
$sectionmap = array();
foreach ($sections as $s) {
    $sectionmap[$s->section] = $s->id;
}
echo "Secciones disponibles: " . implode(', ', array_keys($sectionmap)) . "\n";

// Actualizar nombres de secciones
$sectionnames = array(
    1 => 'Introducción y Marco Conceptual',
    2 => 'Identificación del Problema y Diagnóstico',
    3 => 'Formulación de Objetivos y Marco Lógico',
    4 => 'Metodología y Cronograma',
    5 => 'Evaluación y Presentación Final',
);
foreach ($sectionnames as $num => $name) {
    if (isset($sectionmap[$num])) {
        $DB->set_field('course_sections', 'name', $name, array('id' => $sectionmap[$num]));
        echo "Sección $num actualizada: $name\n";
    }
}

// -----------------------------------------------------------------------
// Función auxiliar para crear un módulo y agregarlo a la sección
// -----------------------------------------------------------------------
function add_module_to_section($courseid, $sectionid, $modname, $instanceid) {
    global $DB;
    $mod = $DB->get_record('modules', array('name' => $modname), '*', MUST_EXIST);
    $cm = new stdClass();
    $cm->course   = $courseid;
    $cm->module   = $mod->id;
    $cm->instance = $instanceid;
    $cm->section  = $sectionid;
    $cm->visible  = 1;
    $cm->added    = time();
    $cmid = $DB->insert_record('course_modules', $cm);

    // Agregar al sequence de la sección
    $section = $DB->get_record('course_sections', array('id' => $sectionid));
    $sequence = $section->sequence ? $section->sequence . ',' . $cmid : (string)$cmid;
    $DB->set_field('course_sections', 'sequence', $sequence, array('id' => $sectionid));

    rebuild_course_cache($courseid, true);
    echo "  Modulo '$modname' (cmid=$cmid) agregado a sección $sectionid\n";
    return $cmid;
}

// -----------------------------------------------------------------------
// Crear foro de debate en sección 1
// -----------------------------------------------------------------------
echo "\n--- Creando foro de debate ---\n";
$forum = new stdClass();
$forum->course       = $courseid;
$forum->type         = 'general';
$forum->name         = 'Foro: Discusión y Presentación de Proyectos';
$forum->intro        = '<p>Espacio para compartir avances, dudas y propuestas del proyecto final. El docente publica los lineamientos y los estudiantes responden con sus propuestas.</p>';
$forum->introformat  = 1;
$forum->maxattachments = 9;
$forum->forcesubscribe = 0;
$forum->timemodified = time();
$forumid = $DB->insert_record('forum', $forum);
echo "Foro creado id=$forumid\n";
add_module_to_section($courseid, $sectionmap[1], 'forum', $forumid);

// -----------------------------------------------------------------------
// Crear página: ¿Qué es un proyecto? (sección 1)
// -----------------------------------------------------------------------
echo "\n--- Creando página introductoria ---\n";
$page1 = new stdClass();
$page1->course      = $courseid;
$page1->name        = '¿Qué es un proyecto? Conceptos clave';
$page1->intro       = '<p>Lectura introductoria sobre formulación de proyectos.</p>';
$page1->introformat = 1;
$page1->content     = '<h2>Conceptos clave en Formulación de Proyectos</h2>
<p>Un <strong>proyecto</strong> es una iniciativa temporal que se lleva a cabo para crear un producto, servicio o resultado único. Todo proyecto tiene:</p>
<ul>
  <li><strong>Inicio y fin definidos</strong> en el tiempo.</li>
  <li><strong>Recursos limitados</strong> (humanos, financieros, materiales).</li>
  <li><strong>Objetivos específicos y medibles</strong>.</li>
  <li><strong>Interesados (stakeholders)</strong> con diferentes expectativas.</li>
</ul>
<h3>Ciclo de vida de un proyecto</h3>
<ol>
  <li>Identificación del problema / oportunidad</li>
  <li>Formulación y diseño</li>
  <li>Aprobación y financiamiento</li>
  <li>Ejecución y seguimiento</li>
  <li>Evaluación y cierre</li>
</ol>
<h3>Enfoques metodológicos</h3>
<table border="1" cellpadding="6">
  <thead><tr><th>Enfoque</th><th>Uso principal</th></tr></thead>
  <tbody>
    <tr><td>Marco Lógico (ML)</td><td>Proyectos de desarrollo social e institucional</td></tr>
    <tr><td>PMI / PMBOK</td><td>Proyectos empresariales y de infraestructura</td></tr>
    <tr><td>Metodologías ágiles</td><td>Proyectos de software e innovación</td></tr>
    <tr><td>PRINCE2</td><td>Proyectos gubernamentales en Europa</td></tr>
  </tbody>
</table>';
$page1->contentformat = 1;
$page1->timemodified  = time();
$page1id = $DB->insert_record('page', $page1);
echo "Página 1 creada id=$page1id\n";
add_module_to_section($courseid, $sectionmap[1], 'page', $page1id);

// -----------------------------------------------------------------------
// Crear página: Árbol de problemas (sección 2)
// -----------------------------------------------------------------------
echo "\n--- Creando página árbol de problemas ---\n";
$page2 = new stdClass();
$page2->course      = $courseid;
$page2->name        = 'Árbol de Problemas y Árbol de Objetivos';
$page2->intro       = '<p>Herramientas de diagnóstico para la identificación del problema central.</p>';
$page2->introformat = 1;
$page2->content     = '<h2>Árbol de Problemas</h2>
<p>El <strong>árbol de problemas</strong> es una técnica participativa que ayuda a identificar:</p>
<ul>
  <li><strong>Problema central</strong> (tronco del árbol)</li>
  <li><strong>Causas</strong> (raíces): factores que generan el problema</li>
  <li><strong>Efectos</strong> (copa): consecuencias del problema</li>
</ul>
<h3>Pasos para construirlo</h3>
<ol>
  <li>Identificar el problema central con la comunidad afectada.</li>
  <li>Listar las causas directas e indirectas.</li>
  <li>Listar los efectos directos e indirectos.</li>
  <li>Validar con actores clave.</li>
</ol>
<h2>Árbol de Objetivos</h2>
<p>Se construye invirtiendo las relaciones del árbol de problemas:</p>
<ul>
  <li>El problema central se convierte en el <strong>objetivo general</strong>.</li>
  <li>Las causas se convierten en <strong>medios</strong> (actividades y productos).</li>
  <li>Los efectos se convierten en <strong>fines</strong> (impactos esperados).</li>
</ul>
<blockquote><em>"Si el problema es: Baja calidad en la educación rural, el objetivo sería: Mejorar la calidad educativa en las zonas rurales."</em></blockquote>';
$page2->contentformat = 1;
$page2->timemodified  = time();
$page2id = $DB->insert_record('page', $page2);
echo "Página 2 creada id=$page2id\n";
add_module_to_section($courseid, $sectionmap[2], 'page', $page2id);

// -----------------------------------------------------------------------
// Crear página: Marco Lógico (sección 3)
// -----------------------------------------------------------------------
echo "\n--- Creando página Marco Lógico ---\n";
$page3 = new stdClass();
$page3->course      = $courseid;
$page3->name        = 'Matriz de Marco Lógico (MML)';
$page3->intro       = '<p>Guía para construir la Matriz de Marco Lógico de tu proyecto.</p>';
$page3->introformat = 1;
$page3->content     = '<h2>¿Qué es la Matriz de Marco Lógico?</h2>
<p>La MML es una herramienta de planificación que resume en una tabla de 4×4 los elementos esenciales de un proyecto:</p>
<table border="1" cellpadding="8">
  <thead>
    <tr><th>Nivel</th><th>Indicadores</th><th>Medios de verificación</th><th>Supuestos</th></tr>
  </thead>
  <tbody>
    <tr><td><strong>Fin (Impacto)</strong></td><td>¿Cómo se mide el impacto?</td><td>Estadísticas, encuestas</td><td>Factores externos</td></tr>
    <tr><td><strong>Propósito (Objetivo)</strong></td><td>¿Cómo se mide el logro?</td><td>Informes, registros</td><td>Riesgos a gestionar</td></tr>
    <tr><td><strong>Componentes (Productos)</strong></td><td>¿Qué se entrega?</td><td>Documentos, prototipos</td><td>Condiciones necesarias</td></tr>
    <tr><td><strong>Actividades</strong></td><td>¿Cuánto cuesta? ¿Cuánto tiempo?</td><td>Cronograma, presupuesto</td><td>Recursos disponibles</td></tr>
  </tbody>
</table>
<h3>Criterios SMART para indicadores</h3>
<ul>
  <li><strong>S</strong>pecific — Específico</li>
  <li><strong>M</strong>easurable — Medible</li>
  <li><strong>A</strong>chievable — Alcanzable</li>
  <li><strong>R</strong>elevant — Relevante</li>
  <li><strong>T</strong>ime-bound — Con plazo definido</li>
</ul>';
$page3->contentformat = 1;
$page3->timemodified  = time();
$page3id = $DB->insert_record('page', $page3);
echo "Página 3 creada id=$page3id\n";
add_module_to_section($courseid, $sectionmap[3], 'page', $page3id);

// -----------------------------------------------------------------------
// Crear URL: recurso externo (sección 4)
// -----------------------------------------------------------------------
echo "\n--- Creando recurso URL ---\n";
$url = new stdClass();
$url->course      = $courseid;
$url->name        = 'Guía DNP: Metodología General Ajustada (MGA)';
$url->intro       = '<p>Enlace al portal oficial de la MGA del Departamento Nacional de Planeación de Colombia.</p>';
$url->introformat = 1;
$url->externalurl = 'https://mgaweb.dnp.gov.co/';
$url->display     = 0;
$url->timemodified = time();
$urlid = $DB->insert_record('url', $url);
echo "URL creada id=$urlid\n";
add_module_to_section($courseid, $sectionmap[4], 'url', $urlid);

// -----------------------------------------------------------------------
// Publicar debate en el foro (como admin, asignando userid del profesor)
// -----------------------------------------------------------------------
echo "\n--- Publicando debate inicial como profesor ---\n";

$profesor = $DB->get_record('user', array('username' => 'profesor_demo'), '*', MUST_EXIST);

// Obtener course module id del foro
$cmforum = $DB->get_record('course_modules', array('course' => $courseid, 'instance' => $forumid), '*', MUST_EXIST);
$context = context_module::instance($cmforum->id);

// Crear discusión directamente en BD
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
<p>¡Éxitos a todos! 🚀</p>
<p>Atentamente,<br><strong>Carlos Martínez</strong><br>Docente — Formulación de Proyectos</p>';
$post->messageformat = 1;
$post->messagetrust  = 0;
$post->attachment    = '';
$post->totalscore    = 0;
$post->mailnow       = 0;
$postid = $DB->insert_record('forum_posts', $post);
$DB->set_field('forum_discussions', 'firstpost', $postid, array('id' => $discussionid));
echo "Debate creado: discussion_id=$discussionid, post_id=$postid\n";

// -----------------------------------------------------------------------
// Respuesta del estudiante
// -----------------------------------------------------------------------
echo "\n--- Publicando respuesta como estudiante ---\n";

$estudiante = $DB->get_record('user', array('username' => 'estudiante_demo'), '*', MUST_EXIST);

$reply = new stdClass();
$reply->discussion   = $discussionid;
$reply->parent       = $postid;
$reply->userid       = $estudiante->id;
$reply->created      = time() + 300;
$reply->modified     = time() + 300;
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
echo "Respuesta creada: post_id=$replyid\n";

// Actualizar timemodified de la discusión
$DB->set_field('forum_discussions', 'timemodified', time() + 300, array('id' => $discussionid));

rebuild_course_cache($courseid, true);

echo "\n=== TODO LISTO ===\n";
echo "Curso ID:       $courseid\n";
echo "Foro ID:        $forumid\n";
echo "Discusion ID:   $discussionid\n";
echo "Post profesor:  $postid\n";
echo "Post estudiante: $replyid\n";
