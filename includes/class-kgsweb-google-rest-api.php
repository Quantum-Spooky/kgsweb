<?php
// includes/class-kgsweb-google-rest-api.php
if (!defined('ABSPATH')) { exit; }

class KGSweb_Google_REST_API {

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
    }

    public static function register_rest_routes() {
        $ns = 'kgsweb/v1';

        // ------------------------
        // Ticker
        // ------------------------
        register_rest_route($ns, '/ticker', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'handle_ticker_request'],
            'args'     => [
                'folder' => ['type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field'],
                'file'   => ['type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field'],
            ],
            'permission_callback' => '__return_true',
        ]);

        // ------------------------
        // Calendar Events 
        // ------------------------
		// Register unified REST endpoint for upcoming events
		register_rest_route('kgsweb/v1', '/events', [
			'methods'  => 'GET',
			'callback' => [ new KGSweb_Google_Upcoming_Events(), 'rest_calendar_events' ],
			'args'     => [
				'calendar_id' => [
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'page' => [
					'type'    => 'integer',
					'required'=> false,
					'default' => 1,
				],
				'per_page' => [
					'type'    => 'integer',
					'required'=> false,
					'default' => 10,
				],
			],
			'permission_callback' => '__return_true',
		]);


        // ------------------------
        // Image Display Feature (Menus, etc)
        // ------------------------
		register_rest_route($ns, '/display', [
			'methods'  => 'GET',
			'callback' => [__CLASS__, 'get_display'],
			'args'     => [
				'type' => [
					'type'        => 'string',
					'required'    => false,
					'enum'        => array_keys(KGSweb_Google_Display::$types),
					'sanitize_callback' => 'sanitize_text_field',
				],
				'folder' => [
					'type'        => 'string',
					'required'    => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			'permission_callback' => '__return_true',
		]);

        // ------------------------
        // Documents (Google Drive)
        // ------------------------
        register_rest_route($ns, '/documents', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_documents'],
            'args' => [
                'root' => [
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            'permission_callback' => '__return_true',
        ]);

		
		// ------------------------
        // User-Accessible Cache Refresh 
        // ------------------------
		register_rest_route('kgsweb/v1', '/cache-refresh', [
			'methods' => 'POST',
			'callback' => [__CLASS__, 'kgsweb_refresh_cache_endpoint'],
			'permission_callback' => '__return_true',
		]);

        // ------------------------
        // Slides
        // ------------------------
        register_rest_route($ns, '/slides', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'get_slides'],
            'args'     => [
                'file_id' => ['type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field'],
            ],
            'permission_callback' => '__return_true',
        ]);

        // ------------------------
        // Sheets
        // ------------------------
        register_rest_route($ns, '/sheets', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'get_sheets'],
            'args'     => [
                'sheet_id' => ['type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field'],
                'range'    => ['type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field'],
            ],
            'permission_callback' => '__return_true',
        ]);

        // ------------------------
        // Upload
        // ------------------------
		register_rest_route('kgsweb/v1', '/upload', [
			'methods'  => 'POST',
			'callback' => [__CLASS__, 'upload'],
			'permission_callback' => '__return_true'
		]);

		register_rest_route('kgsweb/v1', '/upload-folders', [
			'methods'  => 'GET',
			'callback' => [__CLASS__, 'get_upload_folders'],
			'permission_callback' => '__return_true'
		]);
		
    }


	
	
	public static function handle_ticker_request(WP_REST_Request $request) {
		$folder = sanitize_text_field($request->get_param('folder') ?: '');
		$file   = sanitize_text_field($request->get_param('file') ?: '');

		// fetch cached ticker
		$text = KGSweb_Google_Ticker::get_cached_ticker($folder, $file);

		// If there's no ticker content, try to rebuild the ticker cache (if supported)
		if (!$text && method_exists('KGSweb_Google_Ticker', 'refresh_cache_cron')) {
			// Use refresh logic to get a fresh version
			KGSweb_Google_Ticker::refresh_cache_cron();
			$text = KGSweb_Google_Ticker::get_cached_ticker($folder, $file);
		}

		if (!$text || trim($text) === '') {
			// Log for debugging
			error_log("KGSWEB REST Ticker: Empty after rebuild (folder={$folder}, file={$file})");
			return rest_ensure_response([
				'success' => false,
				'ticker'  => '',
			]);
		}

		return rest_ensure_response([
			'success' => true,
			'ticker'  => $text,
		]);
	}


    // ------------------------
    // Other callbacks
    // ------------------------
    public static function get_events(WP_REST_Request $req) {
        $calendar_id = $req->get_param('calendar_id');
        $page        = max(1, intval($req->get_param('page')));
        $per         = max(1, intval($req->get_param('per_page')));
        $data        = KGSweb_Google_Upcoming_Events::get_events_payload($calendar_id, $page, $per);

        if (is_wp_error($data)) {
            return $data;
        }
        return rest_ensure_response($data);
    }

	public static function get_display(WP_REST_Request $req) {
		$type   = $req->get_param('type');
		$folder = $req->get_param('folder');

		// Fallback: resolve folder from type if not explicitly provided
		if (empty($folder)) {
			$type_map = KGSweb_Google_Display::$types;
			if (!empty($type) && isset($type_map[$type])) {
				$folder = get_option($type_map[$type]);
			}
		}

		if (empty($folder)) {
			return new WP_Error('no_folder', 'No folder specified for display type.', ['status' => 400]);
		}

		// Call the display class with both arguments
		$data = KGSweb_Google_Display::get_display_payload($type, $folder);

		if (is_wp_error($data)) {
			return $data;
		}

		return rest_ensure_response($data);
	}



	
	public static function get_documents(WP_REST_Request $req) {
		$folder_id = $req->get_param('root') ?? KGSweb_Google_Drive_Docs::get_public_root_id();                                
		$drive = KGSweb_Google_Integration::init()->get_drive();

		if (!$drive) {
			return new WP_Error('no_drive', 'Google Drive client not initialized.', ['status'=>500]);
		}

		$payload = $drive->get_documents_tree_payload($folder_id);

		if (is_wp_error($payload)) return $payload;

		// Deduplicate tree
		$tree = $payload['tree'] ?? [];
		$tree = self::deduplicate_nodes($tree);

		// Normalize empty children
		$tree = self::normalize_empty_children($tree);

		// Replace payload tree
		$payload['tree'] = $tree;

		return rest_ensure_response($payload);
	}

	private static function deduplicate_nodes(array $nodes): array {
		$seen = [];
		$result = [];

		foreach ($nodes as $node) {
			if (!isset($node['id']) || isset($seen[$node['id']])) {
				continue;
			}
			$seen[$node['id']] = true;
			if (!empty($node['children']) && is_array($node['children'])) {
				$node['children'] = self::deduplicate_nodes($node['children']);
			}
			$result[] = $node;
		}

		return $result;
	}
	
	
	
	
	public static function upload($request) {
        // Stub
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Upload not implemented'
        ], 200);
    }

    public static function get_upload_folders($request) {
        // Stub
        return new WP_REST_Response([
            'folders' => []
        ], 200);
    }



    private static function normalize_empty_children(array $nodes): array {
        foreach ($nodes as &$node) {
            if ($node['type'] === 'folder') {
                if (empty($node['children'])) {
                    $node['children'] = null;
                } else {
                    $node['children'] = self::normalize_empty_children($node['children']);
                }
            }
        }
        return $nodes;
    }

    public static function get_slides(WP_REST_Request $req) {
        $file_id = $req->get_param('file_id');
        if (!$file_id) {
            return new WP_Error('missing_file', 'Slide file ID is required.', ['status' => 400]);
        }

        $cache_key = "kgsweb_cache_slides_{$file_id}";
        $data = get_transient($cache_key);

        if ($data === false) {
            $data = [
                'file_id'   => $file_id,
                'embed_url' => "https://docs.google.com/presentation/d/{$file_id}/embed",
                'message'   => 'Slides fetched or placeholder',
            ];
            set_transient($cache_key, $data, HOUR_IN_SECONDS);
        }

        return rest_ensure_response($data);
    }

    public static function get_sheets(WP_REST_Request $req) {
        $sheet_id = $req->get_param('sheet_id');
        $range    = $req->get_param('range') ?: (KGSweb_Google_Integration::get_settings()['sheets_default_range'] ?? 'A1:Z100');

        if (!$sheet_id) {
            return new WP_Error('missing_sheet', 'Sheet ID is required.', ['status' => 400]);
        }

        $cache_key = "kgsweb_cache_sheet_{$sheet_id}_" . md5($range);
        $data = get_transient($cache_key);

        if ($data === false) {
            $data = [
                'sheet_id' => $sheet_id,
                'range'    => $range,
                'rows'     => [],
                'headers'  => [],
                'message'  => 'Sheet fetched or placeholder',
            ];
            set_transient($cache_key, $data, HOUR_IN_SECONDS);
        }

        return rest_ensure_response($data);
    }
	
	// ------------------------------------------------
	// User-Accessible Cache Refresh 
	// ------------------------------------------------
		
		public static function kgsweb_refresh_cache_endpoint(WP_REST_Request $request) {
			$secret = $request->get_param('secret');
			$expected_secret = get_option('kgsweb_cache_refresh_secret');

			if (!$secret || $secret !== $expected_secret) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Invalid secret.'
				], 403);
			}

			// Rate limiting: 1 call per minute per IP
			$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
			$last_call = get_transient('kgsweb_cache_refresh_' . md5($ip));
			if ($last_call) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Rate limit exceeded. Try again later.'
				], 429);
			}
			set_transient('kgsweb_cache_refresh_' . md5($ip), time(), MINUTE_IN_SECONDS);

			// Call the integration class
			if (class_exists('KGSweb_Google_Integration')) {
				$integration = KGSweb_Google_Integration::init();
				$integration->cron_refresh_all_caches();
				error_log('KGSWEB: Global cache refreshed via REST endpoint.');
			}

			return new WP_REST_Response([
				'success' => true,
				'message' => 'Cache refreshed successfully.'
			]);
		}
}
