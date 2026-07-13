<?php
/**
 * Icon Tool Runner
 * Provides a web-accessible gateway to the protected script.
 */

// 1. Load the system foundation
require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';

// 2. Security Check (Optional: Only allow if logged in)
session_start();
if (!isset($_SESSION['logged_in'])) {
    // If you want to keep this private, uncomment the next line:
    // die("Access Denied: Please log in to the admin panel first.");
}

// 3. Include the actual logic from the protected scripts folder
$scriptPath = ROOT_PATH . 'tools/scripts/icon-preview.php';

if (file_exists($scriptPath)) {
    include $scriptPath;
} else {
    echo "<h1>Error</h1><p>Logic file not found at: <code>$scriptPath</code></p>";
}