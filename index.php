<?php
// moodle/index.php - Clean redirect (no message)
require_once('config.php');

if (isloggedin() && !isguestuser()) {
    // Logged in → Dashboard (silent redirect)
    header('Location: ' . $CFG->wwwroot . '/my/');
    exit;
} else {
    // Not logged in → Landing page (silent redirect)
    header('Location: ' . $CFG->wwwroot . '/landing.php');
    exit;
}
?>