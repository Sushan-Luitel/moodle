<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    // Hook into quiz review page render
    [
        'eventname'   => '\mod_quiz\event\attempt_reviewed',
        'callback'    => 'local_focusmonitor_extend_quiz_review_page',
        'internal'    => false,
        'priority'    => 1000,
    ],
];
