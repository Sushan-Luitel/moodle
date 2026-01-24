<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

global $DB, $USER;

// Start of today (server time)
$startofday = strtotime('today', time());
$endofday   = $startofday + 86400;

$sql = "
    SELECT COALESCE(SUM(notes_time), 0)
      FROM {local_consistency_log}
     WHERE userid = ?
       AND logintime >= ?
       AND logintime < ?
";

$total = $DB->get_field_sql($sql, [
    $USER->id,
    $startofday,
    $endofday
]);

echo json_encode([
    'seconds' => (int)$total
]);

exit;
