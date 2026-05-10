<?php
class KGSDocs {
    // This is the main function the website calls
    public static function get_full_tree_cached($root_id) {
        $cache_key = 'full_docs_tree';
        $cached = KGSHelper::get_cache($cache_key);
        
        // If cache exists, return it immediately
        if ($cached) return $cached;

        // If no cache (first time or cleared), build it now
        return self::rebuild_full_cache($root_id);
    }

    // This is the function the Cron Job will trigger
    public static function rebuild_full_cache($root_id) {
        $full_tree = self::fetch_recursive($root_id);
        KGSHelper::set_cache('full_docs_tree', $full_tree);
        return $full_tree;
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

		$items = []; // Initialize once here
		
		foreach ($results->getFiles() as $file) {
			$is_folder = ($file->mimeType === 'application/vnd.google-apps.folder');

			if ($is_folder) {
				// 1. GO DEEP FIRST
				$children = self::fetch_recursive($file->id);
				
				// 2. THE PRUNING CHECK
				if (!empty($children)) {
					$items[] = [
						'id'       => $file->id,
						'name'     => $file->name,
						'type'     => 'folder',
						'children' => $children
					];
				}
			} else {
				// 3. FILES: Using 'link' to match your JS expectations
				$items[] = [
					'id'       => $file->getId(),
					'name'     => $file->getName(),
					'mimeType' => $file->getMimeType(),
					'link'     => $file->getWebViewLink() // Changed from 'url' to 'link'
				];
			}
		}
		return $items;
	}
}