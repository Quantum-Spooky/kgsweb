<?php
// includes/class-kgsweb-google-secure-upload.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;

class KGSweb_Google_Secure_Upload {

    /************ Constants ************/
    const NONCE_ACTION = 'kgsweb_secure_upload';
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minutes
    private static $allowed_ext = ['txt','rtf','pdf','doc','docx','ppt','pptx','ppsx','xls','xlsx','csv','png','jpg','jpeg','gif','webp','mp3','wav','mp4','m4v','mov','avi'];
    private static $video_ext   = ['mp4','m4v','mov','avi'];

    /************ Init ************/
    public static function init() {
        add_shortcode('kgsweb_secure_upload', [__CLASS__, 'render_shortcode']);
        add_action('rest_api_init', [__CLASS__, 'register_rest']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('init', [__CLASS__, 'handle_form']);
		add_action('rest_api_init', function() { self::register_rest(); });
	}

   /************ Shortcode ************/
		
		public static function render_shortcode($atts) {
		$atts = shortcode_atts(['upload-folder' => self::get_upload_root_id()], $atts, 'kgsweb_secure_upload');
		$folder_id = sanitize_text_field($atts['upload-folder']);

		$show_password_form = !self::is_user_authorized();

		ob_start();
		echo '<div class="kgsweb-upload" data-folder="' . esc_attr($folder_id) . '">';

		// Form wrapper
		echo '<form method="post" enctype="multipart/form-data" class="kgsweb-upload-form" data-upload-root="' . esc_attr($folder_id) . '">';
		wp_nonce_field(self::NONCE_ACTION, '_wpnonce');

		// --- Authentication Options ---
		echo '<div class="kgsweb-auth-options">';

		// Password Option
		echo '<div class="password-option" style="' . ($show_password_form ? '' : 'display:none;') . '">';
		echo '<input type="password" name="kgsweb_pass" class="kgsweb-pass-input" placeholder="Password">';
		echo '<button type="button" class="kgsweb-pass-submit">Submit</button>';
		echo '<span class="kgsweb-pass-message" style="margin-left:0.5rem;color:red;"></span>';
		echo '</div>';

		// Google Option
		echo '<div class="google-option">';
		echo '<button type="button" class="kgsweb-google-login">Sign in with Google</button>';
		echo '<span class="kgsweb-google-message" style="margin-left:0.5rem;color:red;"></span>';
		echo '</div>';

		echo '</div>'; // end .kgsweb-auth-options

		// --- Upload Fields (hidden until auth succeeds) ---
		echo '<div class="kgsweb-upload-fields" style="' . ($show_password_form ? 'display:none;' : '') . '">';

		echo '<label style="margin:0;">Choose File: <input type="file" name="file" required></label>';

		echo '<label style="margin:0;">Destination Folder: <select name="folder_id" class="kgsweb-folder-select">';
		echo '<option value="' . esc_attr($folder_id) . '">Loading folders…</option>';
		echo '</select></label>';

		echo '<button type="submit" name="kgsweb_upload_submit">Upload</button>';
		echo '<div class="kgsweb-upload-status" style="flex-basis:100%;"></div>';

		echo '</div>'; // end .kgsweb-upload-fields

		echo '</form>';
		echo '</div>';

		return ob_get_clean();
	}


    /************ Auth ************/
    private static function is_user_authorized() {
        return isset($_SESSION['kgsweb_upload_auth']) && $_SESSION['kgsweb_upload_auth'] === true;
    }

    private static function lockout_key() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'kgsweb_lockout_' . md5($ip);
    }

    private static function check_password($pass) {
        $stored_hash = get_option('kgsweb_upload_pass_hash', '');
        if (!$stored_hash || !defined('KGSWEB_PASSWORD_SECRET_KEY')) return false;
        $check = hash_hmac('sha256', $pass, KGSWEB_PASSWORD_SECRET_KEY);
        return hash_equals($stored_hash, $check);
    }

    private static function register_failed_attempt($key) {
        $count = (int) get_transient('kgsweb_attempts_' . $key) + 1;
        set_transient('kgsweb_attempts_' . $key, $count, self::LOCKOUT_TIME);
        if ($count >= self::MAX_ATTEMPTS) {
            set_transient($key, true, self::LOCKOUT_TIME);
            delete_transient('kgsweb_attempts_' . $key);
        }
    }

    private static function is_locked_out($key) {
        return get_transient($key) ? true : false;
    }

