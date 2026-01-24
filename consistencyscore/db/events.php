<?php
defined('MOODLE_INTERNAL') || die();

$observers = [

    ['eventname' => '\core\event\user_loggedin',
     'callback'  => '\local_consistencyscore\observer::user_loggedin'],

    ['eventname' => '\core\event\user_loggedout',
     'callback'  => '\local_consistencyscore\observer::user_loggedout'],

    ['eventname' => '\core\event\course_module_viewed',
     'callback'  => '\local_consistencyscore\observer::module_viewed'],

    ['eventname' => '\mod_quiz\event\course_module_viewed',
     'callback'  => '\local_consistencyscore\observer::quiz_viewed'],

    ['eventname' => '\mod_quiz\event\attempt_submitted',
     'callback'  => '\local_consistencyscore\observer::quiz_submitted'],

    ['eventname' => '\mod_assign\event\course_module_viewed',
     'callback'  => '\local_consistencyscore\observer::assign_viewed'],

    ['eventname' => '\mod_assign\event\submission_created',
     'callback'  => '\local_consistencyscore\observer::assign_submitted'],

    ['eventname' => '\mod_assign\event\submission_updated',
     'callback'  => '\local_consistencyscore\observer::assign_updated'],

];