<?php
// includes/class-kgsweb-google-helpers.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;

class KGSweb_Google_Helpers {

    public static function init() { /* no-op */ }

    // -----------------------------
    // Google Drive client
    // -----------------------------
    public static function get_drive(): ?Drive {
        $settings = get_option(KGSWEB_SETTINGS_OPTION, []);
        $json = $settings['service_account_json'] ?? '';

        if (!$json) return null;

        try {
            $client = new Client();
            $client->setAuthConfig(json_decode($json, true));
            $client->addScope(Drive::DRIVE);
            return new Drive($client);
        } catch (Exception $e) {
            error_log("[KGSweb] get_drive() ERROR: " . $e->getMessage());
            return null;
        }
    }

    // -----------------------------
    // Folder / File Name Formatting
    // -----------------------------
    public static function format_folder_name(string $name): string {
        $name = preg_replace('/[-_]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return ucwords(trim($name));
    }

    public static function extract_date(string $name): ?string {
        if (preg_match('/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/', $name, $m)) {
            return $m[1] . $m[2] . $m[3];
        }
        return null;
    }

    public static function sanitize_file_name(string $filename): string {
        if (!$filename) return '';
        $sanitized = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);
        if (!$sanitized) {
            error_log("[KGSweb] WARNING: sanitize_file_name() returned empty for filename: $filename");
            $sanitized = 'file-' . time();
        }
        return $sanitized;
    }

    public static function sort_items(array &$items, string $sort_by): void {
        usort($items, function($a, $b) use ($sort_by) {
            $isFolderA = ($a['type'] ?? $a['mimeType'] ?? '') === 'folder';
            $isFolderB = ($b['type'] ?? $b['mimeType'] ?? '') === 'folder';

            if ($isFolderA && !$isFolderB) return -1;
            if (!$isFolderA && $isFolderB) return 1;

            $cmp = strcasecmp($a['name'], $b['name']);
            switch ($sort_by) {
                case 'alpha-desc':
                    return -$cmp;
                case 'date-asc':
                    return strcmp($a['modifiedTime'], $b['modifiedTime']);
                case 'date-desc':
                    return strcmp($b['modifiedTime'], $a['modifiedTime']);
                default:
                    $dateA = self::extract_date($a['name'] ?? '') ?? '99999999';
                    $dateB = self::extract_date($b['name'] ?? '') ?? '99999999';
                    if ($dateA !== $dateB) return strcmp($dateB, $dateA);
                    return $cmp;
            }
        });
    }

    // -----------------------------
    // Canonical File Listing
    // -----------------------------
    public static function list_files_in_folder(string $folder_id, array $options = []): array {
        $drive = self::get_drive();
        if (!$drive) return [];
        return self::_fetch_files_from_drive($drive, $folder_id, $options);
    }

    private static function _fetch_files_from_drive($drive, string $folder_id, array $options = []): array {
		$files = [];
		$pageToken = null;

		$pageSize = $options['pageSize'] ?? 1000;
		$fields = $options['fields'] ?? 'nextPageToken, files(id, name, mimeType, modifiedTime, size, parents)';
		$orderBy = $options['orderBy'] ?? null;

		do {
			$params = [
				'q' => sprintf("'%s' in parents and trashed = false", $folder_id),
				'fields' => $fields,
				'pageSize' => $pageSize,
				// Shared drive support
				'supportsAllDrives' => true,
				'includeItemsFromAllDrives' => true,
				'corpora' => 'allDrives',
			];
			if ($orderBy) $params['orderBy'] = $orderBy;
			if ($pageToken) $params['pageToken'] = $pageToken;

			try {
				$response = $drive->files->listFiles($params);
			} catch (Exception $e) {
				error_log("KGSWEB ERROR: Failed fetching folder $folder_id - " . $e->getMessage());
				return [];
			}

			$fetchedFiles = $response->getFiles();
			
			
											KGSweb_Google_Helpers::test(); // TEST
			
			
			
			error_log("KGSWEB: Fetched " . count($fetchedFiles) . " files from folder $folder_id");

			foreach ($fetchedFiles as $file) {
				$files[] = [
					'id' => $file->getId(),
					'name' => $file->getName(),
					'mimeType' => $file->getMimeType(),
					'modifiedTime' => $file->getModifiedTime(),
					'size' => $file->getSize(),
					'parents' => $file->getParents(),
				];
			}

			$pageToken = $response->getNextPageToken();
		} while ($pageToken);

		return $files;
	}


