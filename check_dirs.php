<?php
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');
echo "dataroot: " . $CFG->dataroot . "\n";
echo "localcachedir: " . $CFG->localcachedir . "\n";
echo "lang dir writable: " . (is_writable($CFG->dataroot . '/lang') ? 'yes' : 'no') . "\n";
echo "localcache writable: " . (is_writable($CFG->localcachedir) ? 'yes' : 'no') . "\n";
echo "Plugin lang en exists: " . (file_exists('/bitnami/moodle/local/leotask/lang/en/local_leotask.php') ? 'yes' : 'no') . "\n";
echo "Plugin files:\n";
system("ls -la /bitnami/moodle/local/leotask/");
