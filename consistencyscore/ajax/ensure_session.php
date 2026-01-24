<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_login();

global $DB, $USER;

$sql = "
    SELECT id
    FROM {local_consistency_log}
    WHERE userid = :userid
      AND logouttime IS NULL
    ORDER BY logintime DESC
";

$session = $DB->get_record_sql($sql, ['userid' => $USER->id]);

if (!$session) {
    $rec = new stdClass();
    $rec->userid      = $USER->id;
    $rec->logintime   = time();
    $rec->logouttime  = null;
    $rec->notes_time  = 0;
    $rec->videos_time = 0;
    $DB->insert_record('local_consistency_log', $rec);
}