    // -----------------------------
    // Fetch file contents
    // -----------------------------
	public static function get_file_contents(string $file_id, ?string $mimeType = null): ?string {
    $drive = self::get_drive();
    if (!$drive) return null;

    try {
        // Create a request to download the file
        $response = $drive->files->get($file_id, ['alt' => 'media']);

        // Use the Google client’s HTTP client to fetch the raw body
        $http = $drive->getClient()->getHttpClient();
        $req  = $http->request('GET', $response->getHeader('Location')[0] ?? $response->getHeader('Content-Location')[0] ?? $response->getBody()->getUri());
        $body = $req->getBody();

        return $body ? (string)$body->getContents() : null;
    } catch (Exception $e) {
        error_log("[KGSweb] get_file_contents ERROR for $file_id: " . $e->getMessage());
        return null;
    }
}

    // -----------------------------
    // Tree traversal / latest file
    // -----------------------------
    public static function get_latest_file_from_folder(string $folder_id): ?array {
        $drive = self::get_drive();
        if (!$drive) return null;

        $files = self::list_files_in_folder($folder_id, ['orderBy' => 'modifiedTime desc', 'pageSize' => 50]);
        if (empty($files)) return null;

        foreach ($files as $f) {
            if (in_array($f['mimeType'], ['application/vnd.google-apps.document','text/plain','application/pdf'], true)) {
                return $f;
            }
        }

        return $files[0] ?? null;
    }
	
	/*******************************
	 * Build full documents tree
	 *******************************/
	public static function build_documents_tree(string $root_id): array {
		if (empty($root_id)) return [];

		$tree = [];
		$queue = [['id' => $root_id, 'name' => '', 'path' => []]];

		while (!empty($queue)) {
			$current = array_shift($queue);
			$folder_id = $current['id'];
			$path = $current['path'];

			// ✅ Use the unified list_drive_children()
			$items = self::list_files_in_folder($folder_id);
			$children = [];

			foreach ($items as $item) {
				$node = [
					'id' => $item['id'],
					'name' => $item['name'],
					'type' => $item['mimeType'] === 'application/vnd.google-apps.folder' ? 'folder' : 'file',
				];

				if ($node['type'] === 'file') {
					$node['mime'] = $item['mimeType'];
					$node['size'] = $item['size'] ?? 0;
					$node['modifiedTime'] = $item['modifiedTime'] ?? '';
					$ext = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
					$node['icon'] = KGSweb_Google_Helpers::icon_for_mime_or_ext($item['mimeType'], $ext);
					$children[] = $node;
				} else {
					$queue[] = [
						'id' => $item['id'],
						'name' => $item['name'],
						'path' => array_merge($path, [$item['name']]),
					];
					$children[] = $node + ['children' => []]; // placeholder
				}
			}

			if ($folder_id === $root_id) {
				$tree = $children;
			} else {
				self::inject_children($tree, $folder_id, $children);
			}
		}
	    return $tree; 
	}
	
	
	
	/*******************************
	 * Filter empty folders (recursive)
	 *******************************/
	private static function filter_empty_branches($node) {
		if (empty($node)) return null;

		// Base case: file nodes always pass through
		if (($node['type'] ?? null) === 'file') {
			return $node;
		}

		// If folder has children, recursively filter them
		// If folder has children, recursively filter them
		if (!empty($node['children'])) {
			$filtered = [];
			foreach ($node['children'] as $child) {
				$c = self::filter_empty_branches($child);
				if ($c !== null) $filtered[] = $c;
			}

			if (!empty($filtered)) {
				$node['children'] = $filtered;
				return $node;
			}
		}

		// If no children remain after filtering, prune this branch
		return null;
	}


	
    // -----------------------------
    // Cached Documents Tree
    // -----------------------------
	public static function get_cached_documents_tree(string $root_id): array {
		// Ensure root ID is valid
		$root_safe = !empty($root_id) ? $root_id : 'default_root';
		$cache_key = 'kgsweb_docs_tree_' . md5($root_safe);

		// Try retrieving cached tree
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			return $cached;
		}

