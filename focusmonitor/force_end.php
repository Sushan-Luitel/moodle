<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

require_login();

$attemptid = required_param('attemptid', PARAM_INT);

global $DB, $USER, $SESSION;

// Get attempt record
$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);

// Verify ownership
if ($attempt->userid != $USER->id) {
    throw new moodle_exception('notyourattempt', 'quiz');
}

// Check if already finished
if ($attempt->state == 'finished') {
    $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $quiz->id);
    redirect(new moodle_url('/mod/quiz/review.php', ['attempt' => $attemptid]), 
        get_string('attemptalreadyclosed', 'quiz'), 
        null, 
        \core\output\notification::NOTIFY_WARNING
    );
}

// Get quiz and course module
$quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $quiz->id);
$context = context_module::instance($cm->id);

// Start transaction
$transaction = $DB->start_delegated_transaction();

try {
    // Calculate the sum of all question grades for this attempt
    $sql = "SELECT COALESCE(SUM(fraction * maxmark), 0) as sumgrades
            FROM {question_attempts} qa
            JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
            WHERE qa.questionusageid = :qubaid
            AND qas.sequencenumber = (
                SELECT MAX(sequencenumber) 
                FROM {question_attempt_steps} 
                WHERE questionattemptid = qa.id
            )";
    
    $sumgrades = $DB->get_field_sql($sql, ['qubaid' => $attempt->uniqueid]);
    
    // Update attempt with final state
    $timenow = time();
    $attempt->state = 'finished';
    $attempt->timefinish = $timenow;
    $attempt->timemodified = $timenow;
    $attempt->sumgrades = $sumgrades;
    
    $DB->update_record('quiz_attempts', $attempt);
    
    // Update gradebook
    quiz_update_grades($quiz, $USER->id);
    
    // Mark in session that this was force-submitted
    $SESSION->focusmonitor_forced = true;
    
    // IMPORTANT: Mark as auto-submitted in consistency log BEFORE redirect
    $log = $DB->get_record('local_consistency_log', [
        'userid' => $USER->id,
        'attemptid' => $attemptid
    ]);

    if ($log) {
        $log->auto_submitted = 1;
        $log->last_warning_time = time();
        $DB->update_record('local_consistency_log', $log);
    } else {
        // Create new record if doesn't exist
        $log = new stdClass();
        $log->userid = $USER->id;
        $log->attemptid = $attemptid;
        $log->face_detection_warning = 0;
        $log->tab_switch_warning = 3; // Force submitted means 3+ warnings
        $log->auto_submitted = 1;
        $log->last_warning_time = time();
        $DB->insert_record('local_consistency_log', $log);
    }
    
    // Log event with required submitterid
    $params = array(
        'objectid' => $attemptid,
        'relateduserid' => $USER->id,
        'courseid' => $quiz->course,
        'context' => $context,
        'other' => array(
            'quizid' => $quiz->id,
            'submitterid' => $USER->id,
            'reason' => 'focus_violations'
        )
    );
    
    $event = \mod_quiz\event\attempt_submitted::create($params);
    $event->trigger();
    
    $transaction->allow_commit();
    
} catch (Exception $e) {
    $transaction->rollback($e);
    throw new moodle_exception('errorprocessingresponses', 'quiz', '', $e->getMessage());
}

// Redirect to review page (THIS MUST BE LAST)
redirect(new moodle_url('/mod/quiz/review.php', ['attempt' => $attemptid]), 
    'Your quiz attempt has been submitted due to focus violations.', 
    null, 
    \core\output\notification::NOTIFY_WARNING
);