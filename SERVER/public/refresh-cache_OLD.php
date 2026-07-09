<?php
/**
 * public/refresh-cache.php
 * FORCE-RELOAD TRIGGER
 */

// 1. SURGICAL OPCACHE CLEAR: Forces server to see your new code changes
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(dirname(__DIR__) . '/kgs-core/bootstrap.php', true);
    opcache_invalidate(dirname(__DIR__) . '/cfg/config.php', true);
    opcache_invalidate(dirname(__DIR__) . '/cfg/google.php', true);
}

// 2. Set Muzzle BEFORE bootstrap loads
define('KGS_SILENT_MODE', true);

// 3. Start the recorder
ob_start();

require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';

// 4. Force JSON header
header('Content-Type: application/json');

// 5. VACUUM: Throw away everything caught in the recorder (whitespace/stray logs)
if (ob_get_length()) ob_clean();

// 6. Rate Limit (20 seconds)
$lastRunFile = ROOT_PATH . 'kgs-cache/locks/manual_refresh_time.txt';
if (file_exists($lastRunFile) && (time() - filemtime($lastRunFile)) < 20) {
    echo json_encode(['success' => false, 'message' => 'Cooldown active. Wait 20s.']);
    exit;
}

// 7. Trigger the Google Sync Worker
$workerPath = ROOT_PATH . "kgs-core/workers/refresh-drive-cache.php";
$cmd = "php " . $workerPath . " > /dev/null 2>&1 &";
exec($cmd);

// 8. Purge LiteSpeed/Server Cache (Defined in bootstrap)
if (function_exists('purge_server_cache')) {
    purge_server_cache();
}

touch($lastRunFile);

// 9. Output PURE JSON
echo json_encode([
    'success' => true, 
    'message' => 'Sync started & Server memory cleared!'
]);

// 10. Close recorder
if (ob_get_length()) ob_end_clean();
exit;