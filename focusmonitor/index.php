<?php
require('../../config.php');
require_login();

/** @var \context $context */
$context = \context_system::instance();

require_capability('local/focusmonitor:viewreports', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/focusmonitor/index.php');
$PAGE->set_title('Focus Monitor Reports');
$PAGE->set_heading('Focus Monitor Reports');

$logs = $DB->get_records('local_focusmonitor_log', null, 'timecreated DESC');

echo $OUTPUT->header();

echo html_writer::start_tag('table', ['border' => 1, 'cellpadding' => 6]);
echo html_writer::tag('tr',
    html_writer::tag('th', 'User ID') .
    html_writer::tag('th', 'Event') .
    html_writer::tag('th', 'Time')
);

foreach ($logs as $log) {
    echo html_writer::tag('tr',
        html_writer::tag('td', $log->userid) .
        html_writer::tag('td', $log->eventtype) .
        html_writer::tag('td', userdate($log->timecreated))
    );
}

echo html_writer::end_tag('table');
echo $OUTPUT->footer();
