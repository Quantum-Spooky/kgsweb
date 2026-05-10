<?php
// kgsweb2026/api/class-kgs-helper.php

// Establish the Root Path 
$base_dir = dirname(__DIR__); 

if (file_exists($base_dir . '/vendor/autoload.php')) {
    require_once $base_dir . '/vendor/autoload.php';
} else {
    // This will help us find the problem if the path is wrong
    die("Helper Error: Could not find autoloader at " . $base_dir . '/vendor/autoload.php');
}

class KGSHelper {
    
    public static function getClient() {
		$base_dir = dirname(__DIR__);
		$config = require $base_dir . '/config/config.php';
        
        $client = new \Google\Client();
        
        // Fix the private key newlines (essential for Service Accounts)
        $auth = $config['google_auth'];
        if (isset($auth['private_key'])) {
            $auth['private_key'] = str_replace("\\n", "\n", $auth['private_key']);
        }
        
        $client->setAuthConfig($auth);
        
        // Add all necessary scopes
        $client->addScope(\Google\Service\Calendar::CALENDAR_READONLY);
        $client->addScope(\Google\Service\Drive::DRIVE_READONLY);
        $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);
        //$client->addScope(\Google\Service\Docs::DOCS_READONLY); // Added for the Ticker!
        $client->addScope("https://www.googleapis.com/auth/documents.readonly");
		
        return $client;
    }


	// GET AND SET CACHE TO MINIMIZE GOOGLE API CALLS
	
	public static function get_cache($key) {
		// __DIR__ is /api/, so this looks in /api/cache/
		$file = __DIR__ . "/cache/{$key}.json";
		
		if (file_exists($file) && (time() - filemtime($file) < 3600)) {
			$data = file_get_contents($file);
			$decoded = json_decode($data, true);
			return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $data;
		}
		return false;
	}

	public static function set_cache($key, $data) {
		// Force path to /api/cache/
		$dir = __DIR__ . '/cache';
		
		if (!is_dir($dir)) { 
			mkdir($dir, 0775, true); 
		}
		
		$output = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
		file_put_contents($dir . "/{$key}.json", $output);
	}
}