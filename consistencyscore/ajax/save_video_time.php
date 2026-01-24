<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $USER;

$seconds = optional_param('seconds', 0, PARAM_INT);
if ($seconds <= 0) die('No seconds to save');

// Get latest session
$sql = "
    SELECT *
    FROM {local_consistency_log}
    WHERE userid = :userid
      AND logouttime IS NULL
    ORDER BY logintime DESC
";
$session = $DB->get_record_sql($sql, ['userid' => $USER->id]);

if (!$session) {
    $session = new stdClass();
    $session->userid      = $USER->id;
    $session->logintime   = time();
    $session->logouttime  = null;
    $session->notes_time  = 0;
    $session->video_time = 0;
    $session->id = $DB->insert_record('local_consistency_log', $session);
}

// Ensure video_time is integer
$session->video_time = (int)$session->video_time + $seconds;

// Update record
$DB->update_record('local_consistency_log', $session);
