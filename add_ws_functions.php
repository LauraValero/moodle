<?php
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

$functions_to_add = [
    'mod_assign_get_assignments',
    'mod_quiz_get_quizzes_by_courses',
    'mod_page_get_pages_by_courses',
    'mod_forum_get_discussion_posts',
    'mod_forum_get_discussion_posts_by_userid',
];

$service = $DB->get_record('external_services', ['shortname' => 'mira_setup']);
if (!$service) {
    echo "ERROR: servicio 'mira_setup' no encontrado\n";
    exit(1);
}

foreach ($functions_to_add as $fname) {
    if (!$DB->record_exists('external_services_functions', [
        'externalserviceid' => $service->id,
        'functionname' => $fname
    ])) {
        $DB->insert_record('external_services_functions', [
            'externalserviceid' => $service->id,
            'functionname' => $fname
        ]);
        echo "Agregada: $fname\n";
    } else {
        echo "Ya existia: $fname\n";
    }
}

echo "LISTO\n";
