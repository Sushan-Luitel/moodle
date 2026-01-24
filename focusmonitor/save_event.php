<?php
// Enable ALL error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$logfile = __DIR__ . '/debug_save.log';
$timestamp = date('Y-m-d H:i:s');
file_put_contents($logfile, "\n\n=== REQUEST at $timestamp ===\n", FILE_APPEND);

require_once('../../config.php');
require_login();

global $DB, $USER;

// Set JSON header
header('Content-Type: application/json');

file_put_contents($logfile, "User ID: " . $USER->id . "\n", FILE_APPEND);

// Block admins from saving data
if (is_siteadmin()) {
    file_put_contents($logfile, "BLOCKED: Admin user detected\n", FILE_APPEND);
    http_response_code(403);
    echo json_encode(['error' => 'Admin users are not tracked']);
    exit;
}

// Get attempt ID from URL
$attemptid = optional_param('attemptid', 0, PARAM_INT);
file_put_contents($logfile, "Attempt ID from URL: $attemptid\n", FILE_APPEND);

if (!$attemptid) {
    file_put_contents($logfile, "ERROR: No attempt ID provided\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'No attempt ID provided']);
    exit;
}

// Get JSON data
$rawdata = file_get_contents('php://input');
file_put_contents($logfile, "Raw POST data: $rawdata\n", FILE_APPEND);

if (empty($rawdata)) {
    file_put_contents($logfile, "ERROR: Empty POST data\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    exit;
}

$data = json_decode($rawdata, true);

if (!$data || !isset($data['eventtype'])) {
    file_put_contents($logfile, "ERROR: Invalid JSON or missing eventtype\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data format']);
    exit;
}

$eventtype = $data['eventtype'];
$warningcount = isset($data['warningcount']) ? intval($data['warningcount']) : 0;
$duration = isset($data['duration']) ? intval($data['duration']) : 0;

file_put_contents($logfile, "Parsed - Event: $eventtype, Count from JS: $warningcount, Duration: $duration\n", FILE_APPEND);

// Verify attempt exists and belongs to user
$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', IGNORE_MISSING);
if (!$attempt) {
    file_put_contents($logfile, "ERROR: Attempt $attemptid not found\n", FILE_APPEND);
    http_response_code(404);
    echo json_encode(['error' => 'Quiz attempt not found']);
    exit;
}

if ($attempt->userid != $USER->id) {
    file_put_contents($logfile, "ERROR: Attempt belongs to user {$attempt->userid}, not {$USER->id}\n", FILE_APPEND);
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Look up by attemptid only
$log = $DB->get_record('local_consistency_log', ['attemptid' => $attemptid]);

if (!$log) {
    file_put_contents($logfile, "WARNING: No log record found for attemptid=$attemptid, creating one\n", FILE_APPEND);
    
    // Create the record if it doesn't exist
    $log = new stdClass();
    $log->userid = $USER->id;
    $log->attemptid = $attemptid;
    $log->face_detection_warning = 0;
    $log->tab_switch_warning = 0;
    $log->auto_submitted = 0;
    $log->logintime = time();
    $log->last_warning_time = 0;
    
    // Initialize other plugin fields to avoid NULL issues
    $log->notes = '';
    $log->quiz = '';
    $log->assignment = '';
    $log->videos = '';
    $log->logouttime = null;
    $log->notes_time = 0;
    $log->video_time = 0;
    
    try {
        $log->id = $DB->insert_record('local_consistency_log', $log);
        file_put_contents($logfile, "Created new log record with ID: {$log->id}\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents($logfile, "ERROR creating record: " . $e->getMessage() . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create log record']);
        exit;
    }
}

file_put_contents($logfile, "Found/created log record ID: {$log->id}\n", FILE_APPEND);
file_put_contents($logfile, "BEFORE UPDATE - Tab: {$log->tab_switch_warning}, Face: {$log->face_detection_warning}, Auto: {$log->auto_submitted}\n", FILE_APPEND);

// Update warning counts
$currentTime = time();

// ✅ CRITICAL FIX: Handle TAB_SWITCH separately from NO_CAMERA
if ($eventtype === 'TAB_SWITCH') {
    // ONLY TAB_SWITCH affects tab_switch_warning counter
    file_put_contents($logfile, "Tab switch event. Saving EXACT count from JS: $warningcount\n", FILE_APPEND);
    file_put_contents($logfile, "IMPORTANT: This is NOT an increment - this is the total count\n", FILE_APPEND);
    
    // Only auto-submit if count is EXACTLY 5 or more
    $autoSubmit = ($warningcount >= 5) ? 1 : 0;
    
    file_put_contents($logfile, "Auto-submit flag: $autoSubmit (count=$warningcount >= 3)\n", FILE_APPEND);
    
    // Use UPDATE, not increment - set to exact value
    $sql = "UPDATE {local_consistency_log} 
            SET tab_switch_warning = :tabwarning,
                auto_submitted = :autosubmit,
                last_warning_time = :lasttime
            WHERE id = :id";
    
    $params = [
        'tabwarning' => $warningcount,  // EXACT value from JS
        'autosubmit' => $autoSubmit,
        'lasttime' => $currentTime,
        'id' => $log->id
    ];
    
    file_put_contents($logfile, "SQL will SET tab_switch_warning = $warningcount (REPLACE, not ADD)\n", FILE_APPEND);
    
} elseif ($eventtype === 'NO_FACE') {
    file_put_contents($logfile, "Face not detected. Saving count: $warningcount\n", FILE_APPEND);
    
    $sql = "UPDATE {local_consistency_log} 
            SET face_detection_warning = :facewarning,
                last_warning_time = :lasttime
            WHERE id = :id";
    
    $params = [
        'facewarning' => $warningcount,
        'lasttime' => $currentTime,
        'id' => $log->id
    ];
    
} elseif ($eventtype === 'NO_CAMERA') {
    // ✅ NO_CAMERA: Log only, do NOT touch tab_switch_warning
    file_put_contents($logfile, "Camera access denied - logged only, NO counter update\n", FILE_APPEND);
    
    echo json_encode([
        'status' => 'logged',
        'message' => 'Camera issue recorded',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
    
} else {
    file_put_contents($logfile, "ERROR: Unknown event type: $eventtype\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Unknown event type']);
    exit;
}

// Execute the update
try {
    file_put_contents($logfile, "Executing SQL with params: " . print_r($params, true) . "\n", FILE_APPEND);
    $DB->execute($sql, $params);
    file_put_contents($logfile, "SQL executed successfully\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($logfile, "ERROR during SQL execution: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Database update failed', 'message' => $e->getMessage()]);
    exit;
}

// Verify the update
$verify = $DB->get_record('local_consistency_log', ['id' => $log->id]);

if ($verify) {
    file_put_contents($logfile, "AFTER UPDATE - Tab: {$verify->tab_switch_warning}, Face: {$verify->face_detection_warning}, Auto: {$verify->auto_submitted}\n", FILE_APPEND);
    
    // Check if update was successful
    if ($eventtype === 'TAB_SWITCH') {
        if ($verify->tab_switch_warning == $warningcount) {
            file_put_contents($logfile, "✓ SUCCESS: Tab warning saved correctly as $warningcount\n", FILE_APPEND);
        } else {
            file_put_contents($logfile, "✗ WARNING: Tab warning mismatch! Expected $warningcount, got {$verify->tab_switch_warning}\n", FILE_APPEND);
        }
    } elseif ($eventtype === 'NO_FACE') {
        if ($verify->face_detection_warning == $warningcount) {
            file_put_contents($logfile, "✓ SUCCESS: Face warning saved correctly as $warningcount\n", FILE_APPEND);
        } else {
            file_put_contents($logfile, "✗ WARNING: Face warning mismatch! Expected $warningcount, got {$verify->face_detection_warning}\n", FILE_APPEND);
        }
    }
} else {
    file_put_contents($logfile, "ERROR: Could not verify update - record disappeared!\n", FILE_APPEND);
}

file_put_contents($logfile, "=== REQUEST COMPLETED ===\n", FILE_APPEND);

// Return success response
echo json_encode([
    'status' => 'success',
    'record_id' => intval($log->id),
    'tab_warnings' => intval($verify->tab_switch_warning),
    'face_warnings' => intval($verify->face_detection_warning),
    'auto_submitted' => intval($verify->auto_submitted),
    'last_warning_time' => intval($verify->last_warning_time),
    'timestamp' => date('Y-m-d H:i:s')
]);