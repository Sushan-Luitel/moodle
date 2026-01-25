<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');

require_login();

global $DB, $USER, $OUTPUT, $PAGE;

// Get attempt ID
$attemptid = required_param('attempt', PARAM_INT);

// Get the quiz attempt
$attemptobj = quiz_attempt::create($attemptid);
$attempt = $attemptobj->get_attempt();
$quiz = $attemptobj->get_quiz();
$cm = $attemptobj->get_cm();
$course = $attemptobj->get_course();

// Check permissions
$context = context_module::instance($cm->id);
$canreview = $attemptobj->is_own_attempt() || has_capability('mod/quiz:viewreports', $context);

if (!$canreview) {
    throw new moodle_exception('nopermissiontoview', 'quiz');
}

$PAGE->set_cm($cm, $course);
$PAGE->set_course($course);
$PAGE->set_context($context);

// Set up page
$PAGE->set_url(new moodle_url('/local/focusmonitor/attempt_report.php', ['attempt' => $attemptid]));
$PAGE->set_title('Focus Monitor Report - Attempt #' . $attemptid);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add('Quiz: ' . $quiz->name, new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]));
$PAGE->navbar->add('Review Attempt', new moodle_url('/mod/quiz/review.php', ['attempt' => $attemptid]));
$PAGE->navbar->add('Focus Monitor Report');

// Get monitoring data
$log = $DB->get_record('local_consistency_log', ['attemptid' => $attemptid]);

echo $OUTPUT->header();

echo html_writer::start_div('focus-monitor-report');

// Title and attempt info
echo html_writer::tag('h2', '📹 Focus Monitoring Report');

echo html_writer::start_div('alert alert-info');
echo html_writer::tag('strong', 'Quiz: ') . format_string($quiz->name) . html_writer::empty_tag('br');
echo html_writer::tag('strong', 'Attempt ID: ') . $attemptid . html_writer::empty_tag('br');
echo html_writer::tag('strong', 'Student: ') . fullname($DB->get_record('user', ['id' => $attempt->userid])) . html_writer::empty_tag('br');
echo html_writer::tag('strong', 'Attempt Date: ') . userdate($attempt->timestart) . html_writer::empty_tag('br');
if ($attempt->timefinish > 0) {
    echo html_writer::tag('strong', 'Finished: ') . userdate($attempt->timefinish) . html_writer::empty_tag('br');
}
echo html_writer::end_div();

if (!$log) {
    echo html_writer::div(
        '⚠️ No monitoring data found for this attempt. This may be because:
        <ul>
            <li>Focus monitoring was not enabled when this attempt was taken</li>
            <li>The monitoring log was not created properly</li>
            <li>This is an old attempt from before focus monitoring was installed</li>
        </ul>',
        'alert alert-warning'
    );
} else {
    // Display monitoring statistics
    echo html_writer::tag('h3', 'Monitoring Statistics');
    
    echo html_writer::start_tag('table', ['class' => 'generaltable table table-bordered']);
    
    // Tab Switch Warnings
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '🔄 Tab Switch Warnings', ['style' => 'width: 40%;']);
    $tabclass = $log->tab_switch_warning >= 3 ? 'badge badge-danger' : ($log->tab_switch_warning > 0 ? 'badge badge-warning' : 'badge badge-success');
    echo html_writer::tag('td', 
        html_writer::span($log->tab_switch_warning, $tabclass . ' badge-lg') . 
        ' times',
        ['style' => 'font-size: 16px;']
    );
    echo html_writer::end_tag('tr');
    
    // Face Detection Warnings
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '😶 Face Not Detected Warnings');
    $faceclass = $log->face_detection_warning > 5 ? 'badge badge-danger' : ($log->face_detection_warning > 0 ? 'badge badge-warning' : 'badge badge-success');
    echo html_writer::tag('td', 
        html_writer::span($log->face_detection_warning, $faceclass . ' badge-lg') . 
        ' times (5+ seconds each)',
        ['style' => 'font-size: 16px;']
    );
    echo html_writer::end_tag('tr');
    
    // Auto Submitted
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '⚡ Auto-Submitted');
    if ($log->auto_submitted == 1) {
        echo html_writer::tag('td', 
            html_writer::span('YES - Automatically submitted due to violations', 'badge badge-danger badge-lg'),
            ['style' => 'font-size: 16px;']
        );
    } else {
        echo html_writer::tag('td', 
            html_writer::span('No - Student submitted normally', 'badge badge-success badge-lg'),
            ['style' => 'font-size: 16px;']
        );
    }
    echo html_writer::end_tag('tr');
    
    // Last Warning Time
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '⏰ Last Warning Time');
    if ($log->last_warning_time > 0) {
        echo html_writer::tag('td', userdate($log->last_warning_time), ['style' => 'font-size: 16px;']);
    } else {
        echo html_writer::tag('td', 'No warnings recorded', ['style' => 'font-size: 16px; color: #999;']);
    }
    echo html_writer::end_tag('tr');
    
    echo html_writer::end_tag('table');
    
    // Summary/Interpretation
    echo html_writer::tag('h3', 'Interpretation');
    
    if ($log->auto_submitted == 1) {
        echo html_writer::div(
            '🚨 <strong>This attempt was automatically submitted</strong> because the student switched tabs 3 or more times during the quiz. This is a serious violation of quiz rules.',
            'alert alert-danger'
        );
    } elseif ($log->tab_switch_warning > 0 || $log->face_detection_warning > 0) {
        echo html_writer::div(
            '⚠️ <strong>Warning violations detected.</strong> The student had some focus issues during this attempt but completed it normally.',
            'alert alert-warning'
        );
    } else {
        echo html_writer::div(
            '✅ <strong>No violations detected.</strong> The student maintained focus throughout the entire quiz attempt.',
            'alert alert-success'
        );
    }
    
    // Additional details
    echo html_writer::tag('h4', 'Violation Details');
    echo html_writer::start_tag('ul');
    
    if ($log->tab_switch_warning > 0) {
        echo html_writer::tag('li', 
            "Student switched away from the quiz tab <strong>{$log->tab_switch_warning}</strong> time(s). " .
            "Each tab switch suggests the student may have been looking at other resources or materials."
        );
    }
    
    if ($log->face_detection_warning > 0) {
        echo html_writer::tag('li', 
            "Student's face was not detected for 5+ seconds on <strong>{$log->face_detection_warning}</strong> occasion(s). " .
            "This could mean the student looked away, covered the camera, or left the computer."
        );
    }
    
    if ($log->tab_switch_warning == 0 && $log->face_detection_warning == 0) {
        echo html_writer::tag('li', 
            "No violations were recorded. The student remained focused on the quiz with their face visible throughout the attempt."
        );
    }
    
    echo html_writer::end_tag('ul');
}

// Back button
echo html_writer::div(
    html_writer::link(
        new moodle_url('/mod/quiz/review.php', ['attempt' => $attemptid]),
        '← Back to Review',
        ['class' => 'btn btn-secondary']
    ),
    'mt-3'
);

echo html_writer::end_div();