    /************ Form Handling ************/
    public static function handle_form() {
       

        // Password submission
        if (isset($_POST['kgsweb_pass_submit'])) {
            check_admin_referer(self::NONCE_ACTION);
            $lock_key = self::lockout_key();
            if (self::is_locked_out($lock_key)) wp_die("Too many attempts. Try again later.");

            $pass = sanitize_text_field($_POST['kgsweb_pass'] ?? '');
            if (self::check_password($pass)) {
                $_SESSION['kgsweb_upload_auth'] = true;
                delete_transient($lock_key);
                wp_safe_redirect($_SERVER['REQUEST_URI']);
                exit;
            } else {
                self::register_failed_attempt($lock_key);
                wp_die("Invalid password.");
            }
        }

        // File upload via form
        if (isset($_POST['kgsweb_upload_submit'])) {
            check_admin_referer(self::NONCE_ACTION);
            if (!self::is_user_authorized()) wp_die("Unauthorized upload attempt.");
            if (empty($_FILES['file']['name'])) wp_die("No file selected.");

            $file = $_FILES['file'];
            $folder_id = sanitize_text_field($_POST['folder_id'] ?? '');
            if (!$folder_id || !self::folder_exists_in_upload_tree($folder_id)) wp_die("Invalid folder.");
            if (!self::validate_file($file)) wp_die("Invalid file type or size.");

            $result = self::upload_to_drive($folder_id, $file);

            // Fallback to WordPress if Drive upload fails
            if (is_wp_error($result)) {
                $result = self::upload_to_wordpress($folder_id, $file);
                if (is_wp_error($result)) wp_die($result->get_error_message());
                $result['link'] = $result['url'] ?? '';
            }

            echo "<div class='upload-success'>Upload successful: <a href='" . esc_url($result['link']) . "' target='_blank'>" . esc_html($result['name']) . "</a></div>";
        }
    }

    /************ File Validation ************/
    private static function validate_file($file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = in_array($ext, self::$video_ext, true) ? 500 * 1024 * 1024 : 100 * 1024 * 1024;
        return in_array($ext, self::$allowed_ext, true) && $file['size'] <= $max_size;
    }

    /************ Google Drive Upload ************/
    private static function upload_to_drive($folder_id, $file) {
        try {
            $client = KGSweb_Google_Helpers::get_google_client();
            $drive  = new Drive($client);

            $fileMetadata = new Drive\DriveFile([
                'name' => $file['name'],
                'parents' => [$folder_id]
            ]);

            $content = file_get_contents($file['tmp_name']);
            $uploadedFile = $drive->files->create($fileMetadata, [
                'data' => $content,
                'uploadType' => 'multipart',
                'fields' => 'id,name,webViewLink'
            ]);

            return [
                'id'   => $uploadedFile->id,
                'name' => $uploadedFile->name,
                'link' => $uploadedFile->webViewLink
            ];
        } catch (Exception $e) {
            return new WP_Error('upload_failed', $e->getMessage(), ['status'=>500]);
        }
    }

