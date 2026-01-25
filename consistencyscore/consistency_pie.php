<?php
require_once(__DIR__ . '/../../config.php');
require_login();

use core\chart_pie;
use core\chart_series;

global $DB, $PAGE, $OUTPUT, $USER;

$userid = required_param('userid', PARAM_INT);

// Security
if ($USER->id != $userid && !is_siteadmin()) {
    throw new moodle_exception('accessdenied');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/consistency_pie.php', [
    'userid' => $userid
]));
$PAGE->set_title('Consistency Breakdown');
$PAGE->set_heading('Consistency Breakdown');

$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// --------------------------------------------------
// Fetch logs
// --------------------------------------------------
$logs = $DB->get_records(
    'local_consistency_log',
    ['userid' => $userid],
    'logintime ASC'
);

if (!$logs) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification('No activity data found.', 'warning');
    echo $OUTPUT->footer();
    exit;
}

// --------------------------------------------------
// Calculate total days from first login
// --------------------------------------------------
$firstlogin = reset($logs)->logintime;
$startday   = strtotime(date('Y-m-d', $firstlogin));
$today      = strtotime(date('Y-m-d'));

$totaldays = (int)((($today - $startday) / 86400) + 1);

// --------------------------------------------------
// Group logs by day and aggregate
// --------------------------------------------------
$days = [];

foreach ($logs as $log) {
    $day = date('Y-m-d', $log->logintime);

    if (!isset($days[$day])) {
        $days[$day] = [
            'notes_sum' => 0,
            'video_sum' => 0,
            'assignment' => false,
            'quiz_properly_submitted' => false,
            'quiz_auto_submitted' => false
        ];
    }

    $days[$day]['notes_sum'] += (int)$log->notes_time;
    $days[$day]['video_sum'] += (int)$log->video_time;
    
    if ($log->assignment === 'submitted') {
        $days[$day]['assignment'] = true;
    }
    
    if ($log->quiz === 'submitted') {
        // Check if it was auto-submitted (violation)
        if (isset($log->auto_submitted) && $log->auto_submitted == 1) {
            $days[$day]['quiz_auto_submitted'] = true;
        } else {
            // Properly submitted quiz
            $days[$day]['quiz_properly_submitted'] = true;
        }
    }
}

// --------------------------------------------------
// Categorize days
// --------------------------------------------------
$activeDates   = [];
$inactiveDates = [];
$nologinDates  = [];

for ($i = 0; $i < $totaldays; $i++) {
    $date = date('Y-m-d', strtotime("+$i day", $startday));

    if (!isset($days[$date])) {
        $nologinDates[] = $date;
    } else {
        $info = $days[$date];
        
        // Determine if day is active (same logic as other files)
        $isActive = false;

        // If there's a properly submitted quiz, it counts as active
        if ($info['quiz_properly_submitted']) {
            $isActive = true;
        }
        // If only auto-submitted quizzes (no proper submission), check other criteria
        else if (
            $info['notes_sum'] > 120 ||
            $info['video_sum'] > 120 ||
            $info['assignment']
        ) {
            $isActive = true;
        }
        
        if ($isActive) {
            $activeDates[] = $date;
        } else {
            $inactiveDates[] = $date;
        }
    }
}

$activecount   = count($activeDates);
$inactivecount = count($inactiveDates);
$nologincount  = count($nologinDates);

// --------------------------------------------------
// Pie Chart
// --------------------------------------------------
$pie = new chart_pie();
$pie->set_title('Consistency Distribution');

$series = new chart_series(
    'Days',
    [$activecount, $inactivecount, $nologincount]
);

$pie->add_series($series);
$pie->set_labels([
    'Active Days',
    'Inactive Days',
    'Not Logged-in Days'
]);

// --------------------------------------------------
// Render
// --------------------------------------------------
echo $OUTPUT->header();

echo html_writer::tag('h3', fullname($user));

// Total days info
echo html_writer::tag(
    'p',
    'Total days : <strong>' . $totaldays . '</strong>',
    ['style' => 'margin-bottom:10px;']
);

// Chart
echo $OUTPUT->render($pie);

// --------------------------------------------------
// Table
// --------------------------------------------------
echo html_writer::tag('h4', 'Day-wise Breakdown');

echo html_writer::start_tag('table', ['class' => 'generaltable']);

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'Type');
echo html_writer::tag('th', 'Dates');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');

// Active Days - Green background
echo html_writer::start_tag('tr', ['style'=>'background:#e6ffe6;']);
echo html_writer::tag('td', 'Active Days');
echo html_writer::start_tag('td');
foreach ($activeDates as $d) {
    $url = new moodle_url('/local/consistencyscore/day_detail.php', [
        'userid'=>$userid,
        'date'=>$d
    ]);
    echo html_writer::link($url, $d, ['style'=>'color:green;font-weight:bold;']) . ' ';
}
echo html_writer::end_tag('td');
echo html_writer::end_tag('tr');

// Inactive Days - Red background
echo html_writer::start_tag('tr', ['style'=>'background:#ffe6e6;']);
echo html_writer::tag('td', 'Inactive Days');
echo html_writer::start_tag('td');
foreach ($inactiveDates as $d) {
    $url = new moodle_url('/local/consistencyscore/day_detail.php', [
        'userid'=>$userid,
        'date'=>$d
    ]);
    echo html_writer::link($url, $d, ['style'=>'color:red;font-weight:bold;']) . ' ';
}
echo html_writer::end_tag('td');
echo html_writer::end_tag('tr');

// No login - Light red/gray background
echo html_writer::start_tag('tr', ['style'=>'background:#f5f5f5;']);
echo html_writer::tag('td', 'Not Logged-in Days');
echo html_writer::tag('td', implode(', ', $nologinDates), ['style'=>'color:#999;']);
echo html_writer::end_tag('tr');

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();