<?php
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

$course = $DB->get_record('course', array('shortname' => 'FORM-PROJ-01'));
echo "Curso: {$course->fullname} (id={$course->id})\n\n";

$sections = $DB->get_records('course_sections', array('course' => $course->id), 'section ASC');
$sectionmap = array();
foreach ($sections as $s) { $sectionmap[$s->id] = $s; }

$cms = $DB->get_records('course_modules', array('course' => $course->id));
echo "Modulos (" . count($cms) . "):\n";
foreach ($cms as $cm) {
    $mod = $DB->get_record('modules', array('id' => $cm->module));
    $sec = isset($sectionmap[$cm->section]) ? $sectionmap[$cm->section]->section : '?';
    $secname = isset($sectionmap[$cm->section]) ? ($sectionmap[$cm->section]->name ?: "Sección $sec") : '?';
    echo "  [cmid={$cm->id}] {$mod->name} → {$secname}\n";
}

$posts = $DB->get_records('forum_posts');
echo "\nPosts en foro (" . count($posts) . "):\n";
foreach ($posts as $p) {
    $user = $DB->get_record('user', array('id' => $p->userid));
    echo "  [{$p->id}] {$user->username}: " . substr(strip_tags($p->message), 0, 70) . "...\n";
}

echo "\nUsuarios matriculados:\n";
$enrolled = $DB->get_records_sql(
    "SELECT u.username, r.shortname as role FROM {user} u
     JOIN {role_assignments} ra ON ra.userid = u.id
     JOIN {role} r ON r.id = ra.roleid
     JOIN {context} ctx ON ctx.id = ra.contextid
     JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = 50
     WHERE c.shortname = 'FORM-PROJ-01' AND u.username IN ('profesor_demo','estudiante_demo')"
);
foreach ($enrolled as $u) {
    echo "  {$u->username} → rol: {$u->role}\n";
}
