<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$userid = required_param('userid', PARAM_INT);

// Security check
if ($USER->id != $userid && !is_siteadmin()) {
    throw new moodle_exception('accessdenied');
}

// Page setup
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/active_days.php', [
    'userid' => $userid
]));
$PAGE->set_title('Active Days');
$PAGE->set_heading('Login Activity');

// User
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Fetch logs
$logs = $DB->get_records(
    'local_consistency_log',
    ['userid' => $userid],
    'logintime ASC'
);

/* =====================================================
 * 1️⃣ BUILD DAY-WISE AGGREGATED DATA
 * ===================================================== */
$daydata = [];

foreach ($logs as $log) {

    if (empty($log->logintime)) {
        continue;
    }

    $day = gmdate('Y-m-d', $log->logintime);

    if (!isset($daydata[$day])) {
        $daydata[$day] = [
            'notes_time' => 0,
            'video_time' => 0,
            'assignment' => false,
            'quiz'       => false
        ];
    }

    // Sum time (seconds)
    $daydata[$day]['notes_time'] += (int)$log->notes_time;
    $daydata[$day]['video_time'] += (int)$log->video_time;

    if ($log->assignment === 'submitted') {
        $daydata[$day]['assignment'] = true;
    }

    if ($log->quiz === 'submitted') {
        $daydata[$day]['quiz'] = true;
    }
}

/* =====================================================
 * 2️⃣ DETERMINE ACTIVE / INACTIVE DAYS
 * ===================================================== */
$days = [];

foreach ($daydata as $day => $info) {

    $isactive =
        $info['notes_time'] > 120 ||   // sum of notes_time (seconds)
        $info['video_time'] > 120 ||   // sum of video_time (seconds)
        $info['assignment'] ||
        $info['quiz'];

    $days[$day] = $isactive;
}

/* =====================================================
 * 3️⃣ RENDER
 * ===================================================== */
echo $OUTPUT->header();

echo html_writer::tag('h3', fullname($user));

// Table
echo html_writer::start_tag('table', ['class' => 'generaltable']);

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'Date');
echo html_writer::tag('th', 'Status');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');

foreach ($days as $date => $active) {

    $color  = $active ? 'green' : 'red';
    $status = $active ? 'Active' : 'Inactive';

    $dateurl = new moodle_url('/local/consistencyscore/day_detail.php', [
        'userid' => $userid,
        'date'   => $date
    ]);

    echo html_writer::start_tag('tr');

    // ✅ BOTH ACTIVE & INACTIVE DATES CLICKABLE
    echo html_writer::tag(
        'td',
        html_writer::link($dateurl, $date),
        ['style' => "color:$color;font-weight:bold;"]
    );

    echo html_writer::tag(
        'td',
        $status,
        ['style' => "color:$color;font-weight:bold;"]
    );

    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
