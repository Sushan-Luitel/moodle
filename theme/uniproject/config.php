<?php
// theme/uniproject/config.php - MINIMAL ERROR-FREE
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'uniproject';
$THEME->doctype = 'html5';
$THEME->parents = array('boost');
$THEME->sheets = array();
$THEME->editor_sheets = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// MINIMAL layouts - ONLY what you need
$THEME->layouts = array(
    'login' => array(
        'file' => 'login.php',
        'regions' => array(),
        'defaultregion' => '',
    ),
    'standard' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
);
?>