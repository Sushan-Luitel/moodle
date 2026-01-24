<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

global $DB, $USER;

$seconds = optional_param('seconds', 0, PARAM_INT);
if ($seconds <= 0) {
    http_response_code(204);
    exit;
}

// Update current open login row
$rec = $DB->get_record_sql(
    "SELECT *
       FROM {local_consistency_log}
      WHERE userid = ?
        AND logouttime IS NULL
   ORDER BY id DESC",
    [$USER->id],
    IGNORE_MISSING
);

if ($rec) {
    $rec->notes_time = (int)$rec->notes_time + $seconds;
    $DB->update_record('local_consistency_log', $rec);
}

http_response_code(204);
exit;
