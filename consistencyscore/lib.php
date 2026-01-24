<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Front page navigation
 */
function local_consistencyscore_extend_navigation_frontpage($frontpage) {
    $frontpage->add(
        'Consistency Score',
        new moodle_url('/local/consistencyscore/index.php')
    );
}
function local_consistencyscore_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;

    if ($PAGE->cm && $PAGE->cm->modname === 'book') {
        $PAGE->requires->js('/local/consistencyscore/js/notestimer.js');
    }
}

function local_consistencyscore_before_standard_html_head() {
    global $PAGE;

    // Only load on Page module view
    if ($PAGE->pagetype !== 'mod-page-view') {
        return;
    }

    $PAGE->requires->js(
        new moodle_url('/local/consistencyscore/js/videotimer.js')
    );
}