		// Fetch documents tree from Google Drive
		try {
			$tree = self::build_documents_tree($root_id);
			$tree = array_values(array_filter(array_map([self::class, 'filter_empty_branches'], $tree)));

			if (!empty($tree)) {
				set_transient($cache_key, $tree, HOUR_IN_SECONDS);
			} else {
				error_log("KGSWEB: Documents tree is empty for root ID '{$root_id}'. Not caching.");
			}

			return $tree;

		} catch (Exception $e) {
			error_log("KGSWEB: Failed to fetch documents tree for root ID '{$root_id}': " . $e->getMessage());
			return [];
		}
	}


    // -----------------------------
    // Icon selection
    // -----------------------------
    public static function icon_for_mime_or_ext(?string $mime, ?string $ext): string {
        $mime = strtolower($mime ?? '');
        $ext = strtolower($ext ?? '');
        if ($mime === 'application/vnd.google-apps.folder') return 'fa-folder';
        if ($ext === 'pdf') return 'fa-file-pdf';
        if (in_array($ext, ['doc','docx'])) return 'fa-file-word';
        if (in_array($ext, ['xls','xlsx'])) return 'fa-file-excel';
        if (in_array($ext, ['ppt','pptx'])) return 'fa-file-powerpoint';
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) return 'fa-file-image';
        if (in_array($ext, ['wav','mp4','m4v','mov','avi'])) return 'fa-file-video';
        if (in_array($ext, ['mp3','wav'])) return 'fa-file-audio';
        return 'fa-file';
    }

    // -----------------------------
    // Fetch and cache PDF as PNG
    // -----------------------------
    public static function convert_pdf_to_png_cached($pdf_path, $filename = null) {
        $upload_dir = wp_upload_dir();
        $cache_dir  = $upload_dir['basedir'] . '/kgsweb-cache/';
        if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);

        $basename = $filename ? self::sanitize_file_name($filename) : basename($pdf_path);
        $png_path = $cache_dir . pathinfo($basename, PATHINFO_FILENAME) . '.png';
        $meta_path = $png_path . '.meta.json';

        if (file_exists($png_path)) {
            if (!file_exists($meta_path)) {
                [$width, $height] = getimagesize($png_path);
                file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
            }
            return $png_path;
        }

        if (!file_exists($pdf_path)) return false;
        if (!class_exists('Imagick')) return false;

        try {
            $img = new Imagick();
            $img->setResolution(150, 150);
            $img->readImage($pdf_path . '[0]');
            $img->setImageFormat('png');
            if ($img->getImageWidth() > 1000) {
                $img->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1);
            }
            $img->setImageCompression(Imagick::COMPRESSION_JPEG);
            $img->setImageCompressionQuality(85);
            $img->stripImage();
            $img->writeImage($png_path);
            [$width, $height] = getimagesize($png_path);
            file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
            $img->clear();
            $img->destroy();
            return $png_path;
        } catch (Exception $e) {
            error_log("[KGSweb] PDF to PNG conversion failed for $pdf_path: " . $e->getMessage());
            return false;
        }
    }

	
    // -----------------------------
    // Cached file URL
    // -----------------------------
    public static function get_cached_file_url(string $path): string {
        $upload_dir = wp_upload_dir();
        return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $path);
    }



				// -----------------------------
				// TEST
				// -----------------------------
				public static function test() {
					$test_folder_id = '1nz-2OAaUDOTwcmcGO7396pbRS2wqkXy0'; 
					$files = self::list_files_in_folder($test_folder_id);

					if (empty($files)) {
						echo "No files returned for folder $test_folder_id\n";
					} else {
						echo "Files in folder $test_folder_id:\n";
						foreach ($files as $file) {
							echo sprintf(
								"- %s (ID: %s, MIME: %s, Size: %s)\n",
								$file['name'],
								$file['id'],
								$file['mimeType'],
								$file['size'] ?? 'unknown'
							);
						}
					}
				}
	




}
