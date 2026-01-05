<?php
require_once(__DIR__.'/../../config.php');
require_login();

global $DB, $USER, $OUTPUT;

/** @var \context $context */
$context = \core\context\system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/index.php'));
$PAGE->set_title('Consistency Score');
$PAGE->set_heading('Consistency Score');

// Fetch users
if (is_siteadmin() || has_capability('moodle/course:update', $context)) {

    $sql = "SELECT DISTINCT u.*
            FROM {user} u
            JOIN {local_consistency_log} l ON l.userid = u.id
            WHERE u.deleted = 0
              AND u.suspended = 0
            ORDER BY u.lastname ASC";

    $users = $DB->get_records_sql($sql);

} else {
    $users = [$USER->id => $USER];
}

$data = [];

foreach ($users as $user) {

    $logs = $DB->get_records('local_consistency_log', ['userid' => $user->id], 'logintime ASC');

    $activeDays = [];
    $firstday = null;

    foreach ($logs as $log) {

        if (!empty($log->logintime) &&
            (!is_null($log->notes) || !is_null($log->quiz) || !is_null($log->assignment) || !is_null($log->videos))) {

            $day = gmdate('Y-m-d', $log->logintime);
            $activeDays[$day] = true;

            if ($firstday === null) {
                $firstday = $log->logintime;
            }
        }
    }

    $activecount = count($activeDays);

    if ($firstday !== null) {

        $firstday_midnight = strtotime(gmdate('Y-m-d', $firstday));
        $today_midnight    = strtotime(gmdate('Y-m-d'));

        $totaldays = (($today_midnight - $firstday_midnight) / 86400) + 1;
        $totaldays = max(1, (int)$totaldays);

        $consistencyscore = round(($activecount / $totaldays) * 100);

    } else {
        $consistencyscore = 0;
    }

    $data[] = (object)[
        'userid' => $user->id,
        'photo' => $OUTPUT->user_picture($user, ['size'=>50]),
        'name' => fullname($user),
        'active_days' => $activecount,
        'score' => $consistencyscore
    ];
}

// Render
echo $OUTPUT->header();

echo html_writer::start_tag('table', ['class'=>'generaltable']);

echo html_writer::start_tag('thead');
echo html_writer::tag('th', 'Photo');
echo html_writer::tag('th', 'Name');
echo html_writer::tag('th', 'Logs');
echo html_writer::tag('th', 'Active Days');
echo html_writer::tag('th', 'Consistency Score');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');

foreach ($data as $row) {
    echo html_writer::start_tag('tr');

    echo html_writer::tag('td', $row->photo);

    $profileurl = new moodle_url('/user/profile.php', ['id' => $row->userid]);
    echo html_writer::tag('td', html_writer::link($profileurl, $row->name));

    $logsurl = new moodle_url('/local/consistencyscore/student_logs.php', ['userid' => $row->userid]);
    echo html_writer::tag('td', html_writer::link($logsurl, 'View Logs'));

    echo html_writer::tag('td', $row->active_days);
    echo html_writer::tag('td', $row->score . '%');

    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
