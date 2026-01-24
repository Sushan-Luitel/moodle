<?php
// Place this file at: /local/focusmonitor/test_db.php
// Access it directly: http://yoursite/local/focusmonitor/test_db.php?attemptid=123

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../../config.php');
require_login();

global $DB, $USER;

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Database Connection Test</h2>";

$attemptid = optional_param('attemptid', 0, PARAM_INT);

if (!$attemptid) {
    echo "<p style='color:red;'>ERROR: No attempt ID provided. Add ?attemptid=123 to URL</p>";
    die();
}

echo "<p>Testing with Attempt ID: <strong>$attemptid</strong></p>";
echo "<p>User ID: <strong>{$USER->id}</strong></p>";
echo "<hr>";

// Test 1: Check if record exists
echo "<h3>Test 1: Check Existing Record</h3>";
$log = $DB->get_record('local_consistency_log', [
    'userid' => $USER->id,
    'attemptid' => $attemptid
]);

if ($log) {
    echo "<p style='color:green;'>✓ Record found with ID: {$log->id}</p>";
    echo "<pre>";
    print_r($log);
    echo "</pre>";
} else {
    echo "<p style='color:orange;'>⚠ No record found. Creating one...</p>";
    
    // Create record
    $log = new stdClass();
    $log->userid = $USER->id;
    $log->attemptid = $attemptid;
    $log->face_detection_warning = 0;
    $log->tab_switch_warning = 0;
    $log->auto_submitted = 0;
    $log->logintime = time();
    $log->last_warning_time = 0;
    
    try {
        $log->id = $DB->insert_record('local_consistency_log', $log);
        echo "<p style='color:green;'>✓ Record created with ID: {$log->id}</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ Failed to create: {$e->getMessage()}</p>";
        die();
    }
}

echo "<hr>";

// Test 2: Try to update using update_record()
echo "<h3>Test 2: Update Using update_record()</h3>";
$log->tab_switch_warning = 99;
$log->face_detection_warning = 88;
$log->last_warning_time = time();

try {
    $result = $DB->update_record('local_consistency_log', $log);
    echo "<p style='color:green;'>✓ update_record() returned: " . ($result ? 'TRUE' : 'FALSE') . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ update_record() failed: {$e->getMessage()}</p>";
}

// Verify
$verify = $DB->get_record('local_consistency_log', ['id' => $log->id]);
echo "<p>After update_record():</p>";
echo "<pre>";
print_r($verify);
echo "</pre>";

if ($verify->tab_switch_warning == 99) {
    echo "<p style='color:green;'>✓ Tab warning updated successfully!</p>";
} else {
    echo "<p style='color:red;'>✗ Tab warning NOT updated (expected 99, got {$verify->tab_switch_warning})</p>";
}

echo "<hr>";

// Test 3: Try direct SQL update
echo "<h3>Test 3: Update Using Direct SQL</h3>";

$sql = "UPDATE {local_consistency_log} 
        SET tab_switch_warning = :tabwarning,
            face_detection_warning = :facewarning,
            last_warning_time = :lasttime
        WHERE id = :id";

$params = [
    'tabwarning' => 77,
    'facewarning' => 66,
    'lasttime' => time(),
    'id' => $log->id
];

try {
    $DB->execute($sql, $params);
    echo "<p style='color:green;'>✓ SQL execute() completed</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ SQL execute() failed: {$e->getMessage()}</p>";
}

// Verify
$verify2 = $DB->get_record('local_consistency_log', ['id' => $log->id]);
echo "<p>After SQL execute():</p>";
echo "<pre>";
print_r($verify2);
echo "</pre>";

if ($verify2->tab_switch_warning == 77) {
    echo "<p style='color:green;'>✓ SQL update worked!</p>";
} else {
    echo "<p style='color:red;'>✗ SQL update failed (expected 77, got {$verify2->tab_switch_warning})</p>";
}

echo "<hr>";
echo "<h3>Conclusion</h3>";
echo "<p>If both tests show green checkmarks, the database is working fine.</p>";
echo "<p>If update_record() fails but SQL works, there's an issue with the Moodle API.</p>";
echo "<p>If both fail, there's a database permission or table structure issue.</p>";