<?php
// theme/uniproject/config.php - FIXED VERSION
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'uniproject';
$THEME->doctype = 'html5';
$THEME->parents = array('boost');
$THEME->sheets = array();
$THEME->editor_sheets = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Enable features from Boost parent
$THEME->enable_dock = false;
$THEME->enable_dashboard = true;

// Essential layouts for basic functionality
$THEME->layouts = array(
    // Most pages use this
    'base' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    
    // Standard layout (used by default)
    'standard' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    
    // Dashboard
    'dashboard' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('nonavbar' => true),
    ),
    
    // Login page
    'login' => array(
        'file' => 'login.php',
        'regions' => array(),
        'defaultregion' => '',
        'options' => array('langmenu' => true),
    ),
    
    // Front page
    'frontpage' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    
    // Course main page
    'course' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    
    // Course category page
    'coursecategory' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    
    // Embedded (for iframes)
    'embedded' => array(
        'file' => 'embedded.php',
        'regions' => array(),
        'defaultregion' => '',
    ),
    
    // Popup
    'popup' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'defaultregion' => '',
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    
    // Redirect (simple message display)
    'redirect' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'defaultregion' => '',
    ),
    
    // Maintenance
    'maintenance' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'defaultregion' => '',
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    
    // Admin pages
    'admin' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    
    // My Public (user profile)
    'mypublic' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
);

// SCSS function
$THEME->scss = function($theme) {
    return theme_uniproject_get_main_scss_content($theme);
};

// Additional theme settings if needed
$THEME->javascripts = array();
$THEME->javascripts_footer = array();
$THEME->supports = array(
    'doctype' => 'html5',
    'search' => true,
);
?>