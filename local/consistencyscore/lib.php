<?php
function local_consistencyscore_extend_navigation_frontpage($frontpage) {
    $frontpage->add(
        'Consistency Score',
        new moodle_url('/local/consistencyscore/index.php')
    );
}
