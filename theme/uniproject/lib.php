<?php
defined('MOODLE_INTERNAL') || die();

function theme_uniproject_get_main_scss_content($theme) {
    global $CFG;
    
    $scss = '';
    
    // First, get parent theme SCSS (Boost)
    if (file_exists($CFG->dirroot . '/theme/boost/scss/preset/default.scss')) {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }
    
    // Then add your custom SCSS
    if (file_exists($CFG->dirroot . '/theme/uniproject/scss/custom.scss')) {
        $scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/uniproject/scss/custom.scss');
    }
    
    return $scss;
}

// Optional: Add this function to ensure proper initialization
function theme_uniproject_page_init(moodle_page $page) {
    $page->add_body_class('pagelayout-' . $page->pagelayout);
}
?>