    /************ WordPress Upload Fallback ************/
    private static function upload_to_wordpress($folder_id, $file) {
        $root = KGSweb_Google_Integration::get_settings()['wp_upload_root_path'] ?? wp_upload_dir()['basedir'].'/kgsweb';
        $subpath = sanitize_title($folder_id);
        $destdir = trailingslashit($root) . $subpath;
        wp_mkdir_p($destdir);
        $san = KGSweb_Google_Helpers::sanitize_filename($file['name']);
        $dest = trailingslashit($destdir) . $san;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return new WP_Error('move_failed', 'Could not store file.', ['status'=>409]);
        }
        return [
            'path' => $dest,
            'name' => $file['name'],
            'url'  => str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $dest)
        ];
    }

    /************ Upload Tree Helpers ************/
    public static function folder_exists_in_upload_tree($folder_id) {
        $root = self::get_upload_root_id();
        $tree = get_transient('kgsweb_cache_upload_tree_' . $root);
        if ($tree === false) self::rebuild_upload_tree_cache($root);
        $tree = get_transient('kgsweb_cache_upload_tree_' . $root);
        return self::search_tree_for_id($tree, $folder_id);
    }

    private static function search_tree_for_id($nodes, $id) {
        foreach ((array)$nodes as $n) {
            if (($n['id'] ?? '') === $id) return true;
            if (!empty($n['children']) && self::search_tree_for_id($n['children'], $id)) return true;
        }
        return false;
    }

    private static function rebuild_upload_tree_cache($root_id) {
        if (empty($root_id)) return;
        $tree = self::build_folders_only_tree($root_id);
        KGSweb_Google_Integration::set_transient('kgsweb_cache_upload_tree_' . $root_id, $tree, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_uploadtree_' . $root_id, current_time('timestamp'));
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
                'pageSize' => 1000
            ];
            $pageToken = null;
            do {
                if ($pageToken) $params['pageToken'] = $pageToken;
                $response = $service->files->listFiles($params);
                foreach ($response->getFiles() as $f) {
                    $folders[] = [
                        'id' => $f->getId(),
                        'name' => $f->getName(),
                        'children' => $fetch_folders($f->getId())
                    ];
                }
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);
            return $folders;
        };

        return [
            [
                'id' => $root_id,
                'name' => 'Root',
                'children' => $fetch_folders($root_id)
            ]
        ];
    }

    public static function get_upload_root_id() {
        return KGSweb_Google_Integration::get_settings()['upload_root_id'] ?? '';
    }

	 /************ REST API ************/
		public static function register_rest() {
			register_rest_route('kgsweb/v1','/upload-folders',[
				'methods'             => 'GET',
				'callback'            => [__CLASS__,'rest_upload_folders'],
				'permission_callback' => '__return_true'
			]);

			register_rest_route('kgsweb/v1', '/upload-check', [
				'methods'             => 'POST',
				'callback'            => [__CLASS__, 'rest_check_password'],
				//'permission_callback' => '__return_true', // ✅ bypass WP core cookie/nonce check
				'permission_callback' => function() {
						return true;
					},
			]);

			register_rest_route('kgsweb/v1','/upload',[
				'methods'             => 'POST',
				'callback'            => [__CLASS__,'rest_upload'],
				'permission_callback' => '__return_true'
			]);
		}

    public static function rest_upload_folders($request) {
        $root = sanitize_text_field($request['root'] ?? self::get_upload_root_id());
        $tree = get_transient('kgsweb_cache_upload_tree_' . $root);
        if (!$tree) $tree = self::build_folders_only_tree($root);
        return rest_ensure_response($tree);
    }

	 public static function rest_check_password($request) {
        error_log('rest_check_password hit!'); // ✅ debugging

        $nonce = sanitize_text_field($request->get_param('nonce') ?? '');
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return new WP_Error(
                'invalid_nonce',
                'Invalid nonce',
                ['status' => 403]
            );
        }

        // Lockout check
        $lock_key = self::lockout_key();
        if (self::is_locked_out($lock_key)) {
            return new WP_Error(
                'locked_out',
                'Too many attempts. Try again later.',
                ['status' => 403]
            );
        }

        $pass        = sanitize_text_field($request->get_param('password'));
        $googleToken = sanitize_text_field($request->get_param('google_token'));
        $is_valid    = false;

        if ($pass) {
            $is_valid = self::check_password($pass);
        } elseif ($googleToken) {
            $is_valid = self::check_google_token($googleToken); // implement token validation
        }

        if ($is_valid) {
            $_SESSION['kgsweb_upload_auth'] = true;
            delete_transient($lock_key);
            return ['success' => true];
        } else {
            self::register_failed_attempt($lock_key);
            return new WP_Error(
                'invalid_password',
                'Invalid password or Google login.',
                ['status' => 403]
            );
        }
    }




    public static function rest_upload($request) {
        $folder_id = sanitize_text_field($request->get_param('folder_id'));
        if (!$folder_id || !self::folder_exists_in_upload_tree($folder_id)) return new WP_Error('invalid_folder','Invalid folder',['status'=>400]);
        if (empty($_FILES['file'])) return new WP_Error('no_file','No file uploaded',['status'=>400]);
        $file = $_FILES['file'];
        if (!self::validate_file($file)) return new WP_Error('invalid_file','Invalid file or size',['status'=>400]);

        $result = self::upload_to_drive($folder_id, $file);
        if (is_wp_error($result)) $result = self::upload_to_wordpress($folder_id, $file);

        return $result;
    }

    /************ JS / CSS ************/
    public static function enqueue_assets() {
        wp_enqueue_script('kgsweb-upload', plugins_url('../js/kgsweb-upload.js', __FILE__), ['jquery'], '1.0', true);
        wp_localize_script('kgsweb-upload','KGSwebUpload',[
			'restFoldersUrl' => esc_url(rest_url('kgsweb/v1/upload-folders')),
			'restCheckUrl'   => esc_url(rest_url('kgsweb/v1/upload-check')),
			'restUploadUrl'  => esc_url(rest_url('kgsweb/v1/upload')),
			'nonce'=>wp_create_nonce(self::NONCE_ACTION),
			'folderSelect'=>'.kgsweb-folder-select'
		]);
    }
}

KGSweb_Google_Secure_Upload::init();
