<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$userid = required_param('userid', PARAM_INT);

// Security check
if ($USER->id != $userid && !is_siteadmin()) {
    throw new moodle_exception('accessdenied');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/active_days.php', ['userid' => $userid]));
$PAGE->set_title('Active Days');
$PAGE->set_heading('Login Activity');

$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Fetch logs
$logs = $DB->get_records(
    'local_consistency_log',
    ['userid' => $userid],
    'logintime ASC'
);

// Prepare day-wise activity
$days = [];

foreach ($logs as $log) {

    if (empty($log->logintime)) {
        continue;
    }

    $date = gmdate('Y-m-d', $log->logintime);

    // ✅ UPDATED ACTIVE DAY RULE (ONLY CHANGE)
    $isactive =
        (
            $log->notes === 'opened' &&
            $log->notes_time > 120
        ) ||
        (
            $log->videos === 'opened' &&
            $log->video_time > 120
        ) ||
        $log->assignment === 'submitted' ||
        $log->quiz === 'submitted';

    if (!isset($days[$date])) {
        $days[$date] = false;
    }

    if ($isactive) {
        $days[$date] = true;
    }
}

// Render
echo $OUTPUT->header();

echo html_writer::tag('h3', fullname($user));

// Table
echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::tag('th', 'Date');
echo html_writer::tag('th', 'Status');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');

foreach ($days as $date => $active) {

    $color  = $active ? 'green' : 'red';
    $status = $active ? 'Active' : 'Inactive';

    echo html_writer::start_tag('tr');

    $dateurl = new moodle_url('/local/consistencyscore/day_detail.php', [
        'userid' => $userid,
        'date'   => $date
    ]);

    echo html_writer::tag(
        'td',
        html_writer::link($dateurl, $date),
        ['style' => "color:$color;font-weight:bold;"]
    );

    echo html_writer::tag(
        'td',
        $status,
        ['style' => "color:$color; font-weight:bold;"]
    );

    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
