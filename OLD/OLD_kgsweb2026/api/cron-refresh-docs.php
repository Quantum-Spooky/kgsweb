#!/usr/local/bin/php.cli
<?php

set_time_limit(300); // Gives the script 5 minutes to finish instead of the default 30 seconds

/**
 * CRON JOB: Refresh Google Drive Tree
 * Location: kgsweb2026/api/cron-refresh-docs.php
 */

// Since crons run from the system root, we force the working directory 
// to the location of this script so includes work correctly.
chdir(__DIR__);

require_once 'class-kgs-helper.php';
require_once 'class-schooldistrict-engine.php';
require_once 'class-docs-engine.php';

// Load config
$config = require dirname(__DIR__) . '/config/config.php';
$root_id = $config['folders']['district_docs_root'];

echo "Starting Google Drive cache rebuild...\n";

try {
    // Trigger the deep rebuild we defined in KGSDocs
    $data = KGSDocs::rebuild_full_cache($root_id);
    echo "Success! Cache updated at: " . date('Y-m-d H:i:s') . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1); // Return error code for server logs
}