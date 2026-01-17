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
    $firstloginday = null;

    foreach ($logs as $log) {

        // ✅ ACTIVE DAY RULE (unchanged)
        $hasactivity =
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

        if (!empty($log->logintime)) {

            // Track FIRST LOGIN DAY
            if ($firstloginday === null || $log->logintime < $firstloginday) {
                $firstloginday = $log->logintime;
            }

            // Track ACTIVE DAYS
            if ($hasactivity) {
                $day = gmdate('Y-m-d', $log->logintime);
                $activeDays[$day] = true;
            }
        }
    }

    $activecount = count($activeDays);

    if ($firstloginday !== null) {

        $firstday_midnight = strtotime(gmdate('Y-m-d', $firstloginday));
        $today_midnight    = strtotime(gmdate('Y-m-d'));

        $totaldays = (($today_midnight - $firstday_midnight) / 86400) + 1;
        $totaldays = max(1, (int)$totaldays);

        // ✅ CORRECT CONSISTENCY FORMULA
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

    $daysurl = new moodle_url('/local/consistencyscore/active_days.php', [
        'userid' => $row->userid
    ]);

    echo html_writer::tag(
        'td',
        html_writer::link($daysurl, $row->active_days, ['style' => 'font-weight:bold;'])
    );

    $scoreurl = new moodle_url('/local/consistencyscore/consistency_pie.php', ['userid' => $row->userid]);
echo html_writer::tag('td', html_writer::link($scoreurl, $row->score . '%', ['style' => 'font-weight:bold;']));


    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
