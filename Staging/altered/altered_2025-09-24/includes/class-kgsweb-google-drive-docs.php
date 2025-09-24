<?php
// includes/class-kgsweb-google-drive-docs.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Docs;

class KGSweb_Google_Drive_Docs {

    /** @var Client */
    private Client $client;

    /** @var Docs|null */
    private $docsService = null;

    public static function init() { /* no-op */ }

    public function __construct(Client $client) {
        $this->client = $client;
    }

	/*******************************
	 * Cron Refresh
	 *******************************/
	public static function refresh_cache_cron() {
		$integration = KGSweb_Google_Integration::init();

		// Rebuild public docs tree
		self::rebuild_documents_tree_cache(self::get_public_root_id());
	}

    /*******************************
     * Root IDs
     *******************************/
    public static function get_public_root_id() {
        return KGSweb_Google_Integration::get_settings()['public_docs_root_id'] ?? '';
    }

    /*******************************
     * Build & Cache Documents Tree
     *******************************/
    public static function rebuild_documents_tree_cache($root) {
        if (empty($root)) return;

        delete_transient('kgsweb_docs_tree_' . md5($root));

        $tree = self::build_documents_tree($root);

        KGSweb_Google_Integration::set_transient('kgsweb_cache_documents_' . $root, $tree, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_documents_' . $root, current_time('timestamp'));
    }

    public static function get_documents_tree_payload($folder_id = '') {
        $root = $folder_id ?: self::get_public_root_id();
        $key = 'kgsweb_cache_documents_' . $root;
        $tree = KGSweb_Google_Integration::get_transient($key);

        if ($tree === false) {
            $tree = self::build_documents_tree($root);
            KGSweb_Google_Integration::set_transient($key, $tree, HOUR_IN_SECONDS);
        }

        if (empty($tree)) {
            return new WP_Error('no_docs', __('No documents available.', 'kgsweb'), ['status' => 404]);
        }

        return ['root_id' => $root, 'tree' => $tree, 'updated_at' => current_time('timestamp')];
    }

    /*******************************
     * Drive Traversal
     *******************************/
    public static function build_documents_tree(string $root_id): array {
        if (empty($root_id)) return [];

        $tree = [];
        $queue = [['id' => $root_id, 'name' => '', 'path' => []]];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $folder_id = $current['id'];
            $path = $current['path'];

            $items = self::list_drive_children($folder_id);
            $children = [];

            foreach ($items as $item) {
                $node = [
                    'id'   => $item['id'],
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
                        'id'   => $item['id'],
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

        // Filter empty branches (remove folders without any files)
        $filtered_tree = [];
        foreach ($tree as $node) {
            $n = self::filter_empty_branches($node);
            if ($n !== null) $filtered_tree[] = $n;
        }

        return $filtered_tree;
    }

    /*******************************
     * Google API Helpers
     *******************************/						 
										 
	public static function list_drive_children($folder_id, $sort_by = 'alpha-asc') {
		$client = KGSweb_Google_Integration::get_google_client();
		if (!$client instanceof Client) return [];

		try {
			$service = new Drive($client);

			$files = [];
			$pageToken = null;

			do {
				$params = [
					'q' => sprintf("'%s' in parents and trashed = false", $folder_id),
					'fields' => 'nextPageToken, files(id,name,mimeType,size,modifiedTime)',
					'pageSize' => 1000,
				];
				if ($pageToken) {
					$params['pageToken'] = $pageToken;
				}

				$response = $service->files->listFiles($params);
				$items = $response->getFiles();

				if (!empty($items)) {
					foreach ($items as $f) {
						$files[] = [
							'id'           => $f->getId(),
							'name'         => $f->getName(),
							'mimeType'     => $f->getMimeType(),
							'size'         => method_exists($f, 'getSize') ? $f->getSize() : 0,
							'modifiedTime' => method_exists($f, 'getModifiedTime') ? $f->getModifiedTime() : '',
						];
					}
				}

				$pageToken = $response->getNextPageToken();
			} while ($pageToken);

			// Apply sort according to $sort_by
			usort($files, function ($a, $b) use ($sort_by) {
				switch ($sort_by) {
					case 'alpha-desc':
						return strcasecmp($b['name'], $a['name']);
					case 'date-asc':
						return strcmp($a['modifiedTime'], $b['modifiedTime']);
					case 'date-desc':
						return strcmp($b['modifiedTime'], $a['modifiedTime']);
					case 'alpha-asc':
					default:
						return strcasecmp($a['name'], $b['name']);
				}
			});

			return $files;

		} catch (Exception $e) {
			error_log("KGSWEB: Failed to list files in folder {$folder_id} - " . $e->getMessage());
			return [];
		}
	}

    private static function inject_children(&$nodes, $target_id, $children) {
        foreach ($nodes as &$node) {
            if (isset($node['id']) && $node['id'] === $target_id && $node['type'] === 'folder') {
                $node['children'] = $children;
                return true;
            }
            if (!empty($node['children'])) {
                if (self::inject_children($node['children'], $target_id, $children)) return true;
            }
        }
        return false;
    }



    private static function filter_empty_branches($node) {
        if (empty($node)) return null;
        if ($node['type'] === 'file') return $node;

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
        return null;
    }

 


    /*******************************
     * List files in folder
     *******************************/
    public function list_files_in_folder(string $folder_id): array {
        try {
            $service = new Drive($this->client);

            $files = [];
            $pageToken = null;

            do {
                $params = [
                    'q' => sprintf("'%s' in parents and trashed = false", $folder_id),
                    'fields' => 'nextPageToken, files(id, name, mimeType, modifiedTime)',
                    'pageSize' => 1000,
                ];

                if ($pageToken) $params['pageToken'] = $pageToken;

                $response = $service->files->listFiles($params);
                foreach ($response->getFiles() as $file) {
                    $files[] = [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType(),
                        'modifiedTime' => $file->getModifiedTime(),
                    ];
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $files;

        } catch (Exception $e) {
            error_log("KGSWEB: Failed to list files in folder {$folder_id} - " . json_encode([
                'error' => $e->getMessage(),
            ]));
            return [];
        }
    }

    /*******************************
     * Manual Cache Refresh (New)
     *******************************/
    public static function force_refresh_file_cache(string $file_id): ?string {
        $client = KGSweb_Google_Integration::get_google_client();
        if (!$client instanceof Client) return null;
        $docs = new self($client);
        $content = $docs->get_file_contents($file_id);
        if ($content !== null) {
            $cache_key = 'kgsweb_cache_file_' . $file_id;
            KGSweb_Google_Integration::set_transient($cache_key, $content, MINUTE_IN_SECONDS * 5);
            error_log("KGSWEB: Force-refreshed cache for file {$file_id}, length=" . strlen($content));
        } else {
            error_log("KGSWEB: Failed to force-refresh cache for {$file_id}");
        }
        return $content;
    }

	/*******************************
	 * Docs API helpers & file content extraction
	 *******************************/
	/*
	private function get_docs_service() {
		if ($this->docsService !== null) return $this->docsService;

		if (!class_exists('\Google\Service\Docs')) {
			error_log('KGSWEB: Google Docs client class not available.');
			return null;
		}

		try {
			$client = KGSweb_Google_Integration::get_google_client();
			if (!$client instanceof Client) {
				error_log('KGSWEB: get_docs_service - google client not available');
				return null;
			}
			$this->docsService = new Docs($client);
			return $this->docsService;
		} catch (Exception $e) {
			error_log('KGSWEB: Failed to initialize Docs service - ' . $e->getMessage());
			return null;
		}
	}

	*/

	/*
	 public function get_file_contents( $file_id, $mime_type = null ) {
		if ( ! $this->client ) {
			error_log("KGSWEB: get_file_contents called but client not initialized!");
			return '';
		}

		try {
			// 	Ensure Drive service is always available
			if (empty($this->service)) {
				$this->service = new Google\Service\Drive($this->client);
				error_log("KGSWEB: get_file_contents - Drive service initialized.");
			}

			// 	Detect MIME if not passed
			if (!$mime_type) {
				$file = $this->service->files->get($file_id, ['fields' => 'mimeType,name']);
				$mime_type = $file->getMimeType();
				error_log("KGSWEB: get_file_contents - detected MIME {$mime_type} for {$file_id}");
			} else {
				error_log("KGSWEB: get_file_contents - using provided MIME {$mime_type} for {$file_id}");
			}

			// 	Handle Google Docs
			if ($mime_type === 'application/vnd.google-apps.document') {
				error_log("KGSWEB: get_file_contents - attempting Docs API extraction for {$file_id}");

				try {
					$docsService = $this->get_docs_service();
					if ($docsService) {
						$doc = $docsService->documents->get($file_id);
						if ($doc && $doc->getBody() && $doc->getBody()->getContent()) {
							$content = $this->extract_text_from_doc($doc);
							error_log("KGSWEB: get_file_contents - Docs API extraction returned " . strlen($content) . " chars.");
							return $content;
						} else {
							error_log("KGSWEB: get_file_contents - Docs API returned empty body, falling back to Drive export.");
						}
					} else {
						error_log("KGSWEB: get_file_contents - Docs API unavailable, falling back to Drive export.");
					}
				} catch (Exception $e) {
					error_log("KGSWEB: get_file_contents - Docs API exception: " . $e->getMessage());
				}

			// 	Fallback to Drive export
				$content = $this->export_google_doc_as_text($file_id);
				error_log("KGSWEB: get_file_contents - fallback Drive export returned " . strlen($content) . " chars.");
				return $content;
			}

			// 	Handle plain text
			if ($mime_type === 'text/plain') {
				error_log("KGSWEB: get_file_contents - downloading plain text file {$file_id}");
				$response = $this->service->files->get($file_id, ['alt' => 'media']);
				if (!$response) {
					error_log("KGSWEB: get_file_contents - ERROR: null response for plain text file {$file_id}");
					return '';
				}
				$body = $response->getBody();
				$content = (string)$body->getContents();
				error_log("KGSWEB: get_file_contents - downloaded " . strlen($content) . " bytes for TXT file.");
				return $content;
			}

			error_log("KGSWEB: get_file_contents - unsupported MIME {$mime_type}, skipping.");
			return '';

		} catch (Exception $e) {
			error_log("KGSWEB: get_file_contents - EXCEPTION for {$file_id}: " . $e->getMessage());
			return '';
		}
	} 
	*/


}
