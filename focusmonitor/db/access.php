<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/focusmonitor:viewreports' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW
        ]
    ]
];
