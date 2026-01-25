<?php
require_once(__DIR__.'/../../config.php');
require_login();

global $DB, $USER, $OUTPUT;

$context = context_system::instance();
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

    $logs = $DB->get_records(
        'local_consistency_log',
        ['userid' => $user->id],
        'logintime ASC'
    );

    $daydata = [];
    $firstloginday = null;

    foreach ($logs as $log) {

        if (empty($log->logintime)) {
            continue;
        }

        $day = gmdate('Y-m-d', $log->logintime);

        // Track first login day
        if ($firstloginday === null || $log->logintime < $firstloginday) {
            $firstloginday = $log->logintime;
        }

        // Init day bucket
        if (!isset($daydata[$day])) {
            $daydata[$day] = [
                'notes_time' => 0,
                'video_time' => 0,
                'assignment' => false,
                'quiz_properly_submitted' => false,  // At least one quiz submitted properly
                'quiz_auto_submitted' => false        // At least one quiz auto-submitted
            ];
        }

        // Sum separately
        $daydata[$day]['notes_time'] += (int)$log->notes_time;
        $daydata[$day]['video_time'] += (int)$log->video_time;

        if ($log->assignment === 'submitted') {
            $daydata[$day]['assignment'] = true;
        }

        // Check if quiz was submitted
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

    // Determine active days
    $activeDays = [];

    foreach ($daydata as $date => $info) {

        // Check if day qualifies as active
        $isActive = false;

        // If there's a properly submitted quiz, it counts as active
        if ($info['quiz_properly_submitted']) {
            $isActive = true;
        }
        // If only auto-submitted quizzes (no proper submission), check other criteria
        else if (
            $info['notes_time'] > 120 ||
            $info['video_time'] > 120 ||
            $info['assignment']
        ) {
            $isActive = true;
        }
        // If ONLY auto-submitted quiz(es) and nothing else, NOT active
        // (this is already handled by the else-if above)

        if ($isActive) {
            $activeDays[$date] = true;
        }
    }

    $activecount = count($activeDays);

    if ($firstloginday !== null) {

        $firstday_midnight = strtotime(gmdate('Y-m-d', $firstloginday));
        $today_midnight    = strtotime(gmdate('Y-m-d'));

        $totaldays = (($today_midnight - $firstday_midnight) / 86400) + 1;
        $totaldays = max(1, (int)$totaldays);

        $consistencyscore = round(($activecount / $totaldays) * 100);

    } else {
        $consistencyscore = 0;
    }

    $data[] = (object)[
        'userid'      => $user->id,
        'photo'       => $OUTPUT->user_picture($user, ['size'=>50]),
        'name'        => fullname($user),
        'active_days' => $activecount,
        'score'       => $consistencyscore
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

    $daysurl = new moodle_url('/local/consistencyscore/active_days.php', ['userid' => $row->userid]);
    echo html_writer::tag('td', html_writer::link($daysurl, $row->active_days, ['style'=>'font-weight:bold;']));

    $scoreurl = new moodle_url('/local/consistencyscore/consistency_pie.php', ['userid' => $row->userid]);
    echo html_writer::tag('td', html_writer::link($scoreurl, $row->score.'%', ['style'=>'font-weight:bold;']));

    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();