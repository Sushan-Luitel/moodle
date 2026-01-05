<?php
require_once(__DIR__.'/../../config.php');
require_login();

global $DB, $USER, $OUTPUT;

// Page setup
/** @var context $context */
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/consistencyscore/index.php'));
$PAGE->set_title('Consistency Score');
$PAGE->set_heading('Consistency Score');


// Time threshold = last 24 hours
$yesterday = time() - (24 * 60 * 60);

// Fetch users
if (is_siteadmin() || has_capability('moodle/course:update', $context)) {
    // Admin/teachers see all users with activity in last 24 hrs
    $sql = "SELECT DISTINCT u.*
            FROM {user} u
            JOIN {local_consistency_log} l ON l.userid = u.id
            WHERE u.deleted = 0
              AND u.suspended = 0
              AND (l.notes IS NOT NULL OR l.quiz IS NOT NULL OR l.assignment IS NOT NULL OR l.videos IS NOT NULL)
              AND l.logintime >= ?
            ORDER BY u.lastname ASC";
    $users = $DB->get_records_sql($sql, [$yesterday]);
} else {
    // Students see only themselves
    $users = [$USER->id => $USER];
}

$data = [];

foreach ($users as $user) {
    $logs = $DB->get_records('local_consistency_log', ['userid' => $user->id], 'logintime ASC');

    $activeDays = [];
    $firstday = null;

    foreach ($logs as $log) {
        // Only consider rows with logintime
        if (isset($log->logintime) && $log->logintime > 0) {

            // Only consider rows with at least one activity
            if (!is_null($log->notes) || !is_null($log->quiz) || !is_null($log->assignment) || !is_null($log->videos)) {

                // Get day string from logintime
                $day = gmdate('Y-m-d', $log->logintime); // Use gmdate to avoid timezone issues
                $activeDays[$day] = true;

                if ($firstday === null) {
                    $firstday = $log->logintime; // use actual timestamp, not strtotime($day)
                }
            }
        }
    }

    $activecount = count($activeDays);

    if ($firstday !== null) {
        $totaldays = floor((time() - $firstday) / 86400) + 1;
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



// Render table
echo $OUTPUT->header();

echo html_writer::start_tag('table', ['class'=>'generaltable']);

// Table header
echo html_writer::start_tag('thead');
echo html_writer::tag('th', 'Photo');
echo html_writer::tag('th', 'Name');
echo html_writer::tag('th', 'Logs');
echo html_writer::tag('th', 'Active Days ');
echo html_writer::tag('th', 'Consistency Score');
echo html_writer::end_tag('thead');

// Table body
echo html_writer::start_tag('tbody');
foreach ($data as $row) {
    echo html_writer::start_tag('tr');

    // Photo
    echo html_writer::start_tag('td');
    echo $row->photo;
    echo html_writer::end_tag('td');

    // Name (clickable profile)
    $profileurl = new moodle_url('/user/profile.php', ['id' => $row->userid]);
    echo html_writer::tag('td', html_writer::link($profileurl, $row->name));

    // Logs (clickable)
    $logsurl = new moodle_url('/local/consistencyscore/student_logs.php', ['userid' => $row->userid]);
    echo html_writer::tag('td', html_writer::link($logsurl, 'View Logs'));

    // Active Days
    echo html_writer::tag('td', $row->active_days);
    // Consistency Score
    echo html_writer::tag('td', $row->score . '%');
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
