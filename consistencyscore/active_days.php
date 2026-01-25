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
            'quiz_properly_submitted' => false,
            'quiz_auto_submitted' => false
        ];
    }

    // Sum time (seconds)
    $daydata[$day]['notes_time'] += (int)$log->notes_time;
    $daydata[$day]['video_time'] += (int)$log->video_time;

    if ($log->assignment === 'submitted') {
        $daydata[$day]['assignment'] = true;
    }

    if ($log->quiz === 'submitted') {
        // Check if it was auto-submitted (violation)
        if (isset($log->auto_submitted) && $log->auto_submitted == 1) {
            $daydata[$day]['quiz_auto_submitted'] = true;
        } else {
            // Properly submitted quiz
            $daydata[$day]['quiz_properly_submitted'] = true;
        }
    }
}

/* =====================================================
 * 2️⃣ DETERMINE ACTIVE / INACTIVE DAYS
 * ===================================================== */
$days = [];

foreach ($daydata as $day => $info) {

    // Check if day qualifies as active (same logic as index.php)
    $isactive = false;

    // If there's a properly submitted quiz, it counts as active
    if ($info['quiz_properly_submitted']) {
        $isactive = true;
    }
    // If only auto-submitted quizzes (no proper submission), check other criteria
    else if (
        $info['notes_time'] > 120 ||
        $info['video_time'] > 120 ||
        $info['assignment']
    ) {
        $isactive = true;
    }
    // If ONLY auto-submitted quiz(es) and nothing else, NOT active

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