<?php
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

// Habilitar webservices y REST
set_config('enablewebservices', 1);
set_config('webserviceprotocols', 'rest');

// Crear o recuperar el servicio REST
$service = $DB->get_record('external_services', array('shortname' => 'mira_setup'));
if (!$service) {
    $serviceobj = new stdClass();
    $serviceobj->name = 'Moodle Middleware Setup';
    $serviceobj->shortname = 'mira_setup';
    $serviceobj->enabled = 1;
    $serviceobj->restrictedusers = 0;
    $serviceobj->downloadfiles = 1;
    $serviceobj->uploadfiles = 1;
    $serviceobj->timecreated = time();
    $serviceobj->timemodified = time();
    $serviceid = $DB->insert_record('external_services', $serviceobj);
    echo "Servicio creado ID: $serviceid\n";
} else {
    $serviceid = $service->id;
    echo "Servicio existente ID: $serviceid\n";
}

// Funciones a agregar
$functions = array(
    // Site
    'core_webservice_get_site_info',
    // Users
    'core_user_create_users',
    'core_user_get_users_by_field',
    // Courses & enrolment
    'core_course_create_courses',
    'core_course_get_courses',
    'core_course_get_contents',
    'core_enrol_get_users_courses',
    'core_enrol_get_enrolled_users',
    'enrol_manual_enrol_users',
    // Assignments
    'mod_assign_get_assignments',
    // Quiz
    'mod_quiz_get_quizzes_by_courses',  // listar quizzes de un curso
    'mod_quiz_start_attempt',           // iniciar preview (get_quiz_questions)
    'mod_quiz_get_attempt_data',        // leer preguntas de un intento activo
    'mod_quiz_process_attempt',         // cerrar preview
    'mod_quiz_get_user_attempts',       // listar intentos de un usuario (get_quiz_attempts)
    'mod_quiz_get_attempt_review',      // revisar intento finalizado con respuestas correctas
    // Pages
    'mod_page_get_pages_by_courses',
    // Forums
    'mod_forum_get_forums_by_courses',
    'mod_forum_get_forum_discussions',
    'mod_forum_get_discussion_posts',
    'mod_forum_get_discussion_posts_by_userid',
    'mod_forum_add_discussion',
    'mod_forum_add_discussion_post',
);

foreach ($functions as $fname) {
    if (!$DB->record_exists('external_services_functions', array('externalserviceid' => $serviceid, 'functionname' => $fname))) {
        $f = new stdClass();
        $f->externalserviceid = $serviceid;
        $f->functionname = $fname;
        $DB->insert_record('external_services_functions', $f);
        echo "Funcion agregada: $fname\n";
    } else {
        echo "Funcion ya existe: $fname\n";
    }
}

// Obtener usuario admin
$admin = get_admin();
echo "Admin ID: {$admin->id}\n";

// Crear o recuperar token para admin
$token = $DB->get_record('external_tokens', array('userid' => $admin->id, 'externalserviceid' => $serviceid, 'tokentype' => 0));
if (!$token) {
    $tokenstr = md5(uniqid(rand(), true));
    $tokenobj = new stdClass();
    $tokenobj->token = $tokenstr;
    $tokenobj->userid = $admin->id;
    $tokenobj->tokentype = 0;
    $tokenobj->externalserviceid = $serviceid;
    $tokenobj->contextid = context_system::instance()->id;
    $tokenobj->creatorid = $admin->id;
    $tokenobj->timecreated = time();
    $tokenobj->validuntil = 0;
    $DB->insert_record('external_tokens', $tokenobj);
    echo "TOKEN: $tokenstr\n";
} else {
    echo "TOKEN: {$token->token}\n";
}

echo "LISTO\n";
