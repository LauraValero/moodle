<?php
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

$changes = array(
    'profesor_demo' => array(
        'username' => 'profesor',
        'email'    => 'profesor@local.dev',
        'password' => 'Profesor1234!',
    ),
    'estudiante_demo' => array(
        'username' => 'estudiante',
        'email'    => 'estudiante@local.dev',
        'password' => 'Estudiante1234!',
    ),
);

foreach ($changes as $old_username => $data) {
    $user = $DB->get_record('user', array('username' => $old_username));
    if (!$user) {
        // Intentar por si ya fue renombrado
        $user = $DB->get_record('user', array('username' => $data['username']));
        if ($user) {
            echo "Ya actualizado: {$data['username']}\n";
            continue;
        }
        echo "ERROR: no se encontró $old_username\n";
        continue;
    }
    $DB->set_field('user', 'username', $data['username'], array('id' => $user->id));
    $DB->set_field('user', 'email',    $data['email'],    array('id' => $user->id));
    $DB->set_field('user', 'password', hash_internal_user_password($data['password']), array('id' => $user->id));
    echo "Actualizado: $old_username -> {$data['username']} / {$data['email']}\n";
}

echo "\n--- Estado final ---\n";
foreach (array('admin', 'profesor', 'estudiante') as $u) {
    $rec = $DB->get_record('user', array('username' => $u));
    if ($rec) echo "  $u | {$rec->firstname} {$rec->lastname} | {$rec->email}\n";
}
echo "LISTO\n";
