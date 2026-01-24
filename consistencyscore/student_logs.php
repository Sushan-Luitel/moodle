<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER, $OUTPUT, $PAGE;

$userid = required_param('userid', PARAM_INT);

// Access control
if (!is_siteadmin() && $USER->id != $userid) {
    throw new moodle_exception('erroraccessdenied', 'local_consistencyscore');
}

// User
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

// ------------------------------------
// 1️⃣ BUILD DAY-LEVEL DATA
// ------------------------------------
$daydata = [];

foreach ($logs as $log) {

    if (empty($log->logintime)) {
        continue;
    }

    $day = date('Y-m-d', $log->logintime);

    if (!isset($daydata[$day])) {
        $daydata[$day] = [
            'notes_time' => 0,
            'video_time' => 0,
            'assignment' => false,
            'quiz_properly_submitted' => false,
            'quiz_auto_submitted' => false
        ];
    }

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

// ------------------------------------
// 2️⃣ DETERMINE ACTIVE DAYS + BASE SHADES
// ------------------------------------
$greenshades = ['#e6ffe6', '#ccffcc'];   // alternating day shades
$submissiongreen = '#99e699';            // submission row shade
$inactivecolor = '#ffe6e6';              // inactive day color
$violationred = '#ffcccc';               // auto-submitted row color

$shadeindex = 0;
$daycolor = [];

foreach ($daydata as $day => $info) {

    // Check if day qualifies as active (same logic as index.php)
    $isactiveday = false;

    // If there's a properly submitted quiz, it counts as active
    if ($info['quiz_properly_submitted']) {
        $isactiveday = true;
    }
    // If only auto-submitted quizzes (no proper submission), check other criteria
    else if (
        $info['notes_time'] > 120 ||
        $info['video_time'] > 120 ||
        $info['assignment']
    ) {
        $isactiveday = true;
    }

    if ($isactiveday) {
        $daycolor[$day] = $greenshades[$shadeindex % count($greenshades)];
        $shadeindex++;
    } else {
        $daycolor[$day] = $inactivecolor;
    }
}

// ------------------------------------
// RENDER
// ------------------------------------
echo $OUTPUT->header();

echo html_writer::start_tag('table', ['class' => 'generaltable']);

// Header
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

// Body
echo html_writer::start_tag('tbody');

foreach ($logs as $log) {

    $day = !empty($log->logintime)
        ? date('Y-m-d', $log->logintime)
        : null;

    // Check if this specific log entry is an auto-submitted quiz
    $isAutoSubmitted = false;
    if ($log->quiz === 'submitted' && isset($log->auto_submitted) && $log->auto_submitted == 1) {
        $isAutoSubmitted = true;
    }

    // Default row color = day color
    $rowcolor = ($day && isset($daycolor[$day]))
        ? $daycolor[$day]
        : '';

    // OVERRIDE: Properly submitted quiz/assignment gets submission green (but not auto-submitted)
    if (
        ($log->assignment === 'submitted' || ($log->quiz === 'submitted' && !$isAutoSubmitted)) &&
        $day && isset($daycolor[$day]) &&
        $daycolor[$day] !== $inactivecolor
    ) {
        $rowcolor = $submissiongreen;
    }

    $rowstyle = $rowcolor ? 'background-color:' . $rowcolor . ';' : '';

    echo html_writer::start_tag('tr', ['style' => $rowstyle]);

    echo html_writer::tag('td', $log->notes ?? 'NULL');
    echo html_writer::tag(
        'td',
        !empty($log->notes_time) ? round($log->notes_time / 60, 2) : '0'
    );

    // Quiz column - handle auto-submitted specially
    if (($log->quiz === 'submitted' || $log->quiz === 'opened') && !empty($log->attemptid)) {
        $url = new moodle_url('/mod/quiz/review.php', [
            'attempt' => $log->attemptid
        ]);

        $quiztext = $log->quiz;
        $linkstyle = 'color:black;font-weight:bold;text-decoration:none;';
        
        // If auto-submitted, make the text and background red
        if ($isAutoSubmitted) {
            $linkstyle = 'color:red;font-weight:bold;text-decoration:none;';
            $quiztext = html_writer::tag('span', $log->quiz, [
                'title' => 'Auto-submitted due to focus violations'
            ]);
        }

        echo html_writer::tag(
            'td',
            html_writer::link($url, $quiztext, ['style' => $linkstyle])
        );
    } else {
        echo html_writer::tag('td', $log->quiz ?? 'NULL');
    }

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