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
            $notesSeries[$i] = round($log->notes_time / 60, 2);
        }
        if ($log->videos === 'opened' && !empty($log->video_time)) {
            $videoSeries[$i] = round($log->video_time / 60, 2);
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
// Aggregate day-wise for active/inactive
// -------------------------
$daydata = [
    'notes_sum' => 0,
    'video_sum' => 0,
    'assignment' => false,
    'quiz_properly_submitted' => false,
    'quiz_auto_submitted' => false
];

foreach ($logs as $log) {
    $daydata['notes_sum'] += (int)$log->notes_time;
    $daydata['video_sum'] += (int)$log->video_time;
    
    if ($log->assignment === 'submitted') {
        $daydata['assignment'] = true;
    }
    
    if ($log->quiz === 'submitted') {
        // Check if it was auto-submitted (violation)
        if (isset($log->auto_submitted) && $log->auto_submitted == 1) {
            $daydata['quiz_auto_submitted'] = true;
        } else {
            // Properly submitted quiz
            $daydata['quiz_properly_submitted'] = true;
        }
    }
}

// Determine if day is active (same logic as other files)
$isActiveDay = false;

// If there's a properly submitted quiz, it counts as active
if ($daydata['quiz_properly_submitted']) {
    $isActiveDay = true;
}
// If only auto-submitted quizzes (no proper submission), check other criteria
else if (
    $daydata['notes_sum'] > 120 ||
    $daydata['video_sum'] > 120 ||
    $daydata['assignment']
) {
    $isActiveDay = true;
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

// Define colors
$greenshades = ['#e6ffe6', '#ccffcc'];   // alternating day shades
$submissiongreen = '#99e699';            // submission row shade
$inactivecolor = '#ffe6e6';              // inactive day color

// Base color for the day
$dayBaseColor = $isActiveDay ? $greenshades[0] : $inactivecolor;

foreach ($logs as $log) {
    
    // Check if this specific log entry is an auto-submitted quiz
    $isAutoSubmitted = false;
    if ($log->quiz === 'submitted' && isset($log->auto_submitted) && $log->auto_submitted == 1) {
        $isAutoSubmitted = true;
    }
    
    // Default row color = day base color
    $rowcolor = $dayBaseColor;
    
    // OVERRIDE: Properly submitted quiz/assignment gets submission green (but not auto-submitted)
    if (
        ($log->assignment === 'submitted' || ($log->quiz === 'submitted' && !$isAutoSubmitted)) &&
        $isActiveDay
    ) {
        $rowcolor = $submissiongreen;
    }

    echo html_writer::start_tag('tr', ['style'=>"background-color:$rowcolor;"]);

    echo html_writer::tag('td', date('H:i:s', $log->logintime));
    echo html_writer::tag('td', $log->logouttime ? date('H:i:s', $log->logouttime) : '-');

    $notesMinutes = !empty($log->notes_time) ? round($log->notes_time / 60, 2) : '-';
    $videoMinutes = !empty($log->video_time) ? round($log->video_time / 60, 2) : '-';

    echo html_writer::tag('td', $notesMinutes);
    echo html_writer::tag('td', $videoMinutes);
    echo html_writer::tag('td', $log->assignment ?? '-');
    
    // Quiz column - handle auto-submitted specially
    if (($log->quiz === 'submitted' || $log->quiz === 'opened') && !empty($log->attemptid)) {
        $url = new moodle_url('/mod/quiz/review.php', [
            'attempt' => $log->attemptid
        ]);

        $quiztext = $log->quiz;
        $linkstyle = 'color:black;font-weight:bold;text-decoration:none;';
        
        // If auto-submitted, make the text red
        if ($isAutoSubmitted) {
            $linkstyle = 'color:red;font-weight:bold;text-decoration:none;';
            $quiztext = html_writer::tag('span', $log->quiz, [
                'title' => 'Auto-submitted due to focus violations'
            ]);
        }

        echo html_writer::tag(
            'td',
            html_writer::link($url, $quiztext, ['style' => $linkstyle])
        );
    } else {
        echo html_writer::tag('td', $log->quiz ?? '-');
    }

    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();