<?php
// kgsweb2026/api/class-schooldistrict-engine.php

class KGSSchoolDistrict {
    public static function get_doc_content($doc_id) {
        // Create a unique cache key for each document
        $cache_key = 'doc_' . substr($doc_id, 0, 10);
        $cached = KGSHelper::get_cache($cache_key);
        if ($cached) return $cached;

        try {
            $client = KGSHelper::getClient();
            $docs = new \Google\Service\Docs($client);
            $doc = $docs->documents->get($doc_id);
            
            $html = '';
            foreach ($doc->body->content as $element) {
                if (isset($element->paragraph)) {
                    $para_text = '';
                    foreach ($element->paragraph->elements as $pe) {
                        $para_text .= $pe->textRun->content ?? '';
                    }
                    // Wrap in P tags if there is actual text
                    if (trim($para_text) !== '') {
                        $html .= '<p>' . nl2br(htmlspecialchars($para_text)) . '</p>';
                    }
                }
            }
            
            KGSHelper::set_cache($cache_key, $html);
            return $html;
        } catch (\Exception $e) {
            error_log("School District Doc Error: " . $e->getMessage());
            return "<p>Content temporarily unavailable.</p>";
        }
    }
	
	public static function get_board_members($spreadsheetId) {
		try {
			// CHANGED: get_google_client() to getClient() to match kgs-helper.php
			$client = KGSHelper::getClient();
			
			$service = new \Google\Service\Sheets($client);
			
			// Ensure "Sheet1" matches the tab name in your actual Google Sheet
			$range = 'Sheet1!A2:B20'; 
			
			$response = $service->spreadsheets_values->get($spreadsheetId, $range);
			$values = $response->getValues();
			
			if (empty($values)) return [];

			$members = [];
			foreach ($values as $row) {
				if (empty($row[0])) continue; 
				$members[] = [
					'name'     => $row[0],
					'position' => $row[1] ?? 'Board Member'
				];
			}
			return $members;
		} catch (\Exception $e) { // Added backslash to \Exception
			error_log("Board Members Error: " . $e->getMessage());
			return ["error" => $e->getMessage()];
		}
	}
	
	private static function fetch_recursive($folder_id) {
		$client = KGSHelper::getClient();
		$drive = new \Google\Service\Drive($client);
		
		$results = $drive->files->listFiles([
			'q' => "'$folder_id' in parents and trashed = false",
			'orderBy' => 'folder, name',
			'fields' => 'files(id, name, mimeType, webViewLink)',
			'supportsAllDrives' => true,
			'includeItemsFromAllDrives' => true
		]);

		$items = [];
		foreach ($results->getFiles() as $file) {
			$is_folder = ($file->mimeType === 'application/vnd.google-apps.folder');
			
			if ($is_folder) {
				// GO DEEP FIRST
				$children = self::fetch_recursive($file->id);
				
				// ONLY add this folder if it contains files OR subfolders that have files
				if (!empty($children)) {
					$items[] = [
						'id'       => $file->id,
						'name'     => $file->name,
						'type'     => 'folder',
						'children' => $children
					];
				}
			} else {
				// It's a file, always add it
				$items[] = [
					'id'   => $file->id,
					'name' => $file->name,
					'type' => 'file',
					'link' => $file->webViewLink
				];
			}
		}
		return $items;
	}
	
	public static function get_staff_directory($sheet_id) {
		$cache_key = 'staff_directory_data';
		$cached = KGSHelper::get_cache($cache_key);
		if ($cached) return $cached;

		try {
			$client = KGSHelper::getClient();
			$service = new \Google\Service\Sheets($client);
			// Fetches Columns A through H
			$range = 'Sheet1!A2:H100'; 
			$response = $service->spreadsheets_values->get($sheet_id, $range);
			$values = $response->getValues();

			$staff = [];
			if (!empty($values)) {
				foreach ($values as $row) {
					$staff[] = [
						'photo'    => $row[0] ?? '',
						'fname'    => $row[1] ?? '',
						'lname'    => $row[2] ?? '',
						'position' => $row[3] ?? '',
						'duties'   => $row[4] ?? '',
						'phone'    => $row[5] ?? '',
						'email'    => $row[6] ?? '',
						'webpage'  => $row[7] ?? ''
					];
				}
			}
			KGSHelper::set_cache($cache_key, $staff);
			return $staff;
		} catch (Exception $e) {
			return [];
		}
	}

	
}