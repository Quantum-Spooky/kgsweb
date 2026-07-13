<?php
/**
 * public/refresh-cache.php
 * MANUAL REFRESH TRIGGER
 */

// 1. Force OpCache to see new changes
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(dirname(__DIR__) . '/kgs-core/bootstrap.php', true);
}

// 2. Start the muzzle
define('KGS_SILENT_MODE', true);

// 3. Start the recorder (Buffer)
ob_start();

require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';

// 4. Set headers
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 5. VACUUM: Throw away any bootstrap logs or whitespace caught so far
if (ob_get_length()) ob_clean();

// 6. Rate Limit (20 seconds)
$lastRunFile = ROOT_PATH . 'kgs-cache/locks/manual_refresh_time.txt';
if (file_exists($lastRunFile) && (time() - filemtime($lastRunFile)) < 20) {
    echo json_encode(['success' => false, 'message' => 'Cooldown active. Wait 20s.']);
    ob_end_flush(); // Send what we just echoed
    exit;
}

// 7. Trigger the Google Sync Worker (Background)
$workerPath = ROOT_PATH . "kgs-core/workers/refresh-drive-cache.php";
$cmd = "php " . $workerPath . " > /dev/null 2>&1 &";
exec($cmd);

// 8. Server Purge (Defined in bootstrap)
if (function_exists('purge_server_cache')) {
    purge_server_cache();
}

touch($lastRunFile);

// 9. Output pure JSON
echo json_encode([
    'success' => true, 
    'message' => 'Sync started in background.'
]);

// 10. FLUSH: Send the recorder's contents to the browser and turn it off
ob_end_flush();
exit;