<?php
// includes/class-kgsweb-google-secure-upload.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Secure_Upload {
    private static $allowed_ext = ['txt','rtf','pdf','doc','docx','ppt','pptx','ppsx','xls','xlsx','csv','png','jpg','jpeg','gif','webp','mp3','wav','mp4','m4v','mov','avi'];
    private static $video_ext   = ['mp4','m4v','mov','avi'];

    public static function init() { /* shortcodes/REST already hooked elsewhere */ }
	
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
	 * Cron Refresh
	 *******************************/
	public static function refresh_cache_cron() {
		$integration = KGSweb_Google_Integration::init();


		// Rebuild upload folder list
		self::rebuild_upload_tree_cache(self::get_upload_root_id());

	}
	
	/*******************************
     * REST
     *******************************/	

    public static function handle_upload_rest( WP_REST_Request $req ) {
        $settings = KGSweb_Google_Integration::get_settings();
	    // 🔐 Password validation (if using password auth)
		if ( $settings['upload_auth_mode'] === 'password' ) {
			$submitted = sanitize_text_field( $req->get_param( 'password' ) ?? '' );
			$expected_hash = $settings['upload_password_hash'] ?? '';
			$key = defined( 'KGSWEB_PASSWORD_SECRET_KEY' ) ? KGSWEB_PASSWORD_SECRET_KEY : '';
		if ( $settings['upload_auth_mode'] === 'google_group' ) {
			// TODO: verify_google_group( $request->get_param('email') )
		}
			if ( ! $key || ! hash_equals( $expected_hash, hash_hmac( 'sha256', $submitted, $key ) ) ) {
				return new WP_Error( 'invalid_password', 'Incorrect upload password.' );
			}
		}
		$folder_id = sanitize_text_field($req->get_param( 'upload-folder' ) ?? $req->get_param( 'folder_id' )
	);

        // Auth gate: password or google_group
        $auth_mode = $settings['upload_auth_mode'] ?? 'password';
        $ip_key = self::lockout_key();
        if ( self::is_locked_out( $ip_key ) ) {
            return new WP_Error( 'upload_locked', __( 'Too many failed attempts. Try again later.', 'kgsweb' ), [ 'status'=>403 ] );
        }

        $authorized = false;
        if ( $auth_mode === 'password' ) {
            $password = $req->get_param( 'password' ); // expected in body (not in shortcode shell)
            $authorized = self::verify_password( $password );
            if ( ! $authorized ) self::register_failed_attempt( $ip_key );
        } else {
            $authorized = self::verify_google_group(); // TODO: implement (OAuth / domain membership)
            if ( ! $authorized ) self::register_failed_attempt( $ip_key );
        }
        if ( ! $authorized ) {
            return new WP_Error( 'unauthorized', __( 'Not authorized to upload.', 'kgsweb' ), [ 'status'=>401 ] );
        }

        // Validate file
        if ( empty( $_FILES['file'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
            return new WP_Error( 'no_file', __( 'No file uploaded.', 'kgsweb' ), [ 'status'=>400 ] );
        }
        $file = $_FILES['file'];
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, self::$allowed_ext, true ) ) {
            return new WP_Error( 'bad_ext', __( 'File type not allowed.', 'kgsweb' ), [ 'status'=>400 ] );
        }
        $max = in_array( $ext, self::$video_ext, true ) ? 500 * 1024 * 1024 : 100 * 1024 * 1024;
        if ( $file['size'] > $max ) {
            return new WP_Error( 'too_large', __( 'File exceeds size limit.', 'kgsweb' ), [ 'status'=>400 ] );
        }

        // Validate folder_id against cached tree (upload root)
        if ( ! self::folder_exists_in_upload_tree( $folder_id ) ) {
            return new WP_Error( 'bad_folder', __( 'Invalid destination folder.', 'kgsweb' ), [ 'status'=>404 ] );
        }

        // Destination
        $dest = $settings['upload_destination'] ?? 'drive';
        if ( $dest === 'wordpress' ) {
            $result = self::upload_to_wordpress( $folder_id, $file );
        } else {
            $result = self::upload_to_drive( $folder_id, $file );
        }
        if ( is_wp_error( $result ) ) return $result;

        return [
            'success'      => true,
            'destination'  => $dest,
            'file'         => $result,
            'message'      => __( 'Upload successful.', 'kgsweb' ),
        ];
    }

	/*******************************
     * Upload to Wordpress (workaround)
     *******************************/
	 
    private static function upload_to_wordpress( $folder_id, $file ) {
        // Create subdir path based on Drive folder tree label (safe)
        $root = KGSweb_Google_Integration::get_settings()['wp_upload_root_path'] ?? '';
        if ( empty( $root ) ) $root = wp_upload_dir()['basedir'].'/kgsweb';
        $subpath = self::folder_path_from_id( $folder_id ); // e.g., "Grade 4/Newsletters"
        $destdir = trailingslashit( $root ) . $subpath;
        wp_mkdir_p( $destdir );
        $san = KGSweb_Google_Helpers::sanitize_filename( $file['name'] );
        $dest = trailingslashit( $destdir ) . $san;
        if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
            return new WP_Error( 'move_failed', __( 'Could not store file.', 'kgsweb' ), [ 'status'=>409 ] );
        }
        // Optionally insert as attachment
        return [
            'path' => $dest,
            'url'  => str_replace( wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $dest ),
        ];
    }

	/*******************************
     * Upload to Google Drive
     *******************************/
	 
    private static function upload_to_drive( $folder_id, $file ) {
        // TODO: Use Drive API to upload to the given folder_id within a Shared Drive
        // Return { id, name, webViewLink, webContentLink } as available
        return new WP_Error( 'todo', __( 'Drive upload not yet implemented.', 'kgsweb' ), [ 'status'=>409 ] );
    }

	/*******************************
     * Verify Permissions
     *******************************/
	 
    private static function verify_password( $password ) {
        if ( ! defined( 'KGSWEB_UPLOAD_PASS_HASH' ) || empty( $password ) ) return false;
        // Expected format: "algo:hash"
        $parts = explode( ':', KGSWEB_UPLOAD_PASS_HASH, 2 );
        if ( count( $parts ) !== 2 ) return false;
        list( $algo, $hash ) = $parts;
        $calc = hash( $algo, $password );
        return hash_equals( $hash, $calc );
    }

    private static function verify_google_group() {
        // TODO: Implement Google Sign-In flow + group membership check
        return false;
    }

    private static function lockout_key() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user = get_current_user_id();
        return 'ip:'.$ip.'|u:'.$user;
    }

    private static function is_locked_out( $key ) {
        $map = get_option( 'kgsweb_upload_lockouts', [] );
        if ( empty( $map[$key] ) ) return false;
        return time() < intval( $map[$key] );
    }

    private static function register_failed_attempt( $key ) {
        $count = (int) get_transient( 'kgsweb_attempts_'.$key );
        $count++;
        set_transient( 'kgsweb_attempts_'.$key, $count, DAY_IN_SECONDS );
        if ( $count >= 50 ) {
            $map = get_option( 'kgsweb_upload_lockouts', [] );
            $map[$key] = time() + DAY_IN_SECONDS;
            update_option( 'kgsweb_upload_lockouts', $map, false );
            delete_transient( 'kgsweb_attempts_'.$key );
        }
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
	
	
		public static function rebuild_upload_tree_cache($root) {
        if (empty($root)) return;

        $tree = self::build_folders_only_tree($root);
        KGSweb_Google_Integration::set_transient('kgsweb_cache_upload_tree_' . $root, $tree, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_uploadtree_' . $root, current_time('timestamp'));
    }
	
	
	
}