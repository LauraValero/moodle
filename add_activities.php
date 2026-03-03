<?php
/**
 * Agrega actividades variadas al curso y renombra a la estudiante.
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

$course   = $DB->get_record('course', array('shortname' => 'FORM-PROJ-01'), '*', MUST_EXIST);
$courseid = $course->id;

// -----------------------------------------------------------------------
// Renombrar estudiante
// -----------------------------------------------------------------------
$student = $DB->get_record('user', array('username' => 'estudiante_demo'), '*', MUST_EXIST);
$DB->set_field('user', 'firstname', 'Laura',  array('id' => $student->id));
$DB->set_field('user', 'lastname',  'Valero', array('id' => $student->id));
echo "Estudiante renombrada: Laura Valero\n";

// -----------------------------------------------------------------------
// Mapa de secciones
// -----------------------------------------------------------------------
$sections = $DB->get_records('course_sections', array('course' => $courseid), 'section ASC');
$sectionmap = array();
foreach ($sections as $s) {
    $sectionmap[$s->section] = $s->id;
}

function add_cm($courseid, $sectionid, $modname, $instanceid) {
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
    $section = $DB->get_record('course_sections', array('id' => $sectionid));
    $seq = $section->sequence ? $section->sequence . ',' . $cmid : (string)$cmid;
    $DB->set_field('course_sections', 'sequence', $seq, array('id' => $sectionid));
    rebuild_course_cache($courseid, true);
    echo "  [{$modname}] cmid={$cmid} -> sección id={$sectionid}\n";
    return $cmid;
}

// -----------------------------------------------------------------------
// SECCIÓN 1 — Introducción: Glosario de términos clave
// -----------------------------------------------------------------------
echo "\n--- Glosario (sección 1) ---\n";
$glosario = new stdClass();
$glosario->course          = $courseid;
$glosario->name            = 'Glosario: Términos de Formulación de Proyectos';
$glosario->intro           = '<p>Consulta los conceptos fundamentales del curso.</p>';
$glosario->introformat     = 1;
$glosario->allowduplicatedentries = 0;
$glosario->displayformat   = 'dictionary';
$glosario->mainglossary    = 1;
$glosario->showspecial     = 1;
$glosario->showalphabet    = 1;
$glosario->showall         = 1;
$glosario->allowcomments   = 1;
$glosario->usedynalink     = 1;
$glosario->defaultapproval = 1;
$glosario->approvaldisplayformat = 'dictionary';
$glosario->globalglossary  = 0;
$glosario->timecreated     = time();
$glosario->timemodified    = time();
$glosarioid = $DB->insert_record('glossary', $glosario);
echo "Glosario id=$glosarioid\n";
$gcmid = add_cm($courseid, $sectionmap[1], 'glossary', $glosarioid);

// Entradas del glosario
$terms = array(
    array('Marco Lógico', 'Herramienta de planificación que resume en una matriz de 4x4 los elementos esenciales de un proyecto: fin, propósito, componentes y actividades, con sus respectivos indicadores, medios de verificación y supuestos.'),
    array('Indicador', 'Variable que permite medir el logro de un objetivo. Debe cumplir los criterios SMART: específico, medible, alcanzable, relevante y con plazo definido.'),
    array('Árbol de problemas', 'Técnica participativa que organiza visualmente un problema central, sus causas (raíces) y sus efectos (copa), facilitando el análisis de causalidad.'),
    array('Stakeholder', 'Persona, grupo u organización que tiene interés o se ve afectada por el proyecto. Incluye beneficiarios, financiadores, ejecutores y opositores.'),
    array('Línea base', 'Medición inicial de los indicadores antes de ejecutar el proyecto. Sirve como referencia para evaluar el progreso y el impacto.'),
    array('Viabilidad', 'Análisis que determina si un proyecto es técnicamente posible, económicamente rentable, socialmente aceptable y ambientalmente sostenible.'),
);
$profesor = $DB->get_record('user', array('username' => 'profesor_demo'), '*', MUST_EXIST);
foreach ($terms as $t) {
    $entry = new stdClass();
    $entry->course       = $courseid;
    $entry->glossaryid   = $glosarioid;
    $entry->userid       = $profesor->id;
    $entry->concept      = $t[0];
    $entry->definition   = '<p>' . $t[1] . '</p>';
    $entry->definitionformat = 1;
    $entry->approved     = 1;
    $entry->usedynalink  = 1;
    $entry->casesensitive = 0;
    $entry->fullmatch    = 0;
    $entry->timecreated  = time();
    $entry->timemodified = time();
    $DB->insert_record('glossary_entries', $entry);
    echo "  Término: {$t[0]}\n";
}

// -----------------------------------------------------------------------
// SECCIÓN 2 — Tarea: Entrega árbol de problemas
// -----------------------------------------------------------------------
echo "\n--- Tarea (sección 2) ---\n";
$assign = new stdClass();
$assign->course              = $courseid;
$assign->name                = 'Entrega 1: Árbol de Problemas de tu proyecto';
$assign->intro               = '<p>Construye el árbol de problemas de tu proyecto aplicando la metodología vista en clase. Identifica el problema central, al menos 4 causas y 4 efectos.</p>
<p><strong>Formato:</strong> PDF o imagen (JPG/PNG). Máximo 5 MB.</p>
<p><strong>Criterios de evaluación:</strong></p>
<ul>
  <li>Claridad en la identificación del problema central (30%)</li>
  <li>Coherencia causal entre causas, problema y efectos (40%)</li>
  <li>Sustento con evidencia o datos (30%)</li>
</ul>';
$assign->introformat         = 1;
$assign->alwaysshowdescription = 1;
$assign->submissiondrafts    = 0;
$assign->sendnotifications   = 0;
$assign->sendlatenotifications = 0;
$assign->duedate             = mktime(23, 59, 0, 3, 13, 2026);
$assign->allowsubmissionsfromdate = time();
$assign->grade               = 100;
$assign->timemodified        = time();
$assign->requiresubmissionstatement = 0;
$assign->completionsubmit    = 1;
$assign->teamsubmission      = 0;
$assign->requireallteammemberssubmit = 0;
$assign->blindmarking        = 0;
$assign->revealidentities    = 0;
$assign->attemptreopenmethod = 'none';
$assign->maxattempts         = -1;
$assign->markingworkflow     = 0;
$assign->markingallocation   = 0;
$assign->sendstudentnotifications = 1;
$assignid = $DB->insert_record('assign', $assign);
echo "Tarea id=$assignid\n";

// Plugin de entrega: archivos
$aplugin = new stdClass();
$aplugin->assignment  = $assignid;
$aplugin->plugin      = 'file';
$aplugin->subtype     = 'assignsubmission';
$aplugin->name        = 'enabled';
$aplugin->value       = '1';
$DB->insert_record('assign_plugin_config', $aplugin);

$aplugin2 = new stdClass();
$aplugin2->assignment = $assignid;
$aplugin2->plugin     = 'file';
$aplugin2->subtype    = 'assignsubmission';
$aplugin2->name       = 'maxfilesubmissions';
$aplugin2->value      = '3';
$DB->insert_record('assign_plugin_config', $aplugin2);

add_cm($courseid, $sectionmap[2], 'assign', $assignid);

// -----------------------------------------------------------------------
// SECCIÓN 3 — Tarea: Entrega Matriz de Marco Lógico
// -----------------------------------------------------------------------
echo "\n--- Tarea MML (sección 3) ---\n";
$assign2 = new stdClass();
$assign2->course             = $courseid;
$assign2->name               = 'Entrega 2: Matriz de Marco Lógico (MML)';
$assign2->intro              = '<p>Completa la Matriz de Marco Lógico de tu proyecto con los cuatro niveles (Fin, Propósito, Componentes y Actividades), incluyendo indicadores SMART, medios de verificación y supuestos.</p>
<p><strong>Formato:</strong> Excel (.xlsx) o PDF. Usa la plantilla disponible en el recurso de la sección.</p>
<p><strong>Criterios:</strong></p>
<ul>
  <li>Correcta articulación vertical (lógica de la intervención) — 35%</li>
  <li>Indicadores SMART — 35%</li>
  <li>Supuestos realistas y relevantes — 30%</li>
</ul>';
$assign2->introformat        = 1;
$assign2->alwaysshowdescription = 1;
$assign2->submissiondrafts   = 0;
$assign2->sendnotifications  = 0;
$assign2->sendlatenotifications = 0;
$assign2->duedate            = mktime(23, 59, 0, 3, 20, 2026);
$assign2->allowsubmissionsfromdate = time();
$assign2->grade              = 100;
$assign2->timemodified       = time();
$assign2->requiresubmissionstatement = 0;
$assign2->completionsubmit   = 1;
$assign2->teamsubmission     = 0;
$assign2->requireallteammemberssubmit = 0;
$assign2->blindmarking       = 0;
$assign2->revealidentities   = 0;
$assign2->attemptreopenmethod = 'none';
$assign2->maxattempts        = -1;
$assign2->markingworkflow    = 0;
$assign2->markingallocation  = 0;
$assign2->sendstudentnotifications = 1;
$assign2id = $DB->insert_record('assign', $assign2);
echo "Tarea 2 id=$assign2id\n";
$ap = new stdClass(); $ap->assignment = $assign2id; $ap->plugin = 'file';
$ap->subtype = 'assignsubmission'; $ap->name = 'enabled'; $ap->value = '1';
$DB->insert_record('assign_plugin_config', $ap);
add_cm($courseid, $sectionmap[3], 'assign', $assign2id);

// -----------------------------------------------------------------------
// SECCIÓN 4 — Quiz: Autoevaluación metodologías de proyectos
// -----------------------------------------------------------------------
echo "\n--- Quiz (sección 4) ---\n";
$quiz = new stdClass();
$quiz->course           = $courseid;
$quiz->name             = 'Quiz: Metodologías de Formulación de Proyectos';
$quiz->intro            = '<p>Autoevaluación sobre los conceptos de Marco Lógico, árbol de problemas e indicadores SMART. Tienes <strong>2 intentos</strong> disponibles.</p>';
$quiz->introformat      = 1;
$quiz->timeopen         = time();
$quiz->timeclose        = mktime(23, 59, 0, 3, 27, 2026);
$quiz->timelimit        = 1800;  // 30 min
$quiz->overduehandling  = 'autosubmit';
$quiz->graceperiod      = 0;
$quiz->preferredbehaviour = 'deferredfeedback';
$quiz->canredoquestions = 0;
$quiz->attempts         = 2;
$quiz->attemptonlast    = 0;
$quiz->grademethod      = 1;  // calificación más alta
$quiz->decimalpoints    = 2;
$quiz->questiondecimalpoints = -1;
$quiz->reviewattempt    = 69904;
$quiz->reviewcorrectness = 69904;
$quiz->reviewmarks      = 69908;
$quiz->reviewspecificfeedback = 69904;
$quiz->reviewgeneralfeedback  = 69904;
$quiz->reviewrightanswer      = 69904;
$quiz->reviewoverallfeedback  = 4368;
$quiz->questionsperpage = 1;
$quiz->navmethod        = 'free';
$quiz->shuffleanswers   = 1;
$quiz->sumgrades        = 5;
$quiz->grade            = 100;
$quiz->timecreated      = time();
$quiz->timemodified     = time();
$quiz->browsersecurity  = '-';
$quiz->delay1           = 0;
$quiz->delay2           = 0;
$quiz->showuserpicture  = 0;
$quiz->showblocks       = 0;
$quiz->completionattemptsexhausted = 0;
$quiz->completionpass   = 0;
$quizid = $DB->insert_record('quiz', $quiz);
echo "Quiz id=$quizid\n";
add_cm($courseid, $sectionmap[4], 'quiz', $quizid);

// Crear preguntas en banco de preguntas
// Obtener/crear categoría para el curso
$catcontext = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $courseid));
if (!$catcontext) {
    // Crear contexto del curso si no existe
    $catcontext = new stdClass();
    $catcontext->contextlevel = 50;
    $catcontext->instanceid   = $courseid;
    $catcontext->depth        = 3;
    $catcontext->path         = '';
    $catcontext->id = $DB->insert_record('context', $catcontext);
}

$qcat = $DB->get_record('question_categories', array('contextid' => $catcontext->id, 'parent' => 0));
if (!$qcat) {
    $qcat = new stdClass();
    $qcat->name       = 'Preguntas del curso';
    $qcat->contextid  = $catcontext->id;
    $qcat->info       = '';
    $qcat->infoformat = 0;
    $qcat->stamp      = make_unique_id_code();
    $qcat->parent     = 0;
    $qcat->sortorder  = 999;
    $qcat->id = $DB->insert_record('question_categories', $qcat);
}
echo "Categoria de preguntas id={$qcat->id}\n";

// Preguntas de opción múltiple
$questions_data = array(
    array(
        'name'     => '¿Qué representa el tronco en el árbol de problemas?',
        'answers'  => array(
            array('El problema central', 1.0),
            array('Las causas del problema', 0.0),
            array('Los efectos del problema', 0.0),
            array('Los objetivos del proyecto', 0.0),
        ),
        'feedback' => 'El tronco del árbol de problemas representa el problema central identificado.',
    ),
    array(
        'name'     => '¿Cuál de los siguientes NO es un criterio SMART para indicadores?',
        'answers'  => array(
            array('Subjetivo', 1.0),
            array('Específico', 0.0),
            array('Medible', 0.0),
            array('Con plazo definido', 0.0),
        ),
        'feedback' => 'SMART significa: Específico, Medible, Alcanzable, Relevante y con plazo (Time-bound). "Subjetivo" no es un criterio SMART.',
    ),
    array(
        'name'     => '¿Qué nivel de la Matriz de Marco Lógico describe las actividades del proyecto?',
        'answers'  => array(
            array('El nivel de actividades (insumos)', 1.0),
            array('El nivel de fin (impacto)', 0.0),
            array('El nivel de propósito (objetivo)', 0.0),
            array('El nivel de componentes (productos)', 0.0),
        ),
        'feedback' => 'El nivel más bajo de la MML corresponde a las actividades e insumos necesarios para generar los componentes.',
    ),
    array(
        'name'     => '¿Qué metodología es más usada en proyectos de desarrollo social e institucional?',
        'answers'  => array(
            array('Marco Lógico', 1.0),
            array('SCRUM', 0.0),
            array('PRINCE2', 0.0),
            array('Six Sigma', 0.0),
        ),
        'feedback' => 'El Marco Lógico es la metodología más extendida en proyectos de desarrollo social, especialmente en organismos internacionales y gobiernos.',
    ),
    array(
        'name'     => '¿Qué son los "supuestos" en la Matriz de Marco Lógico?',
        'answers'  => array(
            array('Condiciones externas necesarias para el éxito del proyecto', 1.0),
            array('Los recursos financieros del proyecto', 0.0),
            array('Los indicadores de impacto', 0.0),
            array('Los objetivos específicos del proyecto', 0.0),
        ),
        'feedback' => 'Los supuestos son factores externos fuera del control del equipo de proyecto que deben cumplirse para que la lógica de la intervención funcione.',
    ),
);

$slot = 1;
foreach ($questions_data as $qdata) {
    // Crear pregunta base
    $q = new stdClass();
    $q->category        = $qcat->id;
    $q->parent          = 0;
    $q->name            = $qdata['name'];
    $q->questiontext    = '<p>' . $qdata['name'] . '</p>';
    $q->questiontextformat = 1;
    $q->generalfeedback = '<p>' . $qdata['feedback'] . '</p>';
    $q->generalfeedbackformat = 1;
    $q->defaultmark     = 1.0;
    $q->penalty         = 0.3333333;
    $q->qtype           = 'multichoice';
    $q->length          = 1;
    $q->stamp           = make_unique_id_code();
    $q->version         = make_unique_id_code();
    $q->hidden          = 0;
    $q->timecreated     = time();
    $q->timemodified    = time();
    $q->createdby       = $profesor->id;
    $q->modifiedby      = $profesor->id;
    $qid = $DB->insert_record('question', $q);

    // Opciones multichoice
    $mc = new stdClass();
    $mc->question        = $qid;
    $mc->layout          = 0;
    $mc->answers         = '';
    $mc->single          = 1;
    $mc->shuffleanswers  = 1;
    $mc->correctfeedback = '<p>¡Correcto!</p>';
    $mc->correctfeedbackformat = 1;
    $mc->partiallycorrectfeedback = '<p>Parcialmente correcto.</p>';
    $mc->partiallycorrectfeedbackformat = 1;
    $mc->incorrectfeedback = '<p>Incorrecto. Revisa el material del curso.</p>';
    $mc->incorrectfeedbackformat = 1;
    $mc->answernumbering = 'abc';
    $mc->showstandardinstruction = 0;
    $DB->insert_record('qtype_multichoice_options', $mc);

    // Respuestas
    foreach ($qdata['answers'] as $ans) {
        $a = new stdClass();
        $a->question     = $qid;
        $a->answer       = $ans[0];
        $a->answerformat = 0;
        $a->fraction     = $ans[1];
        $a->feedback     = '';
        $a->feedbackformat = 1;
        $DB->insert_record('question_answers', $a);
    }

    // Agregar al quiz
    $qs = new stdClass();
    $qs->slot          = $slot++;
    $qs->quizid        = $quizid;
    $qs->questionid    = $qid;
    $qs->page          = $qs->slot;
    $qs->requireprevious = 0;
    $qs->maxmark       = 1.0;
    $DB->insert_record('quiz_slots', $qs);

    echo "  Pregunta [{$qid}]: " . substr($qdata['name'], 0, 50) . "...\n";
}

// -----------------------------------------------------------------------
// SECCIÓN 5 — Tarea final: Propuesta completa de proyecto
// -----------------------------------------------------------------------
echo "\n--- Tarea final (sección 5) ---\n";
$assign3 = new stdClass();
$assign3->course             = $courseid;
$assign3->name               = 'Entrega Final: Propuesta Completa de Proyecto';
$assign3->intro              = '<p>Presenta la propuesta completa de tu proyecto integrando todos los elementos del curso:</p>
<ol>
  <li>Resumen ejecutivo (máx. 1 página)</li>
  <li>Árbol de problemas y árbol de objetivos</li>
  <li>Matriz de Marco Lógico completa</li>
  <li>Cronograma de actividades (diagrama de Gantt)</li>
  <li>Presupuesto estimado por componente</li>
  <li>Análisis de viabilidad y riesgos</li>
  <li>Fuentes de financiamiento identificadas</li>
</ol>
<p><strong>Formato:</strong> PDF. Extensión máxima 20 páginas (sin anexos).</p>
<p><strong>Valor:</strong> 40% de la nota final del curso.</p>';
$assign3->introformat        = 1;
$assign3->alwaysshowdescription = 1;
$assign3->submissiondrafts   = 1;
$assign3->sendnotifications  = 1;
$assign3->sendlatenotifications = 1;
$assign3->duedate            = mktime(23, 59, 0, 4, 10, 2026);
$assign3->allowsubmissionsfromdate = time();
$assign3->grade              = 100;
$assign3->timemodified       = time();
$assign3->requiresubmissionstatement = 1;
$assign3->completionsubmit   = 1;
$assign3->teamsubmission     = 0;
$assign3->requireallteammemberssubmit = 0;
$assign3->blindmarking       = 0;
$assign3->revealidentities   = 0;
$assign3->attemptreopenmethod = 'none';
$assign3->maxattempts        = -1;
$assign3->markingworkflow    = 1;
$assign3->markingallocation  = 0;
$assign3->sendstudentnotifications = 1;
$assign3id = $DB->insert_record('assign', $assign3);
echo "Tarea final id=$assign3id\n";
$ap3 = new stdClass(); $ap3->assignment = $assign3id; $ap3->plugin = 'file';
$ap3->subtype = 'assignsubmission'; $ap3->name = 'enabled'; $ap3->value = '1';
$DB->insert_record('assign_plugin_config', $ap3);
add_cm($courseid, $sectionmap[5], 'assign', $assign3id);

// -----------------------------------------------------------------------
// SECCIÓN 5 — Foro de evaluación entre pares
// -----------------------------------------------------------------------
echo "\n--- Foro de evaluación entre pares (sección 5) ---\n";
$forum2 = new stdClass();
$forum2->course       = $courseid;
$forum2->type         = 'general';
$forum2->name         = 'Foro: Retroalimentación entre pares';
$forum2->intro        = '<p>Espacio para que los estudiantes revisen y comenten las propuestas de sus compañeros. Cada estudiante debe dar retroalimentación constructiva a al menos 2 compañeros.</p>';
$forum2->introformat  = 1;
$forum2->forcesubscribe = 0;
$forum2->maxattachments = 5;
$forum2->timemodified = time();
$forum2id = $DB->insert_record('forum', $forum2);
echo "Foro 2 id=$forum2id\n";
add_cm($courseid, $sectionmap[5], 'forum', $forum2id);

rebuild_course_cache($courseid, true);

// -----------------------------------------------------------------------
// Resumen
// -----------------------------------------------------------------------
echo "\n=== RESUMEN FINAL ===\n";
$cms = $DB->get_records('course_modules', array('course' => $courseid));
$secrows = $DB->get_records('course_sections', array('course' => $courseid), 'section ASC');
$secmap2 = array();
foreach ($secrows as $s) { $secmap2[$s->id] = $s; }
$modmap = array();
foreach ($DB->get_records('modules') as $m) { $modmap[$m->id] = $m->name; }

foreach ($cms as $cm) {
    $secnum = isset($secmap2[$cm->section]) ? $secmap2[$cm->section]->section : '?';
    $secname = isset($secmap2[$cm->section]) ? ($secmap2[$cm->section]->name ?: "Sección $secnum") : '?';
    echo "  [sec {$secnum}] {$modmap[$cm->module]} (cmid={$cm->id})\n";
}

$student2 = $DB->get_record('user', array('username' => 'estudiante_demo'));
echo "\nEstudiante: {$student2->firstname} {$student2->lastname}\n";
echo "LISTO\n";
