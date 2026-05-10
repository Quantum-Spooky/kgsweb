<?php
// kgsweb2026/api/class-showdoc-engine.php

$base_dir = dirname(__DIR__); 

require_once $base_dir . '/vendor/autoload.php';

class ShowDoc {

    public static function get_latest_from_folder($folder_key) {
        global $base_dir; 
        
        $config = require $base_dir . '/config/config.php';
        $folder_id = $config['folders'][$folder_key] ?? null;
        if (!$folder_id) return null;

        $cache_dir  = __DIR__ . '/cache/'; 
        $cache_file = $cache_dir . "{$folder_key}_latest.json";
        $cache_img  = $cache_dir . "{$folder_key}_display.png";

        // Check cache (1 hour)
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < 3600)) {
            return json_decode(file_get_contents($cache_file), true);
        }

        // 1. Fetch a pool of files instead of just one
        $files = self::fetch_google_metadata($config, $folder_id); 
        
        if (!$files || count($files) === 0) return null;

        // 2. Determine the "Winner" based on filename dates
        $target_file = self::pick_smart_winner($files);
        
        if ($target_file) {
            if (self::process_google_file($config, $target_file, $cache_img)) {
                $payload = [
                    'url'     => 'api/cache/' . basename($cache_img) . '?v=' . time(),
                    'name'    => $target_file->name,
                    'updated' => date("F j, Y", strtotime($target_file->modifiedTime))
                ];
                file_put_contents($cache_file, json_encode($payload));
                return $payload;
            }
        }
        return null;
    }

    /**
     * Logic to find the best file from the pool
     */
    private static function pick_smart_winner($files) {
        $winner = $files[0]; // Fallback to Google's 'modifiedTime' winner
        $best_date = 0;

        foreach ($files as $file) {
            $file_date = self::extract_date_from_string($file->name);
            
            // If this filename has a date and it's newer than our current best
            if ($file_date && $file_date > $best_date) {
                $best_date = $file_date;
                $winner = $file;
            }
        }
        return $winner;
    }

    /**
     * Extracts a timestamp from strings like "Feb 2026", "2026-05", or "02-26"
     */
    private static function extract_date_from_string($filename) {
        $months = "january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec";
        
        // 1. Match "Month Year" (e.g. February 2026)
        if (preg_match("/($months)\s+(\d{4})/i", $filename, $matches)) {
            return strtotime($matches[0]);
        }

        // 2. Match "YYYY-MM" or "YYYY-MM-DD"
        if (preg_match("/(\d{4})[-\/\._](\d{2})([-\/\._](\d{2}))?/", $filename, $matches)) {
            return strtotime($matches[0]);
        }

        return null;
    }

    private static function fetch_google_metadata($config, $folder_id) {
		try {
			$client = self::get_google_client($config);
			$service = new \Google\Service\Drive($client);    
			$optParams = [
				'q' => "'$folder_id' in parents and trashed = false and mimeType != 'application/vnd.google-apps.folder'",
				'pageSize' => 10, 
				'orderBy' => 'modifiedTime desc',
				'fields' => 'files(id, name, mimeType, modifiedTime)',
				'supportsAllDrives' => true,
				'includeItemsFromAllDrives' => true
			];
			$results = $service->files->listFiles($optParams);
			
			// This is the critical fix: Ensure we return the array of files
			$files = $results->getFiles();
			return (is_array($files)) ? $files : []; 
			
		} catch (\Exception $e) {
			error_log("Google API Metadata Error: " . $e->getMessage());
			return [];
		}
	}

    private static function get_google_client($config) {
        $client = new \Google\Client();
        $auth = $config['google_auth'];
        $auth['private_key'] = str_replace("\\n", "\n", $auth['private_key']);
        $client->setAuthConfig($auth);
        $client->addScope(\Google\Service\Drive::DRIVE_READONLY);
        return $client;
    }
    
    private static function process_google_file($config, $file, $save_path) {
        try {
            $client = self::get_google_client($config);
            $service = new \Google\Service\Drive($client);
            $content = null;
            $is_pdf = ($file->mimeType === 'application/pdf');

            // 1. ATTEMPT DIRECT DOWNLOAD
            try {
                $response = $service->files->get($file->id, ['alt' => 'media']);
                $content = $response->getBody()->getContents();
            } catch (\Exception $e) {
                // 2. ATTEMPT EXPORT (Google Docs)
                try {
                    $response = $service->files->export($file->id, 'application/pdf', ['alt' => 'media']);
                    $content = $response->getBody()->getContents();
                    $is_pdf = true; 
                } catch (\Exception $e2) {
                    throw new \Exception("Both Download and Export failed for " . $file->name);
                }
            }

            if (!$content) return false;

            // 3. PROCESS CONTENT
            if ($is_pdf) {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                $imagick->setBackgroundColor(new \ImagickPixel('white')); 
                $imagick->readImageBlob($content);
                $imagick->setIteratorIndex(0);
                $imagick->setImageFormat('png');
                $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $imagick->writeImage($save_path);
            } else {
                file_put_contents($save_path, $content);
            }
            
            return true;
        } catch (\Exception $e) {
            echo "<div style='color:red; background:#fee; padding:10px; border:1px solid red;'>";
            echo "<strong>Image Engine Error:</strong> " . $e->getMessage();
            echo "</div>";
            return false;
        }
    }
}