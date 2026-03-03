<?php
/**
 * Seed completo para Moodle 4.5 — Formulación de Proyectos
 * Idempotente: verifica antes de crear.
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

// -----------------------------------------------------------------------
// Helper: agregar módulo a sección
// -----------------------------------------------------------------------
function add_cm($courseid, $sectionid, $modname, $instanceid) {
    global $DB;
    $mod = $DB->get_record('modules', array('name' => $modname), '*', MUST_EXIST);
    $existing = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $mod->id, 'instance' => $instanceid));
    if ($existing) { return $existing->id; }
    $cm = new stdClass();
    $cm->course   = $courseid; $cm->module = $mod->id;
    $cm->instance = $instanceid; $cm->section = $sectionid;
    $cm->visible  = 1; $cm->added = time();
    $cmid = $DB->insert_record('course_modules', $cm);
    $sec = $DB->get_record('course_sections', array('id' => $sectionid));
    $DB->set_field('course_sections', 'sequence', $sec->sequence ? $sec->sequence.','.$cmid : (string)$cmid, array('id' => $sectionid));
    return $cmid;
}

// -----------------------------------------------------------------------
// 1. Usuarios
// -----------------------------------------------------------------------
echo "--- Usuarios ---\n";
$profesor = $DB->get_record('user', array('username' => 'profesor'));
if (!$profesor) {
    $u = new stdClass();
    $u->auth = 'manual'; $u->confirmed = 1; $u->mnethostid = 1;
    $u->username = 'profesor'; $u->password = hash_internal_user_password('Profesor1234!');
    $u->firstname = 'Carlos'; $u->lastname = 'Martínez';
    $u->email = 'profesor@local.dev'; $u->lang = 'es';
    $u->timecreated = time(); $u->timemodified = time();
    $u->id = $DB->insert_record('user', $u);
    $profesor = $u;
    echo "Profesor creado id={$u->id}\n";
} else { echo "Profesor existente id={$profesor->id}\n"; }

$estudiante = $DB->get_record('user', array('username' => 'estudiante'));
if (!$estudiante) {
    $u = new stdClass();
    $u->auth = 'manual'; $u->confirmed = 1; $u->mnethostid = 1;
    $u->username = 'estudiante'; $u->password = hash_internal_user_password('Estudiante1234!');
    $u->firstname = 'Laura'; $u->lastname = 'Valero';
    $u->email = 'estudiante@local.dev'; $u->lang = 'es';
    $u->timecreated = time(); $u->timemodified = time();
    $u->id = $DB->insert_record('user', $u);
    $estudiante = $u;
    echo "Estudiante creada id={$u->id}\n";
} else {
    // Asegurarse de que el nombre sea correcto
    $DB->set_field('user', 'firstname', 'Laura',  array('id' => $estudiante->id));
    $DB->set_field('user', 'lastname',  'Valero', array('id' => $estudiante->id));
    echo "Estudiante existente id={$estudiante->id} — nombre actualizado\n";
}

// -----------------------------------------------------------------------
// 2. Curso
// -----------------------------------------------------------------------
echo "\n--- Curso ---\n";
$course = $DB->get_record('course', array('shortname' => 'FORM-PROJ-01'));
if (!$course) {
    // crear via DB directamente no es seguro — usamos insert con valores mínimos
    // y dejamos que Moodle complete
    $data = new stdClass();
    $data->fullname   = 'Formulación de Proyectos';
    $data->shortname  = 'FORM-PROJ-01';
    $data->category   = 1;
    $data->summary    = 'Curso orientado al desarrollo de competencias para la identificación, diseño y evaluación de proyectos de investigación e innovación.';
    $data->summaryformat = 1;
    $data->format     = 'topics';
    $data->numsections = 5;
    $data->lang       = 'es';
    $data->startdate  = mktime(0,0,0,3,1,2026);
    $data->timecreated = time();
    $data->timemodified = time();
    $data->visible    = 1;
    $data->showgrades = 1;
    $data->newsitems  = 5;
    $courseid = $DB->insert_record('course', $data);
    // Crear secciones 0-5
    for ($i = 0; $i <= 5; $i++) {
        $sec = new stdClass();
        $sec->course = $courseid; $sec->section = $i;
        $sec->name = ''; $sec->summary = ''; $sec->summaryformat = 1;
        $sec->sequence = ''; $sec->visible = 1; $sec->timemodified = time();
        $DB->insert_record('course_sections', $sec);
    }
    $course = $DB->get_record('course', array('id' => $courseid));
    echo "Curso creado id=$courseid\n";
} else {
    echo "Curso existente id={$course->id}\n";
}
$courseid = $course->id;

// Nombres de secciones
$sectionnames = array(
    1 => 'Introducción y Marco Conceptual',
    2 => 'Identificación del Problema y Diagnóstico',
    3 => 'Formulación de Objetivos y Marco Lógico',
    4 => 'Metodología y Cronograma',
    5 => 'Evaluación y Presentación Final',
);
$sectionmap = array();
foreach ($DB->get_records('course_sections', array('course' => $courseid), 'section ASC') as $s) {
    $sectionmap[$s->section] = $s->id;
    if (isset($sectionnames[$s->section]) && empty($s->name)) {
        $DB->set_field('course_sections', 'name', $sectionnames[$s->section], array('id' => $s->id));
    }
}
echo "Secciones: " . implode(', ', array_keys($sectionmap)) . "\n";

// -----------------------------------------------------------------------
// 3. Matriculación
// -----------------------------------------------------------------------
echo "\n--- Matriculación ---\n";
// Asegurar que existe el método de enrolamiento manual para el curso
$enrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));
if (!$enrol) {
    $e = new stdClass();
    $e->enrol = 'manual'; $e->courseid = $courseid; $e->status = 0;
    $e->sortorder = 1; $e->timecreated = time(); $e->timemodified = time();
    $DB->insert_record('enrol', $e);
    $enrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));
}

// Contexto del curso
$coursecontext = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $courseid));
if (!$coursecontext) {
    $ctx = new stdClass();
    $ctx->contextlevel = 50; $ctx->instanceid = $courseid;
    $ctx->depth = 2; $ctx->locked = 0;
    $ctxid = $DB->insert_record('context', $ctx);
    $DB->set_field('context', 'path', '/1/'.$ctxid, array('id' => $ctxid));
    $coursecontext = $DB->get_record('context', array('id' => $ctxid));
}

// Función para matricular usuario
function enrol_user_in_course($userid, $enrol, $roleid, $coursecontext, $DB) {
    $existing = $DB->get_record('user_enrolments', array('enrolid' => $enrol->id, 'userid' => $userid));
    if (!$existing) {
        $ue = new stdClass();
        $ue->enrolid = $enrol->id; $ue->userid = $userid;
        $ue->status = 0; $ue->timestart = 0; $ue->timeend = 0;
        $ue->modifierid = 2; $ue->timecreated = time(); $ue->timemodified = time();
        $DB->insert_record('user_enrolments', $ue);
    }
    $ra = $DB->get_record('role_assignments', array('roleid' => $roleid, 'userid' => $userid, 'contextid' => $coursecontext->id));
    if (!$ra) {
        $r = new stdClass();
        $r->roleid = $roleid; $r->contextid = $coursecontext->id;
        $r->userid = $userid; $r->timemodified = time();
        $r->modifierid = 2; $r->component = ''; $r->itemid = 0;
        $DB->insert_record('role_assignments', $r);
    }
}
enrol_user_in_course($profesor->id, $enrol, 3, $coursecontext, $DB);
echo "Profesor matriculado (editingteacher)\n";
enrol_user_in_course($estudiante->id, $enrol, 5, $coursecontext, $DB);
echo "Estudiante matriculada (student)\n";

// -----------------------------------------------------------------------
// 4. Actividades
// -----------------------------------------------------------------------
echo "\n--- Actividades ---\n";
rebuild_course_cache($courseid, true);

// — FORO DE DEBATE (sección 1)
$forum = $DB->get_record('forum', array('course' => $courseid, 'type' => 'general'));
if (!$forum) {
    $f = new stdClass();
    $f->course = $courseid; $f->type = 'general';
    $f->name = 'Foro: Discusión y Presentación de Proyectos';
    $f->intro = '<p>Espacio para compartir avances, dudas y propuestas del proyecto final.</p>';
    $f->introformat = 1; $f->maxattachments = 9;
    $f->forcesubscribe = 0; $f->timemodified = time();
    $forumid = $DB->insert_record('forum', $f);
    add_cm($courseid, $sectionmap[1], 'forum', $forumid);
    $forum = $DB->get_record('forum', array('id' => $forumid));
    echo "Foro creado id=$forumid\n";
} else { echo "Foro existente id={$forum->id}\n"; }

// — PÁGINA 1: ¿Qué es un proyecto? (sección 1)
if (!$DB->record_exists('page', array('course' => $courseid, 'name' => '¿Qué es un proyecto? Conceptos clave'))) {
    $p = new stdClass();
    $p->course = $courseid; $p->name = '¿Qué es un proyecto? Conceptos clave';
    $p->intro = '<p>Lectura introductoria.</p>'; $p->introformat = 1;
    $p->content = '<h2>Conceptos clave en Formulación de Proyectos</h2>
<p>Un <strong>proyecto</strong> es una iniciativa temporal para crear un producto, servicio o resultado único.</p>
<h3>Ciclo de vida</h3><ol>
  <li>Identificación del problema</li><li>Formulación y diseño</li>
  <li>Aprobación y financiamiento</li><li>Ejecución y seguimiento</li><li>Evaluación y cierre</li>
</ol>
<h3>Enfoques metodológicos</h3>
<table border="1" cellpadding="6"><thead><tr><th>Enfoque</th><th>Uso principal</th></tr></thead><tbody>
  <tr><td>Marco Lógico</td><td>Proyectos de desarrollo social e institucional</td></tr>
  <tr><td>PMI / PMBOK</td><td>Proyectos empresariales y de infraestructura</td></tr>
  <tr><td>Metodologías ágiles</td><td>Proyectos de software e innovación</td></tr>
</tbody></table>';
    $p->contentformat = 1; $p->timemodified = time();
    $pid = $DB->insert_record('page', $p);
    add_cm($courseid, $sectionmap[1], 'page', $pid);
    echo "Página 1 creada id=$pid\n";
}

// — GLOSARIO (sección 1)
if (!$DB->record_exists('glossary', array('course' => $courseid))) {
    $g = new stdClass();
    $g->course = $courseid; $g->name = 'Glosario: Términos de Formulación de Proyectos';
    $g->intro = '<p>Conceptos fundamentales del curso.</p>'; $g->introformat = 1;
    $g->allowduplicatedentries = 0; $g->displayformat = 'dictionary';
    $g->mainglossary = 1; $g->showspecial = 1; $g->showalphabet = 1;
    $g->showall = 1; $g->allowcomments = 1; $g->usedynalink = 1;
    $g->defaultapproval = 1; $g->approvaldisplayformat = 'dictionary';
    $g->globalglossary = 0; $g->timecreated = time(); $g->timemodified = time();
    $gid = $DB->insert_record('glossary', $g);
    add_cm($courseid, $sectionmap[1], 'glossary', $gid);
    $terms = array(
        array('Marco Lógico', 'Herramienta de planificación que resume en una matriz 4×4 los elementos esenciales de un proyecto: fin, propósito, componentes y actividades.'),
        array('Indicador', 'Variable que permite medir el logro de un objetivo. Debe cumplir los criterios SMART.'),
        array('Árbol de problemas', 'Técnica que organiza visualmente un problema central, sus causas (raíces) y efectos (copa).'),
        array('Stakeholder', 'Persona u organización que tiene interés o se ve afectada por el proyecto.'),
        array('Línea base', 'Medición inicial de los indicadores antes de ejecutar el proyecto.'),
        array('Viabilidad', 'Análisis que determina si un proyecto es técnica, económica, social y ambientalmente factible.'),
    );
    foreach ($terms as $t) {
        $e = new stdClass();
        $e->course = $courseid; $e->glossaryid = $gid; $e->userid = $profesor->id;
        $e->concept = $t[0]; $e->definition = '<p>'.$t[1].'</p>'; $e->definitionformat = 1;
        $e->approved = 1; $e->usedynalink = 1; $e->casesensitive = 0; $e->fullmatch = 0;
        $e->timecreated = time(); $e->timemodified = time();
        $DB->insert_record('glossary_entries', $e);
    }
    echo "Glosario creado id=$gid con ".count($terms)." términos\n";
}

// — PÁGINA 2: Árbol de problemas (sección 2)
if (!$DB->record_exists('page', array('course' => $courseid, 'name' => 'Árbol de Problemas y Árbol de Objetivos'))) {
    $p = new stdClass();
    $p->course = $courseid; $p->name = 'Árbol de Problemas y Árbol de Objetivos';
    $p->intro = '<p>Herramientas de diagnóstico.</p>'; $p->introformat = 1;
    $p->content = '<h2>Árbol de Problemas</h2>
<p>Identifica: <strong>problema central</strong> (tronco), <strong>causas</strong> (raíces) y <strong>efectos</strong> (copa).</p>
<h3>Pasos</h3><ol>
  <li>Identificar el problema central con la comunidad.</li>
  <li>Listar causas directas e indirectas.</li>
  <li>Listar efectos directos e indirectos.</li>
  <li>Validar con actores clave.</li>
</ol>
<h2>Árbol de Objetivos</h2>
<p>Se construye invirtiendo las relaciones: causas → medios, efectos → fines, problema → objetivo general.</p>';
    $p->contentformat = 1; $p->timemodified = time();
    $pid = $DB->insert_record('page', $p);
    add_cm($courseid, $sectionmap[2], 'page', $pid);
    echo "Página 2 creada id=$pid\n";
}

// — TAREA 1 (sección 2)
if (!$DB->record_exists('assign', array('course' => $courseid, 'name' => 'Entrega 1: Árbol de Problemas de tu proyecto'))) {
    $a = new stdClass();
    $a->course = $courseid; $a->name = 'Entrega 1: Árbol de Problemas de tu proyecto';
    $a->intro = '<p>Construye el árbol de problemas de tu proyecto con al menos 4 causas y 4 efectos. Formato: PDF o imagen.</p>
<p><strong>Criterios:</strong> Claridad del problema central (30%), coherencia causal (40%), sustento con evidencia (30%).</p>';
    $a->introformat = 1; $a->alwaysshowdescription = 1; $a->submissiondrafts = 0;
    $a->sendnotifications = 0; $a->sendlatenotifications = 0;
    $a->duedate = mktime(23,59,0,3,13,2026); $a->allowsubmissionsfromdate = time();
    $a->grade = 100; $a->timemodified = time(); $a->requiresubmissionstatement = 0;
    $a->completionsubmit = 1; $a->teamsubmission = 0; $a->requireallteammemberssubmit = 0;
    $a->blindmarking = 0; $a->revealidentities = 0; $a->attemptreopenmethod = 'none';
    $a->maxattempts = -1; $a->markingworkflow = 0; $a->markingallocation = 0;
    $a->sendstudentnotifications = 1;
    $aid = $DB->insert_record('assign', $a);
    $ap = new stdClass(); $ap->assignment = $aid; $ap->plugin = 'file';
    $ap->subtype = 'assignsubmission'; $ap->name = 'enabled'; $ap->value = '1';
    $DB->insert_record('assign_plugin_config', $ap);
    add_cm($courseid, $sectionmap[2], 'assign', $aid);
    echo "Tarea 1 creada id=$aid\n";
}

// — PÁGINA 3: Marco Lógico (sección 3)
if (!$DB->record_exists('page', array('course' => $courseid, 'name' => 'Matriz de Marco Lógico (MML)'))) {
    $p = new stdClass();
    $p->course = $courseid; $p->name = 'Matriz de Marco Lógico (MML)';
    $p->intro = '<p>Guía para construir la MML.</p>'; $p->introformat = 1;
    $p->content = '<h2>Matriz de Marco Lógico</h2>
<table border="1" cellpadding="8"><thead>
  <tr><th>Nivel</th><th>Indicadores</th><th>Medios de verificación</th><th>Supuestos</th></tr>
</thead><tbody>
  <tr><td><strong>Fin (Impacto)</strong></td><td>¿Cómo se mide el impacto?</td><td>Estadísticas, encuestas</td><td>Factores externos</td></tr>
  <tr><td><strong>Propósito (Objetivo)</strong></td><td>¿Cómo se mide el logro?</td><td>Informes, registros</td><td>Riesgos a gestionar</td></tr>
  <tr><td><strong>Componentes (Productos)</strong></td><td>¿Qué se entrega?</td><td>Documentos, prototipos</td><td>Condiciones necesarias</td></tr>
  <tr><td><strong>Actividades</strong></td><td>¿Cuánto cuesta? ¿Cuánto tiempo?</td><td>Cronograma, presupuesto</td><td>Recursos disponibles</td></tr>
</tbody></table>
<h3>Criterios SMART</h3>
<ul><li><strong>S</strong>pecific — Específico</li><li><strong>M</strong>easurable — Medible</li>
<li><strong>A</strong>chievable — Alcanzable</li><li><strong>R</strong>elevant — Relevante</li>
<li><strong>T</strong>ime-bound — Con plazo definido</li></ul>';
    $p->contentformat = 1; $p->timemodified = time();
    $pid = $DB->insert_record('page', $p);
    add_cm($courseid, $sectionmap[3], 'page', $pid);
    echo "Página 3 creada id=$pid\n";
}

// — TAREA 2 (sección 3)
if (!$DB->record_exists('assign', array('course' => $courseid, 'name' => 'Entrega 2: Matriz de Marco Lógico (MML)'))) {
    $a = new stdClass();
    $a->course = $courseid; $a->name = 'Entrega 2: Matriz de Marco Lógico (MML)';
    $a->intro = '<p>Completa la MML con los cuatro niveles, indicadores SMART, medios de verificación y supuestos.</p>
<p><strong>Criterios:</strong> Articulación vertical (35%), indicadores SMART (35%), supuestos (30%).</p>';
    $a->introformat = 1; $a->alwaysshowdescription = 1; $a->submissiondrafts = 0;
    $a->sendnotifications = 0; $a->sendlatenotifications = 0;
    $a->duedate = mktime(23,59,0,3,20,2026); $a->allowsubmissionsfromdate = time();
    $a->grade = 100; $a->timemodified = time(); $a->requiresubmissionstatement = 0;
    $a->completionsubmit = 1; $a->teamsubmission = 0; $a->requireallteammemberssubmit = 0;
    $a->blindmarking = 0; $a->revealidentities = 0; $a->attemptreopenmethod = 'none';
    $a->maxattempts = -1; $a->markingworkflow = 0; $a->markingallocation = 0;
    $a->sendstudentnotifications = 1;
    $aid = $DB->insert_record('assign', $a);
    $ap = new stdClass(); $ap->assignment = $aid; $ap->plugin = 'file';
    $ap->subtype = 'assignsubmission'; $ap->name = 'enabled'; $ap->value = '1';
    $DB->insert_record('assign_plugin_config', $ap);
    add_cm($courseid, $sectionmap[3], 'assign', $aid);
    echo "Tarea 2 creada id=$aid\n";
}

// — URL (sección 4)
if (!$DB->record_exists('url', array('course' => $courseid))) {
    $u = new stdClass();
    $u->course = $courseid; $u->name = 'Guía DNP: Metodología General Ajustada (MGA)';
    $u->intro = '<p>Enlace al portal oficial de la MGA del DNP de Colombia.</p>'; $u->introformat = 1;
    $u->externalurl = 'https://mgaweb.dnp.gov.co/'; $u->display = 0; $u->timemodified = time();
    $uid = $DB->insert_record('url', $u);
    add_cm($courseid, $sectionmap[4], 'url', $uid);
    echo "URL creada id=$uid\n";
}

// — QUIZ (sección 4)
if (!$DB->record_exists('quiz', array('course' => $courseid))) {
    $q = new stdClass();
    $q->course = $courseid; $q->name = 'Quiz: Metodologías de Formulación de Proyectos';
    $q->intro = '<p>Autoevaluación sobre Marco Lógico, árbol de problemas e indicadores SMART. 2 intentos disponibles.</p>';
    $q->introformat = 1; $q->timeopen = time();
    $q->timeclose = mktime(23,59,0,3,27,2026); $q->timelimit = 1800;
    $q->overduehandling = 'autosubmit'; $q->graceperiod = 0;
    $q->preferredbehaviour = 'deferredfeedback'; $q->canredoquestions = 0;
    $q->attempts = 2; $q->attemptonlast = 0; $q->grademethod = 1;
    $q->decimalpoints = 2; $q->questiondecimalpoints = -1;
    $q->reviewattempt = 69904; $q->reviewcorrectness = 69904; $q->reviewmarks = 69908;
    $q->reviewspecificfeedback = 69904; $q->reviewgeneralfeedback = 69904;
    $q->reviewrightanswer = 69904; $q->reviewoverallfeedback = 4368;
    $q->questionsperpage = 1; $q->navmethod = 'free'; $q->shuffleanswers = 1;
    $q->sumgrades = 5; $q->grade = 100;
    $q->timecreated = time(); $q->timemodified = time();
    $q->browsersecurity = '-'; $q->delay1 = 0; $q->delay2 = 0;
    $q->showuserpicture = 0; $q->showblocks = 0;
    $quizid = $DB->insert_record('quiz', $q);
    $quizcmid = add_cm($courseid, $sectionmap[4], 'quiz', $quizid);
    echo "Quiz creado id=$quizid\n";

    // Contexto para el quiz
    rebuild_course_cache($courseid, true);
    $coursecontext2 = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $courseid), '*', MUST_EXIST);
    $quizctx = $DB->get_record('context', array('contextlevel' => 70, 'instanceid' => $quizcmid));
    if (!$quizctx) {
        $ctx = new stdClass();
        $ctx->contextlevel = 70; $ctx->instanceid = $quizcmid;
        $ctx->depth = $coursecontext2->depth + 1; $ctx->locked = 0;
        $ctxid = $DB->insert_record('context', $ctx);
        $DB->set_field('context', 'path', $coursecontext2->path.'/'.$ctxid, array('id' => $ctxid));
        $quizctx = $DB->get_record('context', array('id' => $ctxid));
    }

    // Categoría de preguntas
    $qcat = $DB->get_record('question_categories', array('contextid' => $coursecontext2->id, 'parent' => 0));
    if (!$qcat) {
        $qc = new stdClass();
        $qc->name = 'Preguntas del curso'; $qc->contextid = $coursecontext2->id;
        $qc->info = ''; $qc->infoformat = 0; $qc->stamp = make_unique_id_code();
        $qc->parent = 0; $qc->sortorder = 999;
        $qc->id = $DB->insert_record('question_categories', $qc);
        $qcat = $qc;
    }

    $qlist = array(
        array('¿Qué representa el tronco en el árbol de problemas?',
              array(array('El problema central',1.0),array('Las causas del problema',0.0),array('Los efectos del problema',0.0),array('Los objetivos del proyecto',0.0)),
              'El tronco representa el problema central; las raíces son causas y la copa son efectos.'),
        array('¿Cuál criterio NO es parte del acrónimo SMART?',
              array(array('Subjetivo',1.0),array('Específico',0.0),array('Medible',0.0),array('Con plazo definido',0.0)),
              'SMART = Específico, Medible, Alcanzable, Relevante, Con plazo. "Subjetivo" no pertenece.'),
        array('¿Qué nivel de la MML describe las actividades e insumos?',
              array(array('Nivel de actividades (insumos)',1.0),array('Nivel de fin (impacto)',0.0),array('Nivel de propósito',0.0),array('Nivel de componentes',0.0)),
              'El nivel más bajo de la MML corresponde a las actividades e insumos necesarios.'),
        array('¿Qué metodología predomina en proyectos de desarrollo social?',
              array(array('Marco Lógico',1.0),array('SCRUM',0.0),array('PRINCE2',0.0),array('Six Sigma',0.0)),
              'El Marco Lógico es el estándar en proyectos sociales de organismos como BID y PNUD.'),
        array('¿Qué son los supuestos en la MML?',
              array(array('Condiciones externas necesarias para el éxito',1.0),array('Los recursos financieros',0.0),array('Los indicadores de impacto',0.0),array('Los objetivos específicos',0.0)),
              'Los supuestos son factores externos fuera del control del equipo que deben cumplirse.'),
    );

    $slot = 1;
    foreach ($qlist as $qd) {
        $qi = new stdClass();
        $qi->parent = 0; $qi->name = $qd[0];
        $qi->questiontext = '<p>'.$qd[0].'</p>'; $qi->questiontextformat = 1;
        $qi->generalfeedback = '<p>'.$qd[2].'</p>'; $qi->generalfeedbackformat = 1;
        $qi->defaultmark = 1.0000000; $qi->penalty = 0.3333333;
        $qi->qtype = 'multichoice'; $qi->length = 1;
        $qi->stamp = make_unique_id_code(); $qi->timecreated = time(); $qi->timemodified = time();
        $qi->createdby = $profesor->id; $qi->modifiedby = $profesor->id;
        $qi->category = $qcat->id; $qi->hidden = 0;
        $qid2 = $DB->insert_record('question', $qi);

        $qbe = new stdClass();
        $qbe->questioncategoryid = $qcat->id; $qbe->idnumber = null; $qbe->ownerid = $profesor->id;
        $qbeid = $DB->insert_record('question_bank_entries', $qbe);

        $qv = new stdClass();
        $qv->questionbankentryid = $qbeid; $qv->version = 1;
        $qv->questionid = $qid2; $qv->status = 'ready';
        $DB->insert_record('question_versions', $qv);

        $mc = new stdClass();
        $mc->questionid = $qid2; $mc->layout = 0; $mc->single = 1; $mc->shuffleanswers = 1;
        $mc->correctfeedback = '<p>¡Correcto!</p>'; $mc->correctfeedbackformat = 1;
        $mc->partiallycorrectfeedback = '<p>Parcialmente correcto.</p>'; $mc->partiallycorrectfeedbackformat = 1;
        $mc->incorrectfeedback = '<p>Incorrecto. Revisa el material.</p>'; $mc->incorrectfeedbackformat = 1;
        $mc->answernumbering = 'abc'; $mc->shownumcorrect = 0; $mc->showstandardinstruction = 0;
        $DB->insert_record('qtype_multichoice_options', $mc);

        foreach ($qd[1] as $ans) {
            $a = new stdClass();
            $a->question = $qid2; $a->answer = $ans[0]; $a->answerformat = 0;
            $a->fraction = $ans[1]; $a->feedback = ''; $a->feedbackformat = 1;
            $DB->insert_record('question_answers', $a);
        }

        $qs = new stdClass();
        $qs->slot = $slot; $qs->quizid = $quizid; $qs->page = $slot;
        $qs->requireprevious = 0; $qs->maxmark = 1.0000000;
        $slotid = $DB->insert_record('quiz_slots', $qs);

        $qr = new stdClass();
        $qr->usingcontextid = $quizctx->id; $qr->component = 'mod_quiz';
        $qr->questionarea = 'slot'; $qr->itemid = $slotid;
        $qr->questionbankentryid = $qbeid; $qr->version = null;
        $DB->insert_record('question_references', $qr);

        echo "  Pregunta quiz [$slot]: ".substr($qd[0],0,50)."\n";
        $slot++;
    }
    $DB->set_field('quiz', 'sumgrades', 5, array('id' => $quizid));
}

// — TAREA FINAL (sección 5)
if (!$DB->record_exists('assign', array('course' => $courseid, 'name' => 'Entrega Final: Propuesta Completa de Proyecto'))) {
    $a = new stdClass();
    $a->course = $courseid; $a->name = 'Entrega Final: Propuesta Completa de Proyecto';
    $a->intro = '<p>Entrega la propuesta completa integrando: resumen ejecutivo, árbol de problemas, MML, cronograma (Gantt), presupuesto, análisis de viabilidad y fuentes de financiamiento.</p>
<p><strong>Formato:</strong> PDF. Máximo 20 páginas. <strong>Valor:</strong> 40% de la nota final.</p>';
    $a->introformat = 1; $a->alwaysshowdescription = 1; $a->submissiondrafts = 1;
    $a->sendnotifications = 1; $a->sendlatenotifications = 1;
    $a->duedate = mktime(23,59,0,4,10,2026); $a->allowsubmissionsfromdate = time();
    $a->grade = 100; $a->timemodified = time(); $a->requiresubmissionstatement = 1;
    $a->completionsubmit = 1; $a->teamsubmission = 0; $a->requireallteammemberssubmit = 0;
    $a->blindmarking = 0; $a->revealidentities = 0; $a->attemptreopenmethod = 'none';
    $a->maxattempts = -1; $a->markingworkflow = 1; $a->markingallocation = 0;
    $a->sendstudentnotifications = 1;
    $aid = $DB->insert_record('assign', $a);
    $ap = new stdClass(); $ap->assignment = $aid; $ap->plugin = 'file';
    $ap->subtype = 'assignsubmission'; $ap->name = 'enabled'; $ap->value = '1';
    $DB->insert_record('assign_plugin_config', $ap);
    add_cm($courseid, $sectionmap[5], 'assign', $aid);
    echo "Tarea final creada id=$aid\n";
}

// — FORO DE PARES (sección 5)
$forum2 = $DB->get_record('forum', array('course' => $courseid, 'name' => 'Foro: Retroalimentación entre Pares'));
if (!$forum2) {
    $f = new stdClass();
    $f->course = $courseid; $f->type = 'general';
    $f->name = 'Foro: Retroalimentación entre Pares';
    $f->intro = '<p>Revisa y comenta las propuestas de tus compañeros. Cada estudiante debe retroalimentar al menos 2 compañeros.</p>';
    $f->introformat = 1; $f->forcesubscribe = 0; $f->maxattachments = 5; $f->timemodified = time();
    $f2id = $DB->insert_record('forum', $f);
    add_cm($courseid, $sectionmap[5], 'forum', $f2id);
    $forum2 = $DB->get_record('forum', array('id' => $f2id));
    echo "Foro de pares creado id=$f2id\n";
}

// -----------------------------------------------------------------------
// 5. Posts en el foro
// -----------------------------------------------------------------------
echo "\n--- Posts en foro ---\n";
$existing_disc = $DB->get_records('forum_discussions', array('forum' => $forum->id));
if (empty($existing_disc)) {
    $disc = new stdClass();
    $disc->course = $courseid; $disc->forum = $forum->id;
    $disc->name = 'Presentación del proyecto final: metodología y marco lógico';
    $disc->firstpost = 0; $disc->userid = $profesor->id; $disc->groupid = -1;
    $disc->assessed = 0; $disc->timemodified = time(); $disc->usermodified = $profesor->id;
    $disc->timestart = 0; $disc->timeend = 0;
    $discid = $DB->insert_record('forum_discussions', $disc);

    $post = new stdClass();
    $post->discussion = $discid; $post->parent = 0; $post->userid = $profesor->id;
    $post->created = time(); $post->modified = time(); $post->mailed = 1;
    $post->subject = 'Presentación del proyecto final: metodología y marco lógico';
    $post->message = '<p>Estimados estudiantes,</p>
<p>Bienvenidos al foro de discusión del curso <strong>Formulación de Proyectos</strong>.</p>
<p>Cada grupo deberá presentar:</p>
<ol>
  <li>Título tentativo del proyecto.</li>
  <li>Problema identificado y su justificación (árbol de problemas).</li>
  <li>Objetivo general y específicos (árbol de objetivos).</li>
  <li>Metodología propuesta: Marco Lógico, PMI, SCRUM u otra justificada.</li>
  <li>Cronograma estimado (mínimo 4 fases).</li>
  <li>Fuentes de financiamiento posibles.</li>
</ol>
<p>Fecha límite: <strong>viernes de esta semana</strong>.</p>
<p>Atentamente,<br><strong>Carlos Martínez</strong><br>Docente</p>';
    $post->messageformat = 1; $post->messagetrust = 0;
    $post->attachment = ''; $post->totalscore = 0; $post->mailnow = 0;
    $postid = $DB->insert_record('forum_posts', $post);
    $DB->set_field('forum_discussions', 'firstpost', $postid, array('id' => $discid));

    $reply = new stdClass();
    $reply->discussion = $discid; $reply->parent = $postid; $reply->userid = $estudiante->id;
    $reply->created = time()+3600; $reply->modified = time()+3600; $reply->mailed = 1;
    $reply->subject = 'Re: Presentación del proyecto final — Propuesta Grupo 1';
    $reply->message = '<p>Buenos días profesor Martínez,</p>
<p>Comparto la propuesta de mi grupo:</p>
<h3>Sistema participativo de seguimiento para proyectos comunitarios en zonas rurales de Colombia</h3>
<p><strong>Problema:</strong> Los proyectos de inversión social rural carecen de mecanismos de monitoreo accesibles, dificultando la rendición de cuentas y la participación ciudadana.</p>
<p><strong>Objetivo general:</strong> Diseñar una herramienta digital de bajo costo para el seguimiento participativo de proyectos comunitarios.</p>
<p><strong>Metodología:</strong> Marco Lógico + Scrum adaptado con co-diseño comunitario.</p>
<p><strong>Cronograma (16 semanas):</strong></p>
<ul>
  <li>Sem 1-4: Diagnóstico participativo.</li>
  <li>Sem 5-8: Diseño de interfaz.</li>
  <li>Sem 9-12: Desarrollo del prototipo.</li>
  <li>Sem 13-16: Validación con comunidades.</li>
</ul>
<p>Saludos,<br><strong>Laura Valero</strong></p>';
    $reply->messageformat = 1; $reply->messagetrust = 0;
    $reply->attachment = ''; $reply->totalscore = 0; $reply->mailnow = 0;
    $replyid = $DB->insert_record('forum_posts', $reply);
    $DB->set_field('forum_discussions', 'timemodified', time()+3600, array('id' => $discid));
    echo "Debate creado: discussion=$discid, post profesor=$postid, respuesta Laura=$replyid\n";
} else {
    echo "Debate ya existe (".count($existing_disc)." discusiones)\n";
}

rebuild_course_cache($courseid, true);

// -----------------------------------------------------------------------
// Resumen
// -----------------------------------------------------------------------
echo "\n=== VERIFICACIÓN FINAL ===\n";
$modmap = array(); foreach ($DB->get_records('modules') as $m) { $modmap[$m->id] = $m->name; }
$secrows = $DB->get_records('course_sections', array('course' => $courseid), 'section ASC');
$secmap3 = array(); foreach ($secrows as $s) { $secmap3[$s->id] = $s; }
foreach ($DB->get_records('course_modules', array('course' => $courseid), 'section ASC,id ASC') as $cm) {
    $sn = isset($secmap3[$cm->section]) ? $secmap3[$cm->section]->section : '?';
    $snm = isset($secmap3[$cm->section]) ? ($secmap3[$cm->section]->name ?: "Sección $sn") : '?';
    echo "  [sec{$sn}] {$modmap[$cm->module]} — {$snm}\n";
}
$posts = $DB->count_records('forum_posts');
echo "\nPosts totales en foros: $posts\n";
$student2 = $DB->get_record('user', array('username' => 'estudiante'));
echo "Estudiante: {$student2->firstname} {$student2->lastname}\n";
$moodleversion = $DB->get_field('config', 'value', array('name' => 'release'));
echo "Moodle: $moodleversion\n";
echo "LISTO\n";
