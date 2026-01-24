<?php
defined('MOODLE_INTERNAL') || die();

function local_focusmonitor_extend_attempt_review_page($attemptobj, $context) {
    global $OUTPUT;
    if (!has_capability('local/focusmonitor:viewreports', $context)) {
        return;
    }
    $attempt = $attemptobj->get_attempt();
    $attemptid = $attempt->id;
    $url = new moodle_url('/local/focusmonitor/attempt_report.php', ['attempt' => $attemptid]);
    echo html_writer::div(
        html_writer::link($url, '📹 Focus Monitor Report', ['class' => 'btn btn-secondary']),
        'mt-3'
    );
}

function local_focusmonitor_before_standard_html_head() {
    global $PAGE, $DB, $USER;
    if ($PAGE->pagetype === 'mod-quiz-attempt') {
        $PAGE->requires->css('/local/focusmonitor/styles.css');
        $PAGE->requires->js('/local/focusmonitor/js/monitor.js', false);
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if ($attemptid) {
            if ($DB->record_exists('local_consistency_log', ['attemptid' => $attemptid])) {
                debugging('Focus monitor: attempt already linked ' . $attemptid, DEBUG_DEVELOPER);
                return;
            }
            $log = $DB->get_record_sql(
                "SELECT * FROM {local_consistency_log} WHERE userid = ? AND attemptid IS NULL ORDER BY id DESC",
                [$USER->id],
                IGNORE_MULTIPLE
            );
            if ($log) {
                $log->attemptid = $attemptid;
                $log->face_detection_warning = $log->face_detection_warning ?? 0;
                $log->tab_switch_warning     = $log->tab_switch_warning ?? 0;
                $log->auto_submitted         = $log->auto_submitted ?? 0;
                $log->last_warning_time      = $log->last_warning_time ?? 0;
                $DB->update_record('local_consistency_log', $log);
                debugging('Focus monitor: updated record ' . $log->id, DEBUG_DEVELOPER);
            } else {
                $log = (object)[
                    'userid'                 => $USER->id,
                    'attemptid'              => $attemptid,
                    'logintime'              => time(),
                    'last_warning_time'      => 0,
                    'face_detection_warning' => 0,
                    'tab_switch_warning'     => 0,
                    'auto_submitted'         => 0,
                    'notes'                  => '',
                    'quiz'                   => 'opened',
                    'assignment'             => '',
                    'videos'                 => '',
                    'logouttime'             => null,
                    'notes_time'             => 0,
                    'video_time'             => 0
                ];
                $DB->insert_record('local_consistency_log', $log);
                debugging('Focus monitor: created new record for attempt ' . $attemptid, DEBUG_DEVELOPER);
            }
        }
    }
    if ($PAGE->pagetype === 'mod-quiz-review') {
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if ($attemptid) {
            $context = $PAGE->context;
            
            // Allow both students (viewing their own) and teachers to see the button
            $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);
            $canView = false;
            
            if ($attempt) {
                // Student can view their own attempt OR user has viewreports capability
                $canView = ($attempt->userid == $USER->id) || 
                           has_capability('local/focusmonitor:viewreports', $context);
            }
            
            if (!$canView) {
                return;
            }
            
            $PAGE->requires->js_init_code("
                window.addEventListener('load', function() {
                    // Find the region-main div or the main content area
                    const mainContent = document.querySelector('#region-main') || document.querySelector('.region-main');
                    if (!mainContent) return;
                    
                    // Create a container div for the button
                    const buttonContainer = document.createElement('div');
                    buttonContainer.className = 'focus-monitor-button-container';
                    buttonContainer.style.marginTop = '15px';
                    buttonContainer.style.marginBottom = '15px';
                    buttonContainer.style.padding = '15px';
                    buttonContainer.style.backgroundColor = '#f8f9fa';
                    buttonContainer.style.borderRadius = '8px';
                    buttonContainer.style.border = '1px solid #dee2e6';
                    
                    // Create the button/link
                    const link = document.createElement('a');
                    link.href = M.cfg.wwwroot + '/local/focusmonitor/attempt_report.php?attempt={$attemptid}';
                    link.className = 'btn btn-info btn-lg';
                    link.innerHTML = '📹 View Focus Monitor Report';
                    link.style.padding = '12px 24px';
                    link.style.fontSize = '16px';
                    link.style.fontWeight = 'bold';
                    link.style.textDecoration = 'none';
                    link.style.display = 'inline-block';
                    
                    // Add hover effect
                    link.addEventListener('mouseenter', function() {
                        this.style.transform = 'scale(1.05)';
                        this.style.transition = 'transform 0.2s';
                    });
                    link.addEventListener('mouseleave', function() {
                        this.style.transform = 'scale(1)';
                    });
                    
                    // Add the link to the container
                    buttonContainer.appendChild(link);
                    
                    // Insert at the top of the main content area
                    mainContent.insertBefore(buttonContainer, mainContent.firstChild);
                });
            ");
        }
    }
}

function local_focusmonitor_before_footer() {
    global $PAGE, $SESSION;
    // Removed the code that disabled the "Return to attempt" button
    // Now only clears the session variable if it exists
    if (!empty($SESSION->focusmonitor_forced)) {
        unset($SESSION->focusmonitor_forced);
    }
}