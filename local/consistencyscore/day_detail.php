<?php
require_once(__DIR__.'/../../config.php');
require_login();

use core\chart_line;
use core\chart_series;

global $DB, $PAGE, $OUTPUT, $USER;

$userid = required_param('userid', PARAM_INT);
$date   = required_param('date', PARAM_TEXT);

// Security check
if ($USER->id != $userid && !is_siteadmin()) {
    throw new moodle_exception('accessdenied');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/day_detail.php', [
    'userid' => $userid,
    'date'   => $date
]));
$PAGE->set_title('Daily Activity');
$PAGE->set_heading('Daily Activity Details');

$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Day start/end
$daystart = strtotime($date . ' 00:00:00');
$dayend   = strtotime($date . ' 23:59:59');

// Fetch logs
$sql = "SELECT *
        FROM {local_consistency_log}
        WHERE userid = ?
          AND logintime BETWEEN ? AND ?
        ORDER BY logintime ASC";
$logs = $DB->get_records_sql($sql, [$userid, $daystart, $dayend]);

// -------------------------
// Determine dynamic x-axis range
// -------------------------
$firstlogin = PHP_INT_MAX;
$lastlogout = 0;

foreach ($logs as $log) {
    if (!empty($log->logintime) && $log->logintime < $firstlogin) {
        $firstlogin = $log->logintime;
    }
    if (!empty($log->logouttime) && $log->logouttime > $lastlogout) {
        $lastlogout = $log->logouttime;
    } elseif (!empty($log->logintime) && $log->logintime > $lastlogout) {
        $lastlogout = $log->logintime;
    }
}

// Optional padding 5 min
$chartstart = max($daystart, $firstlogin - 5*60);
$chartend   = min($dayend, $lastlogout + 5*60);

$totalMinutes = floor(($chartend - $chartstart) / 60);
$interval = 5; // 5-min intervals

// -------------------------
// Prepare chart arrays (minutes)
// -------------------------
$labels = [];
$notesSeries = [];
$videoSeries = [];
$assignmentSeries = [];
$quizSeries = [];

for ($i = 0; $i <= $totalMinutes; $i += $interval) {
    $time = $chartstart + $i * 60;
    $labels[] = date('H:i', $time);
    $notesSeries[] = 0;
    $videoSeries[] = 0;
    $assignmentSeries[] = null;
    $quizSeries[] = null;
}

// Fill series data
foreach ($logs as $log) {
    $start = max($log->logintime, $chartstart);
    $end   = !empty($log->logouttime) ? min($log->logouttime, $chartend) : $start;

    $startIndex = floor(($start - $chartstart) / 60 / $interval);
    $endIndex   = floor(($end - $chartstart) / 60 / $interval);

    for ($i = $startIndex; $i <= $endIndex; $i++) {
        if ($log->notes === 'opened' && !empty($log->notes_time)) {
            $notesSeries[$i] = round($log->notes_time / 60, 2); // convert to minutes
        }
        if ($log->videos === 'opened' && !empty($log->video_time)) {
            $videoSeries[$i] = round($log->video_time / 60, 2); // convert to minutes
        }
        if ($log->assignment === 'submitted') {
            $assignmentSeries[$i] = 1;
        }
        if ($log->quiz === 'submitted') {
            $quizSeries[$i] = 1;
        }
    }
}

// -------------------------
// Chart
// -------------------------
$chart = new chart_line();
$chart->set_title('Activity Timeline (Login → Logout)');
$chart->set_labels($labels);

$chart->add_series(new chart_series('Notes Time (min)', $notesSeries));
$chart->add_series(new chart_series('Video Time (min)', $videoSeries));
$chart->add_series(new chart_series('Assignment Submitted', $assignmentSeries));
$chart->add_series(new chart_series('Quiz Submitted', $quizSeries));

// -------------------------
// Prepare row colors (active = alternating green, inactive = red)
// -------------------------
$activedayshade = [];
$shadeindex = 0;
$greenshades = ['#e6ffe6', '#ccffcc']; // alternating shades
$rowshades = [];

foreach ($logs as $log) {
    $isactive =
        ($log->notes === 'opened' && $log->notes_time > 120) || // >2 min in seconds
        ($log->videos === 'opened' && $log->video_time > 120) ||
        $log->assignment === 'submitted' ||
        $log->quiz === 'submitted';

    $day = date('Y-m-d', $log->logintime);

    if ($isactive) {
        if (!isset($activedayshade[$day])) {
            $activedayshade[$day] = $greenshades[$shadeindex % count($greenshades)];
            $shadeindex++;
        }
        $rowshades[] = $activedayshade[$day];
    } else {
        $rowshades[] = '#ffe6e6'; // red for inactive
    }
}

// -------------------------
// Render
// -------------------------
echo $OUTPUT->header();
echo html_writer::tag('h3', fullname($user) . ' — ' . $date);

// Render chart
echo $OUTPUT->render($chart);

// -------- FULL DETAILED TABLE --------
echo html_writer::tag('h4', 'Detailed Activity Log');

echo html_writer::start_tag('table', ['class' => 'generaltable']);

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
$columns = ['Login Time', 'Logout Time', 'Notes Time (min)', 'Video Time (min)', 'Assignment', 'Quiz'];
foreach ($columns as $col) {
    echo html_writer::tag('th', $col);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');

$idx = 0;
foreach ($logs as $log) {
    $rowcolor = $rowshades[$idx];

    echo html_writer::start_tag('tr', ['style'=>"background-color:$rowcolor;"]);

    echo html_writer::tag('td', date('H:i:s', $log->logintime));
    echo html_writer::tag('td', $log->logouttime ? date('H:i:s', $log->logouttime) : '-');

    // Convert seconds to minutes for table
    $notesMinutes = !empty($log->notes_time) ? round($log->notes_time / 60, 2) : '-';
    $videoMinutes = !empty($log->video_time) ? round($log->video_time / 60, 2) : '-';

    echo html_writer::tag('td', $notesMinutes);
    echo html_writer::tag('td', $videoMinutes);
    echo html_writer::tag('td', $log->assignment ?? '-');
    echo html_writer::tag('td', $log->quiz ?? '-');

    echo html_writer::end_tag('tr');
    $idx++;
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
