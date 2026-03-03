<?php
/**
 * Crea contextos para módulos nuevos y agrega preguntas al quiz (Moodle 4.1+).
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

$course   = $DB->get_record('course', array('shortname' => 'FORM-PROJ-01'), '*', MUST_EXIST);
$courseid = $course->id;
$profesor = $DB->get_record('user', array('username' => 'profesor_demo'), '*', MUST_EXIST);

// Crear contextos de módulo para cmids que no los tienen
$cms = $DB->get_records('course_modules', array('course' => $courseid));
$coursecontext = $DB->get_record('context', array('contextlevel' => 50, 'instanceid' => $courseid), '*', MUST_EXIST);

foreach ($cms as $cm) {
    $existing = $DB->get_record('context', array('contextlevel' => 70, 'instanceid' => $cm->id));
    if (!$existing) {
        $ctx = new stdClass();
        $ctx->contextlevel = 70;
        $ctx->instanceid   = $cm->id;
        $ctx->depth        = $coursecontext->depth + 1;
        $ctx->path         = '';
        $ctx->locked       = 0;
        $ctxid = $DB->insert_record('context', $ctx);
        $path = $coursecontext->path . '/' . $ctxid;
        $DB->set_field('context', 'path', $path, array('id' => $ctxid));
        echo "Contexto creado para cmid={$cm->id} -> ctx_id=$ctxid\n";
    }
}

// Obtener quiz
$quiz = $DB->get_record('quiz', array('course' => $courseid), '*', MUST_EXIST);
$quizcm = $DB->get_record('course_modules', array('course' => $courseid, 'instance' => $quiz->id,
    'module' => $DB->get_field('modules', 'id', array('name' => 'quiz'))), '*', MUST_EXIST);
$quizctx = $DB->get_record('context', array('contextlevel' => 70, 'instanceid' => $quizcm->id), '*', MUST_EXIST);
echo "Quiz cmid={$quizcm->id}, context_id={$quizctx->id}\n";

// Limpiar preguntas anteriores fallidas
$DB->delete_records('quiz_slots', array('quizid' => $quiz->id));
$DB->delete_records('question_references', array('usingcontextid' => $quizctx->id));
$existing_qbe = $DB->get_records('question_bank_entries', array('questioncategoryid' => 1));
foreach ($existing_qbe as $qbe) {
    $vers = $DB->get_records('question_versions', array('questionbankentryid' => $qbe->id));
    foreach ($vers as $v) {
        $DB->delete_records('question_answers', array('question' => $v->questionid));
        $DB->delete_records('qtype_multichoice_options', array('questionid' => $v->questionid));
        $DB->delete_records('question', array('id' => $v->questionid));
    }
    $DB->delete_records('question_versions', array('questionbankentryid' => $qbe->id));
}
$DB->delete_records('question_bank_entries', array('questioncategoryid' => 1));
echo "Preguntas anteriores limpiadas\n";

// Categoría de preguntas del curso
$qcat = $DB->get_record('question_categories', array('id' => 1));
if (!$qcat) {
    $qcat = new stdClass();
    $qcat->name       = 'Preguntas del curso';
    $qcat->contextid  = $coursecontext->id;
    $qcat->info       = '';
    $qcat->infoformat = 0;
    $qcat->stamp      = make_unique_id_code();
    $qcat->parent     = 0;
    $qcat->sortorder  = 999;
    $qcat->id = $DB->insert_record('question_categories', $qcat);
}
echo "Categoria id={$qcat->id}\n";

// Preguntas
$questions_data = array(
    array(
        'name'     => '¿Qué representa el tronco en el árbol de problemas?',
        'text'     => '<p>¿Qué representa el tronco en el árbol de problemas?</p>',
        'feedback' => 'El tronco del árbol de problemas representa el <strong>problema central</strong> identificado. Las raíces son las causas y la copa son los efectos.',
        'answers'  => array(
            array('El problema central', 1.0),
            array('Las causas del problema', 0.0),
            array('Los efectos del problema', 0.0),
            array('Los objetivos del proyecto', 0.0),
        ),
    ),
    array(
        'name'     => '¿Cuál de los siguientes NO es un criterio SMART?',
        'text'     => '<p>¿Cuál de los siguientes criterios NO hace parte del acrónimo SMART para indicadores?</p>',
        'feedback' => 'SMART = Específico, Medible, Alcanzable, Relevante, Con plazo (Time-bound). "Subjetivo" no forma parte.',
        'answers'  => array(
            array('Subjetivo', 1.0),
            array('Específico', 0.0),
            array('Medible', 0.0),
            array('Con plazo definido', 0.0),
        ),
    ),
    array(
        'name'     => '¿Qué nivel de la MML describe las actividades?',
        'text'     => '<p>¿Qué nivel de la Matriz de Marco Lógico corresponde a las actividades e insumos del proyecto?</p>',
        'feedback' => 'El nivel más bajo (Actividades/Insumos) describe las acciones necesarias para generar los componentes del proyecto.',
        'answers'  => array(
            array('El nivel de actividades (insumos)', 1.0),
            array('El nivel de fin (impacto)', 0.0),
            array('El nivel de propósito (objetivo)', 0.0),
            array('El nivel de componentes (productos)', 0.0),
        ),
    ),
    array(
        'name'     => '¿Qué metodología predomina en proyectos de desarrollo social?',
        'text'     => '<p>¿Qué metodología de gestión de proyectos es más utilizada en proyectos de desarrollo social e institucional?</p>',
        'feedback' => 'El Marco Lógico es la metodología más extendida en proyectos sociales, usado por organismos como BID, PNUD y gobiernos latinoamericanos.',
        'answers'  => array(
            array('Marco Lógico', 1.0),
            array('SCRUM', 0.0),
            array('PRINCE2', 0.0),
            array('Six Sigma', 0.0),
        ),
    ),
    array(
        'name'     => '¿Qué son los supuestos en la MML?',
        'text'     => '<p>¿Qué representan los "supuestos" en la columna derecha de la Matriz de Marco Lógico?</p>',
        'feedback' => 'Los supuestos son condiciones externas fuera del control del equipo, necesarias para que la lógica de intervención del proyecto sea válida.',
        'answers'  => array(
            array('Condiciones externas necesarias para el éxito del proyecto', 1.0),
            array('Los recursos financieros del proyecto', 0.0),
            array('Los indicadores de impacto medibles', 0.0),
            array('Los objetivos específicos del proyecto', 0.0),
        ),
    ),
);

$slot = 1;
foreach ($questions_data as $qdata) {
    // 1. question
    $q = new stdClass();
    $q->parent          = 0;
    $q->name            = $qdata['name'];
    $q->questiontext    = $qdata['text'];
    $q->questiontextformat = 1;
    $q->generalfeedback = '<p>' . $qdata['feedback'] . '</p>';
    $q->generalfeedbackformat = 1;
    $q->defaultmark     = 1.0000000;
    $q->penalty         = 0.3333333;
    $q->qtype           = 'multichoice';
    $q->length          = 1;
    $q->stamp           = make_unique_id_code();
    $q->timecreated     = time();
    $q->timemodified    = time();
    $q->createdby       = $profesor->id;
    $q->modifiedby      = $profesor->id;
    $q->category        = $qcat->id;  // legacy, needed by some checks
    $q->hidden          = 0;
    $qid = $DB->insert_record('question', $q);

    // 2. question_bank_entry
    $qbe = new stdClass();
    $qbe->questioncategoryid = $qcat->id;
    $qbe->idnumber  = null;
    $qbe->ownerid   = $profesor->id;
    $qbeid = $DB->insert_record('question_bank_entries', $qbe);

    // 3. question_versions
    $qv = new stdClass();
    $qv->questionbankentryid = $qbeid;
    $qv->version    = 1;
    $qv->questionid = $qid;
    $qv->status     = 'ready';
    $DB->insert_record('question_versions', $qv);

    // 4. qtype_multichoice_options
    $mc = new stdClass();
    $mc->questionid  = $qid;
    $mc->layout      = 0;
    $mc->single      = 1;
    $mc->shuffleanswers = 1;
    $mc->correctfeedback = '<p>¡Correcto!</p>';
    $mc->correctfeedbackformat = 1;
    $mc->partiallycorrectfeedback = '<p>Parcialmente correcto.</p>';
    $mc->partiallycorrectfeedbackformat = 1;
    $mc->incorrectfeedback = '<p>Incorrecto. Revisa el material del curso.</p>';
    $mc->incorrectfeedbackformat = 1;
    $mc->answernumbering = 'abc';
    $mc->shownumcorrect  = 0;
    $mc->showstandardinstruction = 0;
    $DB->insert_record('qtype_multichoice_options', $mc);

    // 5. question_answers
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

    // 6. quiz_slots
    $qs = new stdClass();
    $qs->slot          = $slot;
    $qs->quizid        = $quiz->id;
    $qs->page          = $slot;
    $qs->requireprevious = 0;
    $qs->maxmark       = 1.0000000;
    $slotid = $DB->insert_record('quiz_slots', $qs);

    // 7. question_references
    $qr = new stdClass();
    $qr->usingcontextid     = $quizctx->id;
    $qr->component          = 'mod_quiz';
    $qr->questionarea       = 'slot';
    $qr->itemid             = $slotid;
    $qr->questionbankentryid = $qbeid;
    $qr->version            = null;
    $DB->insert_record('question_references', $qr);

    echo "  Pregunta [{$qid}] slot {$slot}: " . substr($qdata['name'], 0, 50) . "\n";
    $slot++;
}

// Actualizar sumgrades del quiz
$DB->set_field('quiz', 'sumgrades', count($questions_data), array('id' => $quiz->id));

// -----------------------------------------------------------------------
// Tarea final (sección 5) — si no existe
// -----------------------------------------------------------------------
$sectionmap = array();
foreach ($DB->get_records('course_sections', array('course' => $courseid), 'section ASC') as $s) {
    $sectionmap[$s->section] = $s->id;
}

function add_cm_fix($courseid, $sectionid, $modname, $instanceid) {
    global $DB;
    $mod = $DB->get_record('modules', array('name' => $modname), '*', MUST_EXIST);
    $existing = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $mod->id, 'instance' => $instanceid));
    if ($existing) { echo "  [{$modname}] ya existe cmid={$existing->id}\n"; return $existing->id; }
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

// Tarea final
$existing_assigns = $DB->get_records('assign', array('course' => $courseid));
if (count($existing_assigns) < 3) {
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
<p><strong>Formato:</strong> PDF. Máximo 20 páginas.</p>
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
    add_cm_fix($courseid, $sectionmap[5], 'assign', $assign3id);
}

// Foro de retroalimentación entre pares
$existing_forums = $DB->get_records('forum', array('course' => $courseid));
$has_peer_forum = false;
foreach ($existing_forums as $f) {
    if (strpos($f->name, 'pares') !== false || strpos($f->name, 'Retroalimentación') !== false) {
        $has_peer_forum = true;
    }
}
if (!$has_peer_forum) {
    echo "\n--- Foro retroalimentación entre pares (sección 5) ---\n";
    $forum2 = new stdClass();
    $forum2->course       = $courseid;
    $forum2->type         = 'general';
    $forum2->name         = 'Foro: Retroalimentación entre Pares';
    $forum2->intro        = '<p>Espacio para revisar y comentar las propuestas de tus compañeros. Cada estudiante debe dar retroalimentación constructiva a al menos 2 compañeros usando la rúbrica de evaluación.</p>';
    $forum2->introformat  = 1;
    $forum2->forcesubscribe = 0;
    $forum2->maxattachments = 5;
    $forum2->timemodified = time();
    $forum2id = $DB->insert_record('forum', $forum2);
    echo "Foro 2 id=$forum2id\n";
    add_cm_fix($courseid, $sectionmap[5], 'forum', $forum2id);
}

rebuild_course_cache($courseid, true);

// Verificación final
echo "\n=== VERIFICACIÓN FINAL ===\n";
$modmap = array();
foreach ($DB->get_records('modules') as $m) { $modmap[$m->id] = $m->name; }
$secrows = $DB->get_records('course_sections', array('course' => $courseid), 'section ASC');
$secmap2 = array(); foreach ($secrows as $s) { $secmap2[$s->id] = $s; }

foreach ($DB->get_records('course_modules', array('course' => $courseid), 'id ASC') as $cm) {
    $sn = isset($secmap2[$cm->section]) ? $secmap2[$cm->section]->section : '?';
    $snm = isset($secmap2[$cm->section]) ? ($secmap2[$cm->section]->name ?: "Sección $sn") : '?';
    echo "  [sec{$sn}] {$modmap[$cm->module]} cmid={$cm->id} — {$snm}\n";
}

$slots = $DB->count_records('quiz_slots', array('quizid' => $quiz->id));
echo "\nPreguntas en quiz: $slots\n";
$student = $DB->get_record('user', array('username' => 'estudiante_demo'));
echo "Estudiante: {$student->firstname} {$student->lastname}\n";
echo "LISTO\n";
