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

    /** @var Drive|null */
    private $service = null;

    /*******************************
     * Class Initialization
     *******************************/
    public static function init() { /* no-op */ }

    public function __construct(Client $client) {
        $this->client = $client;
    }

    /*******************************
     * CRON Refresh
     * Rebuilds cached trees & menus
     *******************************/
    public static function refresh_cache_cron() {
        $integration = KGSweb_Google_Integration::init();

        // Rebuild public docs tree
        self::rebuild_documents_tree_cache(self::get_public_root_id());

        // Rebuild upload folder tree
        self::rebuild_upload_tree_cache(self::get_upload_root_id());

        // Refresh menus
        self::refresh_menu_cache('breakfast');
        self::refresh_menu_cache('lunch');
    }

    /*******************************
     * Root IDs
     *******************************/
    public static function get_public_root_id() {
        return KGSweb_Google_Integration::get_settings()['public_docs_root_id'] ?? '';
    }

    public static function get_upload_root_id() {
        return KGSweb_Google_Integration::get_settings()['upload_root_id'] ?? '';
    }

    /*******************************
     * Rebuild Documents Tree Cache
     *******************************/
    public static function rebuild_documents_tree_cache($root) {
        if (empty($root)) return;

        delete_transient('kgsweb_docs_tree_' . md5($root));

        $tree = self::build_documents_tree($root);

        $payload_to_cache = [
            'tree' => $tree,
            'last_fetched' => current_time('timestamp'),
            'max_modified_time' => self::find_max_modified_time($tree),
            'folder_ids' => self::collect_folder_ids($tree),
        ];

        KGSweb_Google_Integration::set_transient('kgsweb_cache_documents_' . $root, $payload_to_cache, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_documents_' . $root, current_time('timestamp'));

        error_log(sprintf(
            "KGSWEB: Cached documents tree for root %s. Items: %d, max_modified_time=%s",
            $root,
            count($payload_to_cache['folder_ids']),
            $payload_to_cache['max_modified_time']
        ));
    }

    public static function rebuild_upload_tree_cache($root) {
        if (empty($root)) return;

        $tree = self::build_folders_only_tree($root);
        KGSweb_Google_Integration::set_transient('kgsweb_cache_upload_tree_' . $root, $tree, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_uploadtree_' . $root, current_time('timestamp'));
        error_log("KGSWEB: Cached upload tree for root {$root}");
    }

    /*******************************
     * Documents Tree Retrieval with Early Invalidation
     *
     * Accepts $folder_id (from shortcode 'folder' attribute).
     * Uses cached tree if valid, otherwise rebuilds.
     *******************************/
    public static function get_documents_tree_payload($folder_id = '') {
        $root = $folder_id ?: self::get_public_root_id();
        if (empty($root)) {
            return new WP_Error('no_root', __('No document root configured.', 'kgsweb'), ['status' => 404]);
        }

        $cache_key = 'kgsweb_cache_documents_' . $root;
        $cached = KGSweb_Google_Integration::get_transient($cache_key);

        if ($cached !== false && is_array($cached)) {
            $tree = $cached['tree'] ?? [];
            $last_fetched = $cached['last_fetched'] ?? current_time('timestamp');
            $max_modified_time = $cached['max_modified_time'] ?? null;
            $folder_ids = $cached['folder_ids'] ?? [];

            if ($max_modified_time) {
                // Early cache invalidation: query Drive for changes since max_modified_time
                try {
                    $client = KGSweb_Google_Integration::get_google_client();
                    if ($client instanceof Client) {
                        $service = new Drive($client);
                        $pageToken = null;
                        $found_new = false;
                        $since = gmdate('Y-m-d\TH:i:s\Z', strtotime($max_modified_time));

                        do {
                            $params = [
                                'q' => sprintf("modifiedTime > '%s' and trashed = false", $since),
                                'fields' => 'nextPageToken, files(id, parents, modifiedTime)',
                                'pageSize' => 100,
                            ];
                            if ($pageToken) $params['pageToken'] = $pageToken;

                            $response = $service->files->listFiles($params);
                            $items = $response->getFiles();

                            if (!empty($items)) {
                                foreach ($items as $f) {
                                    $fid = $f->getId();
                                    if (in_array($fid, $folder_ids, true)) {
                                        $found_new = true;
                                        error_log("KGSWEB: Early cache invalidation triggered for root {$root}. Found updated folder {$fid}.");
                                        break;
                                    }

                                    $parents = $f->getParents() ?? [];
                                    foreach ($parents as $p) {
                                        if (in_array($p, $folder_ids, true)) {
                                            $found_new = true;
                                            error_log("KGSWEB: Early cache invalidation triggered for root {$root}. Found updated file {$fid} in parent folder {$p}.");
                                            break 2;
                                        }
                                    }
                                }
                            }

                            $pageToken = $response->getNextPageToken();
                        } while (!$found_new && $pageToken);

                        if ($found_new) {
                            // Invalidate cached payload
                            KGSweb_Google_Integration::set_transient($cache_key, false, 1);
                            $cached = false;
                        }
                    }
                } catch (Exception $e) {
                    error_log("KGSWEB WARNING: Early freshness check failed for root {$root} - " . $e->getMessage() . ". Using cached tree.");
                }
            }

            if ($cached !== false) {
                return [
                    'root_id' => $root,
                    'tree' => $tree,
                    'updated_at' => $last_fetched,
                    'max_modified_time' => $max_modified_time,
                    'cached' => true,
                ];
            }
        }

        // Cache miss or invalidated: rebuild fresh tree
        try {
            $tree = self::build_documents_tree($root);
            $max_modified_time = self::find_max_modified_time($tree);
            $folder_ids = self::collect_folder_ids($tree);

            $payload_to_cache = [
                'tree' => $tree,
                'last_fetched' => current_time('timestamp'),
                'max_modified_time' => $max_modified_time,
                'folder_ids' => $folder_ids,
            ];

            KGSweb_Google_Integration::set_transient($cache_key, $payload_to_cache, HOUR_IN_SECONDS);
            update_option('kgsweb_cache_last_refresh_documents_' . $root, current_time('timestamp'));

            error_log(sprintf(
                "KGSWEB: Rebuilt and cached documents tree for root %s. Items: %d, max_modified_time=%s",
                $root,
                count($folder_ids),
                $max_modified_time
            ));

            return [
                'root_id' => $root,
                'tree' => $tree,
                'updated_at' => current_time('timestamp'),
                'max_modified_time' => $max_modified_time,
                'cached' => false,
            ];
        } catch (Exception $e) {
            error_log("KGSWEB ERROR: Failed to rebuild documents tree for {$root} - " . $e->getMessage());
            return new WP_Error('kgsweb_drive_error', __('Failed to fetch Google Drive tree.', 'kgsweb'), ['status' => 500]);
        }
    }

    /*******************************
     * Helper: Collect folder IDs from tree
     *******************************/
    private static function collect_folder_ids($nodes) {
        $ids = [];
        $walk = function($items) use (&$walk, &$ids) {
            foreach ((array)$items as $n) {
                if (empty($n) || !is_array($n)) continue;
                if (isset($n['type']) && $n['type'] === 'folder' && !empty($n['id'])) {
                    $ids[] = $n['id'];
                }
                if (!empty($n['children'])) $walk($n['children']);
            }
        };
        $walk($nodes);
        return array_values(array_unique($ids));
    }

    /*******************************
     * Helper: Find max modifiedTime in tree
     *******************************/
    private static function find_max_modified_time($nodes) {
        $max = null;
        $walk = function($items) use (&$walk, &$max) {
            foreach ((array)$items as $n) {
                if (empty($n) || !is_array($n)) continue;
                if (!empty($n['modifiedTime']) && (!$max || strtotime($n['modifiedTime']) > strtotime($max))) {
                    $max = $n['modifiedTime'];
                }
                if (!empty($n['children'])) $walk($n['children']);
            }
        };
        $walk($nodes);
        return $max;
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

            $items = self::list_drive_children($folder_id);
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

        // Filter empty folders
        $filtered_tree = [];
        foreach ($tree as $node) {
            $n = self::filter_empty_branches($node);
            if ($n !== null) $filtered_tree[] = $n;
        }

        return $filtered_tree;
    }

    /*******************************
     * Inject children into tree recursively
     *******************************/
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

    /*******************************
     * List Drive children for a folder
     *******************************/
    private static function list_drive_children($folder_id) {
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
                if ($pageToken) $params['pageToken'] = $pageToken;

                $response = $service->files->listFiles($params);
                $items = $response->getFiles();

                foreach ($items as $f) {
                    $files[] = [
                        'id' => $f->getId(),
                        'name' => $f->getName(),
                        'mimeType' => $f->getMimeType(),
                        'size' => method_exists($f, 'getSize') ? $f->getSize() : 0,
                        'modifiedTime' => method_exists($f, 'getModifiedTime') ? $f->getModifiedTime() : '',
                    ];
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $files;
        } catch (Exception $e) {
            error_log("KGSWEB ERROR: Failed to list files in folder {$folder_id} - " . $e->getMessage());
            return [];
        }
    }

    /*******************************
     * Filter empty folders (recursive)
     *******************************/
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
     * Folders-only tree for uploads
     *******************************/
    private static function build_folders_only_tree($root_id) {
        if (empty($root_id)) return [];

        $client = KGSweb_Google_Integration::get_google_client();
        if (!$client instanceof Client) return [];

        $service = new Drive($client);

        $fetch_folders = function($parent_id) use (&$fetch_folders, $service) {
            $folders = [];
            $params = [
                'q' => sprintf("'%s' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false", $parent_id),
                'fields' => 'nextPageToken, files(id,name)',
                'pageSize' => 1000,
            ];
            $pageToken = null;
            do {
                if ($pageToken) $params['pageToken'] = $pageToken;
                $response = $service->files->listFiles($params);
                $results = $response->getFiles();

                foreach ($results as $f) {
                    $child = [
                        'id' => $f->getId(),
                        'name' => $f->getName(),
                        'type' => 'folder',
                        'children' => $fetch_folders($f->getId()),
                    ];
                    $folders[] = $child;
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $folders;
        };

        return [
            [
                'id' => $root_id,
                'name' => '',
                'type' => 'folder',
                'children' => $fetch_folders($root_id),
            ]
        ];
    }

    /*******************************
     * Upload Tree Helpers
     *******************************/
    public static function folder_exists_in_upload_tree($folder_id) {
        $root = self::get_upload_root_id();
        $tree = get_transient('kgsweb_cache_upload_tree_' . $root);

        if ($tree === false) {
            $tree = self::build_folders_only_tree($root);
            KGSweb_Google_Integration::set_transient('kgsweb_cache_upload_tree_' . $root, $tree, HOUR_IN_SECONDS);
        }

        return self::search_tree_for_id($tree, $folder_id);
    }

    private static function search_tree_for_id($nodes, $id) {
        foreach ((array)$nodes as $n) {
            if (isset($n['id']) && $n['id'] === $id) return true;
            if (!empty($n['children']) && self::search_tree_for_id($n['children'], $id)) return true;
        }
        return false;
    }

    public static function folder_path_from_id($folder_id) {
        return sanitize_title($folder_id);
    }

    /*******************************
     * Menus (Breakfast/Lunch)
     *******************************/
    public static function get_menu_payload($type) {
        $key = 'kgsweb_cache_menu_' . $type;
        $data = get_transient($key);
        if ($data === false) {
            $data = self::build_latest_menu_image($type);
            set_transient($key, $data, HOUR_IN_SECONDS);
        }
        if (empty($data['image_url'])) {
            return new WP_Error('no_menu', __('Menu not available.', 'kgsweb'), ['status' => 404]);
        }
        return $data;
    }

    public static function refresh_menu_cache($type) {
        $data = self::build_latest_menu_image($type);
        set_transient('kgsweb_cache_menu_' . $type, $data, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_menu_' . $type, current_time('timestamp'));
    }

    private static function build_latest_menu_image($type) {
        return [
            'type' => $type,
            'image_url' => '',
            'width' => 0,
            'height' => 0,
            'updated_at' => current_time('timestamp')
        ];
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


/**
 * Extracts plain text from a Google Docs API document object.
 *
 * @param Google\Service\Docs\Document $doc
 * @return string Plain text content
 */
private function extract_text_from_doc( $doc ) {
    if ( ! $doc || ! $doc->getBody() || ! $doc->getBody()->getContent() ) {
        error_log( "KGSWEB: extract_text_from_doc - received empty document object" );
        return '';
    }

    $output = '';

    // Iterate through all structural elements (paragraphs, tables, etc.)
    foreach ( $doc->getBody()->getContent() as $structuralElement ) {
        if ( ! isset( $structuralElement['paragraph'] ) ) {
            continue;
        }

        $paragraph = $structuralElement['paragraph'];
        if ( ! isset( $paragraph['elements'] ) ) {
            continue;
        }

        foreach ( $paragraph['elements'] as $element ) {
            if ( isset( $element['textRun']['content'] ) ) {
                $output .= $element['textRun']['content'];
            }
        }

        // Ensure line breaks between paragraphs
        $output .= "\n";
    }

    error_log( "KGSWEB: extract_text_from_doc - extracted " . strlen( $output ) . " chars" );
    return trim( $output );
}


/**
 * Helper: fallback export of Google Doc as plain text
 */
private function export_google_doc_as_text( $file_id ) {
    try {
        $response = $this->service->files->export( $file_id, 'text/plain', array( 'alt' => 'media' ) );
        if ( ! $response ) {
            error_log( "KGSWEB ERROR: export_google_doc_as_text - NULL response for {$file_id}" );
            return '';
        }

        $body = method_exists( $response, 'getBody' ) ? $response->getBody() : null;
        if ( ! $body ) {
            error_log( "KGSWEB ERROR: export_google_doc_as_text - Missing body for {$file_id}" );
            return '';
        }

        $content = (string) $body->getContents();
        error_log( "KGSWEB: export_google_doc_as_text returned " . strlen( $content ) . " chars for {$file_id}" );
        return $content;

    } catch ( Exception $e ) {
        error_log( "KGSWEB ERROR: export_google_doc_as_text failed for {$file_id}: " . $e->getMessage() );
        return '';
    }
}


    private function extract_paragraph_text_from_structural_elements($elements) {
        $text = '';
        if (empty($elements)) return $text;

        foreach ($elements as $element) {
            if (is_object($element) && method_exists($element, 'getParagraph') && $element->getParagraph()) {
                $paragraph = $element->getParagraph();
                $pelems = $paragraph->getElements() ?? [];
                foreach ($pelems as $pe) {
                    if (is_object($pe) && method_exists($pe, 'getTextRun') && $pe->getTextRun()) {
                        $run = $pe->getTextRun();
                        $content = method_exists($run, 'getContent') ? $run->getContent() : ($run->content ?? '');
                        $text .= $content;
                    } elseif (is_array($pe) && isset($pe['textRun']['content'])) {
                        $text .= $pe['textRun']['content'];
                    }
                }
                $text .= "\n";
            } elseif (is_array($element) && isset($element['paragraph'])) {
                $pelems = $element['paragraph']['elements'] ?? [];
                foreach ($pelems as $pe) {
                    if (isset($pe['textRun']['content'])) $text .= $pe['textRun']['content'];
                }
                $text .= "\n";
            } else {
                // if there's nested structural elements (table/tableOfContents) and we wanted to recurse,
                // we'd handle it here — per requirement we skip tables & TOC.
            }
        }

        return $text;
    }
}
