<?php
// kgsweb2026/api/class-ticker-engine.php

class KGSTicker {
    public static function get_tickerText() {
        try {
            // 1. Check Cache First
            $cached = KGSHelper::get_cache('ticker_data');
            if ($cached) {
                return $cached; // Helper returns the string directly
            }

            // 2. Setup Config
            $base_dir = dirname(__DIR__);
            $config_path = $base_dir . '/config/config.php';
            if (!file_exists($config_path)) return "Welcome to Kell Grade School!";
            
            $config = require $config_path;
            
            // 3. Google API Handshake
            $client = KGSHelper::getClient();
            $drive  = new \Google\Service\Drive($client);
            $docs   = new \Google\Service\Docs($client);

            // 4. Find the newest file in the Ticker folder
            $folderId = $config['folders']['ticker'];
            $response = $drive->files->listFiles([
                'q' => "'$folderId' in parents and trashed = false",
                'orderBy' => 'modifiedTime desc',
                'pageSize' => 1,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true
            ]);

            $files = $response->getFiles();
            if (empty($files)) return "Welcome to Kell Grade School!";

            // 5. Extract text from the Google Doc
            $doc = $docs->documents->get($files[0]->id);
            $content = '';
            foreach ($doc->body->content as $element) {
                if (isset($element->paragraph->elements)) {
                    foreach ($element->paragraph->elements as $pe) {
                        $content .= $pe->textRun->content ?? '';
                    }
                }
            }

            // Clean up extra spaces/line breaks
            $cleanText = trim(preg_replace('/\s+/', ' ', $content));
            
            // 6. Save to local cache & return
            // Even though it's a string, passing it to set_cache is safe
            $finalText = !empty($cleanText) ? $cleanText : "Welcome to Kell Grade School!";
            KGSHelper::set_cache('ticker_data', $finalText);
            
            return $finalText;

        } catch (\Exception $e) {
            error_log("KGS Ticker Error: " . $e->getMessage());
            return "Welcome to Kell Grade School!"; 
        }
    }
}