<?php
require_once(__DIR__.'/../../config.php');
require_login();

global $DB, $USER, $OUTPUT;

// Get userid from URL
$userid = required_param('userid', PARAM_INT);

// Check access: students can only see their own logs
if (!is_siteadmin() && $USER->id != $userid) {
    throw new moodle_exception('erroraccessdenied', 'local_consistencyscore');
}


// Get the student record
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

// Set page
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/student_logs.php', ['userid' => $userid]));
$PAGE->set_title('Student Logs - ' . fullname($user));
$PAGE->set_heading('Student Logs - ' . fullname($user));

// Fetch all logs for this student
$logs = $DB->get_records('local_consistency_log', ['userid' => $userid], 'logintime ASC');

// Render table
echo $OUTPUT->header();

echo html_writer::start_tag('table', ['class'=>'generaltable']);

// Table header
echo html_writer::start_tag('thead');
echo html_writer::tag('th', 'Notes');
echo html_writer::tag('th', 'Quiz');
echo html_writer::tag('th', 'Assignment');
echo html_writer::tag('th', 'Videos');
echo html_writer::tag('th', 'Login Time');
echo html_writer::tag('th', 'Logout Time');
echo html_writer::end_tag('thead');

// Table body
echo html_writer::start_tag('tbody');

foreach ($logs as $log) {
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $log->notes ?? 'NULL');
    echo html_writer::tag('td', $log->quiz ?? 'NULL');
    echo html_writer::tag('td', $log->assignment ?? 'NULL');
    echo html_writer::tag('td', $log->videos ?? 'NULL');
    echo html_writer::tag('td', !empty($log->logintime) ? date('Y-m-d H:i:s', $log->logintime) : 'NULL');
    echo html_writer::tag('td', !empty($log->logouttime) ? date('Y-m-d H:i:s', $log->logouttime) : 'NULL');
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
