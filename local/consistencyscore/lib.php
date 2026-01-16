<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add link on front page
 */
function local_consistencyscore_extend_navigation_frontpage($frontpage) {
    $frontpage->add(
        'Consistency Score',
        new moodle_url('/local/consistencyscore/index.php')
    );
}
function local_consistencyscore_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;

    if ($PAGE->cm && in_array($PAGE->cm->modname, ['page', 'book'])) {
        $PAGE->requires->js('/local/consistencyscore/js/notestimer.js');
    }
}
