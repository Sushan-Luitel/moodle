<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER, $OUTPUT, $PAGE;

// Get userid from URL
$userid = required_param('userid', PARAM_INT);

// Access control
if (!is_siteadmin() && $USER->id != $userid) {
    throw new moodle_exception('erroraccessdenied', 'local_consistencyscore');
}

// Get student record
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

// Page setup
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/student_logs.php', ['userid' => $userid]));
$PAGE->set_title('Student Logs - ' . fullname($user));
$PAGE->set_heading('Student Logs - ' . fullname($user));

// Fetch logs
$logs = $DB->get_records(
    'local_consistency_log',
    ['userid' => $userid],
    'logintime ASC'
);

$activedayshade = [];
$shadeindex = 0;

$greenshades = [
    '#e6ffe6', // light green
    '#ccffcc'  // slightly darker green
];

foreach ($logs as $log) {

    $isactiverow =
        (
            $log->notes === 'opened' &&
            $log->notes_time > 120
        ) ||
        (
            $log->videos === 'opened' &&
            $log->video_time > 120
        ) ||
        $log->quiz === 'submitted' ||
        $log->assignment === 'submitted';

    if ($isactiverow && !empty($log->logintime)) {

        $day = date('Y-m-d', $log->logintime);

        if (!isset($activedayshade[$day])) {
            $activedayshade[$day] = $greenshades[$shadeindex % count($greenshades)];
            $shadeindex++;
        }
    }
}

// Render
echo $OUTPUT->header();

echo html_writer::start_tag('table', ['class' => 'generaltable']);

// ---------- TABLE HEADER ----------
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');

echo html_writer::tag('th', 'Notes');
echo html_writer::tag('th', 'Notes Time (min)');
echo html_writer::tag('th', 'Quiz');
echo html_writer::tag('th', 'Assignment');
echo html_writer::tag('th', 'Videos');
echo html_writer::tag('th', 'Videos Time (min)');
echo html_writer::tag('th', 'Login Time');
echo html_writer::tag('th', 'Logout Time');

echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

// ---------- TABLE BODY ----------
echo html_writer::start_tag('tbody');

foreach ($logs as $log) {

    $isactiverow =
        (
            $log->notes === 'opened' &&
            $log->notes_time > 120
        ) ||
        (
            $log->videos === 'opened' &&
            $log->video_time > 120
        ) ||
        $log->quiz === 'submitted' ||
        $log->assignment === 'submitted';

    if ($isactiverow && !empty($log->logintime)) {
        $day = date('Y-m-d', $log->logintime);
        $rowstyle = 'background-color:' . $activedayshade[$day] . ';';
    } else {
        $rowstyle = 'background-color:#ffe6e6;'; // red for inactive rows
    }

    echo html_writer::start_tag('tr', ['style' => $rowstyle]);

    echo html_writer::tag('td', $log->notes ?? 'NULL');
    echo html_writer::tag(
        'td',
        !empty($log->notes_time) ? round($log->notes_time / 60, 2) : '0'
    );

    echo html_writer::tag('td', $log->quiz ?? 'NULL');
    echo html_writer::tag('td', $log->assignment ?? 'NULL');

    echo html_writer::tag('td', $log->videos ?? 'NULL');
    echo html_writer::tag(
        'td',
        !empty($log->video_time) ? round($log->video_time / 60, 2) : '0'
    );

    echo html_writer::tag(
        'td',
        !empty($log->logintime)
            ? date('Y-m-d H:i:s', $log->logintime)
            : 'NULL'
    );

    echo html_writer::tag(
        'td',
        !empty($log->logouttime)
            ? date('Y-m-d H:i:s', $log->logouttime)
            : 'NULL'
    );

    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
