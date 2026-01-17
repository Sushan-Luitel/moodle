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
// Group logs by day
// --------------------------------------------------
$days = [];

foreach ($logs as $log) {
    $day = date('Y-m-d', $log->logintime);

    if (!isset($days[$day])) {
        $days[$day] = [
            'loggedin' => false,
            'active'   => false
        ];
    }

    $days[$day]['loggedin'] = true;

    $isactive =
        ($log->notes === 'opened' && $log->notes_time > 120) ||
        ($log->videos === 'opened' && $log->video_time > 120) ||
        $log->assignment === 'submitted' ||
        $log->quiz === 'submitted';

    if ($isactive) {
        $days[$day]['active'] = true;
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
    } elseif ($days[$date]['active']) {
        $activeDates[] = $date;
    } else {
        $inactiveDates[] = $date;
    }
}

$activecount   = count($activeDates);
$inactivecount = count($inactiveDates);
$nologincount  = count($nologinDates);

// --------------------------------------------------
// Pie Chart (no color forcing)
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

// Active
echo html_writer::start_tag('tr', ['style'=>'background:#e6ffe6;']);
echo html_writer::tag('td', 'Active Days');
echo html_writer::start_tag('td');
foreach ($activeDates as $d) {
    $url = new moodle_url('/local/consistencyscore/day_detail.php', [
        'userid'=>$userid,
        'date'=>$d
    ]);
    echo html_writer::link($url, $d) . ' ';
}
echo html_writer::end_tag('td');
echo html_writer::end_tag('tr');

// Inactive
echo html_writer::start_tag('tr', ['style'=>'background:#fff6cc;']);
echo html_writer::tag('td', 'Inactive Days');
echo html_writer::start_tag('td');
foreach ($inactiveDates as $d) {
    $url = new moodle_url('/local/consistencyscore/day_detail.php', [
        'userid'=>$userid,
        'date'=>$d
    ]);
    echo html_writer::link($url, $d) . ' ';
}
echo html_writer::end_tag('td');
echo html_writer::end_tag('tr');

// No login
echo html_writer::start_tag('tr', ['style'=>'background:#ffe6e6;']);
echo html_writer::tag('td', 'Not Logged-in Days');
echo html_writer::tag('td', implode(', ', $nologinDates));
echo html_writer::end_tag('tr');

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
