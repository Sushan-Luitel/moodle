<?php
// verify_dataroot.php - Place in MOODLE ROOT folder
echo "<h1>Step 1: Verify dataroot Configuration</h1>";

// Check if config.php exists
if (!file_exists(__DIR__ . '/config.php')) {
    die("<p style='color:red'>❌ config.php not found in moodle root!</p>");
}

// Try to load config.php
try {
    require_once(__DIR__ . '/config.php');
    echo "<p style='color:green'>✅ config.php loaded successfully</p>";
} catch (Exception $e) {
    die("<p style='color:red'>❌ Error loading config.php: " . $e->getMessage() . "</p>");
}

// Check if $CFG exists
if (!isset($CFG)) {
    die("<p style='color:red'>❌ \$CFG object not created in config.php</p>");
}

// Check if dataroot is set
if (empty($CFG->dataroot)) {
    echo "<p style='color:red'>❌ \$CFG->dataroot is NOT set!</p>";
    echo "<p>Add this line to config.php: <code>\$CFG->dataroot = 'C:/xampp/moodledata';</code></p>";
} else {
    echo "<p><strong>Current dataroot:</strong> " . htmlspecialchars($CFG->dataroot) . "</p>";
    
    // Check if folder exists
    if (file_exists($CFG->dataroot)) {
        echo "<p style='color:green; background:#d4edda; padding:10px; border:2px solid #155724;'>";
        echo "✅ SUCCESS! moodledata folder found at:<br>";
        echo "<code>" . htmlspecialchars($CFG->dataroot) . "</code>";
        echo "</p>";
        
        // Check if writable
        $testfile = $CFG->dataroot . '/test.txt';
        if (file_put_contents($testfile, 'test')) {
            unlink($testfile);
            echo "<p style='color:green'>✓ Folder is writable</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Folder exists but may not be writable</p>";
        }
    } else {
        echo "<p style='color:red; background:#f8d7da; padding:10px; border:2px solid #721c24;'>";
        echo "❌ ERROR! moodledata folder NOT found at:<br>";
        echo "<code>" . htmlspecialchars($CFG->dataroot) . "</code>";
        echo "</p>";
        echo "<p>Check if the folder exists at that location.</p>";
        echo "<p>If not, create it or update config.php with correct path.</p>";
    }
}

// Check for required folders inside moodledata
if (isset($CFG->dataroot) && file_exists($CFG->dataroot)) {
    echo "<hr><h3>Checking required subfolders:</h3>";
    $required = ['cache', 'temp', 'lang', 'sessions'];
    foreach ($required as $folder) {
        $path = $CFG->dataroot . '/' . $folder;
        if (file_exists($path)) {
            echo "<p style='color:green'>✅ $folder/ exists</p>";
        } else {
            echo "<p style='color:orange'>⚠️ $folder/ missing (Moodle will create it)</p>";
        }
    }
}

echo "<hr><h2>Next Steps:</h2>";
echo "<p>1. If you see GREEN checkmark above → Moodle should work</p>";
echo "<p>2. Go to: <a href='http://localhost/moodle' target='_blank'>Test Moodle Now</a></p>";
echo "<p>3. If you see RED error → Fix the path in config.php</p>";
?>