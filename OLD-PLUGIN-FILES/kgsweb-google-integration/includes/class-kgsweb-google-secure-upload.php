<?php 
// includes/class-kgsweb-google-secure-upload.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile; 

// Init early
add_action('init', function () {
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
	if (!isset($_SESSION['kgsweb_origin_url']) && !is_admin() && !defined('DOING_AJAX')) {
		$_SESSION['kgsweb_origin_url'] = esc_url_raw($_SERVER['REQUEST_URI'] ?? '/');
	}
}, 0);

	// Single, safe Nextend redirect filter
	add_filter('nsl_redirect_url', function($url, $provider) {
		$redirect = $_SESSION['kgsweb_origin_url'] ?? $url;
		unset($_SESSION['kgsweb_origin_url']);
		return $redirect;
	}, 1, 2); // priority 1 to run before defaults

	// Logout cleanup
	add_action('wp_logout', function() {
		// KGSweb_Google_Helpers::start(); // REMOVED
		unset($_SESSION[KGSweb_Google_Secure_Upload::SESSION_KEY]);
		setcookie('kgsweb_group_auth_verified', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
	});

class KGSweb_Google_Secure_Upload {

    const SESSION_KEY = 'kgsweb_upload_verified';
    const SESSION_EXPIRY = 600; // 10 minutes

	private static function is_verified() {
		// KGSweb_Google_Helpers::start(); // REMOVED

		// --- Bypass: Logged-in WP user in allowed Google group ---
		if (self::is_group_auth_enabled() && is_user_logged_in()) {
			$email = self::get_current_user_email();
			if ($email && self::user_in_allowed_groups($email)) {
				$_SESSION[self::SESSION_KEY] = time();
				return true;
			}
		}
		// ----------------------------------------------------------

		if (!empty($_SESSION[self::SESSION_KEY]) && (time() - $_SESSION[self::SESSION_KEY]) < self::SESSION_EXPIRY) {
			return true;
		}

		// Only treat Google OAuth as verification when group auth is allowed and user is in allowed groups.
		if (self::is_google_oauth_logged_in() && self::is_group_auth_enabled() && self::user_in_allowed_groups(self::get_current_user_email())) {
			$_SESSION[self::SESSION_KEY] = time();
			return true;
		}

		return false;
	}

	public static function init() {
		// Standard initialization to process uploads if needed
		add_action('init', [__CLASS__, 'check_for_upload_and_process']);

		/**
		 * Password authentication route (non-logged-in users)
		 * Users not logged in must submit a password to gain access.
		 */
		add_action('wp_ajax_nopriv_kgsweb_check_upload_password', [__CLASS__, 'ajax_check_password']);
		add_action('wp_ajax_nopriv_kgsweb_handle_upload', [__CLASS__, 'ajax_handle_upload']);
		add_action('wp_ajax_nopriv_kgsweb_check_password', [__CLASS__, 'ajax_check_password']);
		

		/**
		 * Google-group authentication route (logged-in users)
		 * Users in an authorized Google group bypass the password form.
		 */
		add_action('wp_ajax_kgsweb_check_upload_password', [__CLASS__, 'ajax_check_password']);
		add_action('wp_ajax_kgsweb_handle_upload', [__CLASS__, 'ajax_handle_upload']);
		add_action('wp_ajax_kgsweb_check_password', [__CLASS__, 'ajax_check_password']);
		add_action('wp_ajax_kgsweb_get_cached_folders', [__CLASS__, 'ajax_get_cached_folders']);
		add_action('wp_ajax_kgsweb_check_group', [__CLASS__, 'ajax_check_group']);
		add_action('wp_ajax_nopriv_kgsweb_check_group', [__CLASS__, 'ajax_check_group']);

		/**
		 * Backward-compatible AJAX hooks for legacy front-end calls
		 */
		add_action('wp_ajax_kgsweb_secure_upload', [__CLASS__, 'ajax_handle_upload']);
		add_action('wp_ajax_nopriv_kgsweb_secure_upload', [__CLASS__, 'ajax_handle_upload']);
		add_action('wp_ajax_nopriv_kgsweb_get_cached_folders', [__CLASS__, 'ajax_get_cached_folders']);
	}


    /*******************************
     * Options helpers / migration
     *******************************/
    private static function is_password_auth_enabled(): bool {
        $flag = get_option('kgsweb_allow_password_auth', null);
        if ($flag !== null) return (bool) $flag;
        // migrate from legacy upload_auth_mode
        $mode = get_option('kgsweb_upload_auth_mode', '');
        return $mode === 'password' || $mode === 'both';
    }

    private static function is_group_auth_enabled(): bool {
        $flag = get_option('kgsweb_allow_group_auth', null);
        if ($flag !== null) return (bool) $flag;
        // migrate from legacy upload_auth_mode
        $mode = get_option('kgsweb_upload_auth_mode', '');
        return $mode === 'google_group' || $mode === 'both';
    }

    /*******************************
     * Helpers: user email and group membership
     *
     * Note: This checks allowed list configured in
     * 'kgsweb_upload_google_groups'. That option may contain:
     * - explicit email addresses (user@example.com)
     * - group emails (group@example.com) [treated as literal match]
     * - domain wildcards like @example.com (allow whole domain)
     *
     * Domain wildcard and explicit email matching are supported.
     * A robust Directory API check would require domain-wide delegation.
     *******************************/
    private static function get_current_user_email(): string {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (!empty($user->user_email)) return strtolower(trim($user->user_email));
            // Attempt Nextend Social Login store if present
            $meta = get_user_meta($user->ID, 'nsl_current_user_id', true);
            if (!empty($meta['email'])) return strtolower(trim($meta['email']));
        }
        return '';
    }

    private static function user_in_allowed_groups(string $email): bool {
		if (empty($email)) return false;

		$allowedGroupsRaw = get_option('kgsweb_upload_google_groups', []);
		if (!is_array($allowedGroupsRaw)) {
			$allowedGroupsRaw = array_filter(array_map('trim', explode(',', (string)$allowedGroupsRaw)));
		}

		$email = strtolower(trim($email));
		
		
				// --- Admin SDK unavailable: manual whitelist fallback - Always run first ---
					$allowed_manual_raw = get_option('kgsweb_manual_upload_whitelist', []);
					if (!is_array($allowed_manual_raw)) {
						$allowed_manual_raw = array_filter(array_map('trim', explode(',', (string)$allowed_manual_raw)));
					}
					if (in_array($email, array_map('strtolower', $allowed_manual_raw), true)) {
						return true;
					}
		// ----------------------------------------------------------
		
		

		// Basic literal and domain wildcard checks first
		foreach ($allowedGroupsRaw as $entry) {
			$entry = strtolower(trim($entry));
			if ($entry === '') continue;

			if ($entry === $email) return true; // exact
			if (strpos($entry, '@') === 0 && substr($email, -strlen($entry)) === $entry) return true; // domain wildcard
		}

		// --- Directory API check for group membership ---
		try {
			$service_json = get_option('kgsweb_service_account_json');
			if (!$service_json) {
				error_log("KGSWEB: No service account JSON found for group membership check.");
				return false;
			}

			$client = new \Google\Client();
			$client->setAuthConfig(json_decode($service_json, true));
			$client->addScope(\Google\Service\Directory::ADMIN_DIRECTORY_GROUP_MEMBER_READONLY);
			$service = new \Google\Service\Directory($client);

			foreach ($allowedGroupsRaw as $groupEmail) {
				$groupEmail = trim(strtolower($groupEmail));
				if (!str_contains($groupEmail, '@')) continue;

				try {
					$members = $service->members->listMembers($groupEmail)->getMembers();
					if (empty($members)) continue;

					foreach ($members as $member) {
						if (strcasecmp($member->getEmail(), $email) === 0) {
							return true;
						}
					}
				} catch (\Exception $e) {
					error_log("KGSWEB: Error checking group $groupEmail: " . $e->getMessage());
				}
			}
		} catch (\Exception $e) {
			error_log("KGSWEB: Directory API init failed: " . $e->getMessage());
		}

		return false;
	}

    /*******************************
     * AJAX validate password or OAuth login
     *******************************/
	public static function ajax_check_password() {
		// KGSweb_Google_Helpers::start(); // REMOVED

		check_ajax_referer('kgsweb_upload_nonce', 'nonce'); // ajax_check_password()

		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		$lock_key = 'kgsweb_lockout_' . md5($ip);
		$fail_key = 'kgsweb_failures_' . md5($ip);

		// Bypass lockout if logged-in admin
		if (!current_user_can('manage_options')) {
			$locked_until = get_transient($lock_key);
			if ($locked_until && time() < $locked_until) {
				wp_send_json_error(['message' => 'Too many failed attempts. Try again later.']);
			}
		}

		// --- If group auth enabled and WP user is logged in and allowed, accept immediately ---
		if (self::is_group_auth_enabled() && is_user_logged_in()) {
			$email = self::get_current_user_email();
			if ($email && self::user_in_allowed_groups($email)) {
				$_SESSION[self::SESSION_KEY] = time();
				wp_send_json_success(['message' => 'Logged in via WordPress and allowed group membership', 'oauth_logged_in' => false, 'group_member' => true]);
			}
		}
		// -------------------------------------------------------------------------------

		// If group auth is enabled and user is logged in via Google OAuth, verify group membership
		if (self::is_group_auth_enabled() && self::is_google_oauth_logged_in()) {
			$email = self::get_current_user_email();
			if ($email && self::user_in_allowed_groups($email)) {
				$_SESSION[self::SESSION_KEY] = time();
				wp_send_json_success(['message' => 'Logged in via Google OAuth and allowed group membership', 'oauth_logged_in' => true, 'group_member' => true]);
			}
			// If OAuth exists but group auth not satisfied, fall through to password check (if enabled).
		}

		// If password auth is enabled, try password
		if (self::is_password_auth_enabled()) {
			$password = sanitize_text_field($_POST['password'] ?? $_POST['kgsweb_upload_pass'] ?? '');
			if (self::check_password($password)) {
				// KGSweb_Google_Helpers::start(); // REMOVED
				$_SESSION[self::SESSION_KEY] = time();
				delete_transient($fail_key);
				wp_send_json_success(['message' => 'Password verified.', 'oauth_logged_in' => false, 'group_member' => false]);
			}
		}

		// Failure handling (only applied when password auth attempted)
		$failures = (int) get_transient($fail_key);
		$failures++;
		set_transient($fail_key, $failures, HOUR_IN_SECONDS);

		if ($failures >= 50) {
			set_transient($lock_key, time() + HOUR_IN_SECONDS, HOUR_IN_SECONDS);
			wp_send_json_error(['message' => 'Too many failed attempts. Locked for 1 hour.']);
		}

		// Final deny with clear message about allowed methods
		$allowed = [];
		if (self::is_password_auth_enabled()) $allowed[] = 'password';
		if (self::is_group_auth_enabled()) $allowed[] = 'Google group membership';
		$allowed_text = $allowed ? implode(' or ', $allowed) : 'no upload methods configured';
		wp_send_json_error(['message' => 'Invalid credentials or not authorized. Allowed methods: ' . $allowed_text . '.']);
	}


    /*******************************	 
     * Check password (supports secure_upload_options & fallback)
     *******************************/
  
    public static function check_password($input) {
        // Try secure_upload_options first
        $secure_opts = get_option('kgsweb_secure_upload_options', []);

					
        $stored_hash = $secure_opts['upload_password_hash'] ?? '';
        $stored_plain = $secure_opts['upload_password'] ?? '';

        if ($stored_hash && hash('sha256', $input) === $stored_hash) return true;

		
        if ($stored_plain && hash('sha256', $input) === hash('sha256', $stored_plain)) return true;

   

        // Fallback to original CURRENT code
        $fallback_hash = get_option('kgsweb_upload_password_hash', '');

        return $fallback_hash ? hash('sha256', $input) === $fallback_hash : false;
    }

 
    public static function save_password($plain_password) {
        $plain_password = sanitize_text_field($plain_password);

        // Original options
        update_option('kgsweb_upload_password', $plain_password);
        update_option('kgsweb_upload_password_hash', hash('sha256', $plain_password));

        // Suggested secure_upload_options
        $secure_opts = get_option('kgsweb_secure_upload_options', []);
        $secure_opts['upload_password'] = $plain_password;
        $secure_opts['upload_password_hash'] = hash('sha256', $plain_password);
        update_option('kgsweb_secure_upload_options', $secure_opts);
    }

    /*******************************
     * Check if user is logged in via Google OAuth
     *******************************/
	 
	// Implement the Check Group handler
	public static function ajax_check_group() {
		check_ajax_referer('kgsweb_upload_nonce', '_wpnonce');

		$ok = self::bypass_group_auth(); // or whatever logic approves the user
		wp_send_json(['group_ok' => $ok]);
	}

/*     private static function is_google_oauth_logged_in() {
        if (!is_user_logged_in()) return false;
        $user_id = get_current_user_id();
        $meta = get_user_meta($user_id, 'nsl_current_user_id', true); // Nextend Social Login
        return strtolower($meta['provider'] ?? '') === 'google';
		
					$authorized = $this->bypass_group_auth();
					error_log('bypass_group_auth() result: ' . ($authorized ? 'true' : 'false'));
					error_log('SESSION_KEY: ' . ($_SESSION[self::SESSION_KEY] ?? 'none'));
					error_log('COOKIE: ' . ($_COOKIE['kgsweb_group_auth_verified'] ?? 'none'));
    }
*/
	
	private static function is_google_oauth_logged_in() {
		if (!is_user_logged_in()) {
			return false;
		}

		$meta = get_user_meta(get_current_user_id(), 'nsl_user_metadata', true);
		$is_google = strtolower($meta['provider'] ?? '') === 'google';

		return $is_google;
	}

	
	public static function bypass_group_auth(): bool {
		if (!self::is_group_auth_enabled()) return false;

		// Cookie-based short-circuit for verified OAuth users
		if (!empty($_COOKIE['kgsweb_group_auth_verified'])) {
			// KGSweb_Google_Helpers::start(); // REMOVED
			$_SESSION[self::SESSION_KEY] = time();
			return true;
		}

		if (!is_user_logged_in()) return false;
		$email = self::get_current_user_email();
		if (!$email) return false;
		if (self::user_in_allowed_groups($email)) {
			// KGSweb_Google_Helpers::start(); // REMOVED
			$_SESSION[self::SESSION_KEY] = time();
			return true;
		}
		return false;
	}
	
	

		
	


    /*******************************
     * Detect upload requests
     *******************************/
  
    public static function check_for_upload_and_process() {
        if (
            empty($_FILES['kgsweb_upload_file']) && empty($_POST['upload_folder_id']) &&
            empty($_FILES['file']) && empty($_POST['folder_id'])
        ) return;

        $file = $_FILES['kgsweb_upload_file'] ?? $_FILES['file'] ?? null;
        $folder_id = sanitize_text_field($_POST['upload_folder_id'] ?? $_POST['folder_id'] ?? '');

        $result = self::handle_upload($file, $folder_id);

        if (defined('DOING_AJAX') && DOING_AJAX) wp_send_json($result);
		  
   
    }

    /*******************************
     * Handle upload 
     *******************************/
	public static function handle_upload($file, $folder_id) {
		// -----------------------------
		// Added: Initial/final use-case logging
		// -----------------------------
		$allowPassword = self::is_password_auth_enabled();
		$allowGroup    = self::is_group_auth_enabled();
		$authorized    = !empty($_SESSION[self::SESSION_KEY]) && (time() - $_SESSION[self::SESSION_KEY]) < self::SESSION_EXPIRY;

		// --- Initial state ---
		$initial_useCase = 'Unknown';
		if (!$allowPassword && !$allowGroup) $initial_useCase = 'Case 1: No auth enabled';
		elseif ($allowPassword && !$allowGroup && !$authorized) $initial_useCase = 'Case 2: Password only, not validated';
		elseif ($allowPassword && !$allowGroup && $authorized) $initial_useCase = 'Case 3: Password only, validated';
		elseif (!$allowPassword && $allowGroup && !$authorized) $initial_useCase = 'Case 4: Google auth required';
		elseif (!$allowPassword && $allowGroup && $authorized) $initial_useCase = 'Case 5: Google auth approved';
		elseif ($allowPassword && $allowGroup && !$authorized) $initial_useCase = 'Case 6: Password and Google auth enabled, not authorized';
		elseif ($allowPassword && $allowGroup && $authorized) $initial_useCase = 'Case 7: Password and Google auth enabled, authorized';
		elseif (!empty($_SESSION[self::SESSION_KEY])) $initial_useCase = 'Case 8: Session cached';

		error_log('[PHP Initial State] Secure Upload Use ' . $initial_useCase);

		// -----------------------------

		$verified = false;
		$validated_by = 'none'; // Track what validated the user

		// Step 1: check existing verified session
		if (!empty($_SESSION[self::SESSION_KEY]) && (time() - $_SESSION[self::SESSION_KEY]) < self::SESSION_EXPIRY) {
			$verified = true;
			$validated_by = 'session';
		}

		// Step 2: Group/OAuth path
		if (!$verified && self::is_group_auth_enabled()) {
			$email = self::get_current_user_email();
			if ($email && self::user_in_allowed_groups($email)) {
				$verified = true;
				$_SESSION[self::SESSION_KEY] = time();
				$validated_by = 'google_group';
			}
		}

		// Step 3: Only check password if not already verified
		if (!$verified && self::is_password_auth_enabled()) {
			$pass = sanitize_text_field($_POST['kgsweb_upload_pass'] ?? $_POST['password'] ?? '');
			if (self::check_password($pass)) {
				$verified = true;
				$_SESSION[self::SESSION_KEY] = time();
				$validated_by = 'password';
			}
		}

		// Step 4: Deny if still not verified
		if (!$verified) {
			if (defined('DOING_AJAX') && DOING_AJAX) {
				wp_send_json_error(['message' => 'Upload authorization failed']);
			} else {
				wp_die('Upload authorization failed.');
			}
		}

		// -----------------------------
		// Added: Final state logging with method
		// -----------------------------
		$authorized = $verified; // update flag after verification
		$final_useCase = 'Unknown';
		if (!$allowPassword && !$allowGroup) $final_useCase = 'Case 1: No auth enabled';
		elseif ($allowPassword && !$allowGroup && !$authorized) $final_useCase = 'Case 2: Password only, not validated';
		elseif ($allowPassword && !$allowGroup && $authorized) $final_useCase = 'Case 3: Password only, validated';
		elseif (!$allowPassword && $allowGroup && !$authorized) $final_useCase = 'Case 4: Google auth required';
		elseif (!$allowPassword && $allowGroup && $authorized) $final_useCase = 'Case 5: Google auth approved';
		elseif ($allowPassword && $allowGroup && !$authorized) $final_useCase = 'Case 6: Password and Google auth enabled, not authorized';
		elseif ($allowPassword && $allowGroup && $authorized) $final_useCase = 'Case 7: Password and Google auth enabled, authorized';
		elseif (!empty($_SESSION[self::SESSION_KEY])) $final_useCase = 'Case 8: Session cached';

		error_log('[PHP Final State] Secure Upload Use ' . $final_useCase . ' (validated by: ' . $validated_by . ')');

		// -----------------------------
		// Continue with upload (unchanged)
		// -----------------------------
		if (!$file || empty($file['name'])) {
			return ['success' => false, 'message' => 'No file uploaded'];
		}

		$destination = get_option('kgsweb_upload_destination', 'drive');
		if ($destination === 'wordpress') {
			return self::upload_to_wordpress($file, $folder_id);
		}

		return self::upload_to_drive($file, $folder_id);
	}


    /*******************************
     * AJAX upload wrapper
     *******************************/
    public static function ajax_handle_upload() {
        check_ajax_referer('kgsweb_upload_nonce', 'nonce'); // ajax_handle_upload()
        $file = $_FILES['kgsweb_upload_file'] ?? $_FILES['file'] ?? null;
        $folder_id = sanitize_text_field($_POST['upload_folder_id'] ?? $_POST['folder_id'] ?? '');
        $result = self::handle_upload($file, $folder_id);

        if (is_array($result)) wp_send_json($result);
        else wp_send_json_error(['message' => 'Unexpected upload result']);
    }

  
	protected static function upload_to_drive($file, $folder_id) {

		if (!$file || empty($file['name'])) return ['success' => false, 'message' => 'No file uploaded'];
		if (empty($folder_id)) return ['success' => false, 'message' => 'No Google Drive folder specified'];

		$service_json = get_option('kgsweb_service_account_json');
		if (!$service_json) return ['success' => false, 'message' => 'Google service account not configured'];

		error_log("KGSWEB DEBUG: handle_upload POST=" . print_r($_POST, true));
		error_log("KGSWEB DEBUG: handle_upload FILES=" . print_r($_FILES, true));

		try {
			$client = new Client();
			$client->setAuthConfig(json_decode($service_json, true));
			$client->addScope(Drive::DRIVE_FILE);

			$drive = new Drive($client);

			$fileMetadata = new DriveFile([
				'name' => $file['name'],
				'parents' => [$folder_id]
			]);

			$content = file_get_contents($file['tmp_name']);

			// Shared drive support: always set supportsAllDrives
			$createdFile = $drive->files->create(
				$fileMetadata,
				[
					'data' => $content,
					'mimeType' => $file['type'],
					'uploadType' => 'multipart',
					'supportsAllDrives' => true
				]
			);

			// Copy to WP uploads for Media Library
			$wp_upload_dir = wp_upload_dir();
			$target_dir = trailingslashit($wp_upload_dir['path']);
			if (!file_exists($target_dir)) wp_mkdir_p($target_dir);

			$filename = sanitize_file_name($file['name']);
			$targetFile = $target_dir . $filename;

			if (!copy($file['tmp_name'], $targetFile)) {
				return [
					'success' => true,
					'id' => $createdFile->id,
					'name' => $createdFile->name,
					'warning' => 'File saved to Drive but not copied locally.'
				];
			}

			$attach_id = KGSweb_Google_Helpers::register_wp_attachment($targetFile, $file['type']);

			return [
				'success' => true,
				'id' => $createdFile->id,
				'name' => $createdFile->name,
				'wp_attach' => $attach_id,
				'wp_url' => trailingslashit($wp_upload_dir['url']) . $filename,
			];

		} catch (\Exception $e) {
			return ['success' => false, 'message' => 'Google Drive upload failed: ' . $e->getMessage()];
		}
	}


    /*******************************
     * WordPress Upload
     *******************************/
    protected static function upload_to_wordpress($file, $folder_id) {
        if (!$file || empty($file['name'])) return ['success' => false, 'message' => 'No file uploaded'];

        $base_folder = get_option('kgsweb_wp_upload_root_folder_id', 'documents');

        $wp_upload_dir = wp_upload_dir();
        $root_path = trailingslashit($wp_upload_dir['basedir']) . $base_folder;
        $root_url = trailingslashit($wp_upload_dir['baseurl']) . $base_folder;

        if (!file_exists($root_path)) wp_mkdir_p($root_path);

        // -----------------------------
        // Get cached Drive folders tree
        // -----------------------------
        $folders_tree = KGSweb_Google_Drive_Docs::get_cached_folders(get_option('kgsweb_upload_root_folder_id', ''));

        $relative_folder = self::get_drive_folder_path($folder_id, $folders_tree) ?: sanitize_file_name($folder_id);

        $target_path = trailingslashit($root_path) . $relative_folder;
        if (!file_exists($target_path)) wp_mkdir_p($target_path);

        $filename = sanitize_file_name($file['name']);
        $destination = trailingslashit($target_path) . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) return ['success' => false, 'message' => 'Failed to move uploaded file'];

        $attach_id = KGSweb_Google_Helpers::register_wp_attachment($destination, $file['type']);
        $file_url = trailingslashit($root_url) . $relative_folder . '/' . $filename;

        return ['success' => true, 'url' => $file_url, 'id' => $attach_id, 'name' => $filename];
    }

    /*******************************
     * Get relative path helper (Suggested + CURRENT)
     *******************************/
				
    protected static function get_relative_path_from_drive_folder_id($folder_id) {

        $folders_tree = KGSweb_Google_Drive_Docs::get_cached_folders(get_option('kgsweb_upload_root_folder_id', ''));
        $relative = self::get_drive_folder_path($folder_id, $folders_tree);

				  
        if (!$relative) $relative = $folder_id ? sanitize_file_name($folder_id) : '';

        return $relative;
    }

    /*******************************
     * Utility: Map folder ID to path
     *******************************/
    protected static function get_drive_folder_path($folder_id, $folders_tree) {
        $path = [];

		 
        $search = function($items, $target_id) use (&$search, &$path) {
            foreach ($items as $item) {
                if ($item['id'] === $target_id) {
                    array_unshift($path, sanitize_file_name($item['name']));
                    return true;
                }
                if (!empty($item['children']) && $search($item['children'], $target_id)) {
                    array_unshift($path, sanitize_file_name($item['name']));
                    return true;
                }
            }
            return false;
        };

        $search($folders_tree, $folder_id);
        return implode('/', $path);
    }

    /*******************************
     * AJAX: get cached folders
     *******************************/
    public static function ajax_get_cached_folders() {

        check_ajax_referer('kgsweb_upload_nonce', 'nonce'); // ajax_get_cached_folders()
        $root = sanitize_text_field($_REQUEST['root'] ?? '');

        if (!$root) wp_send_json_error(['message' => 'No root folder specified']);
        if (!class_exists('KGSweb_Google_Drive_Docs')) wp_send_json_error(['message' => 'Drive helper not available']);

        $folders_tree = KGSweb_Google_Drive_Docs::get_cached_folders($root);

        $flatten = function(array $items, $prefix = '') use (&$flatten) {
            $flat = [];
            foreach ($items as $item) {
                $label = $prefix . $item['name'];
                $flat[] = ['id' => $item['id'], 'label' => $label];
                if (!empty($item['children'])) $flat = array_merge($flat, $flatten($item['children'], $label . ' > '));
            }
            return $flat;
        };

        wp_send_json_success($flatten($folders_tree));
    }

    /*******************************
     * Refresh cached folder list
     *******************************/
 
    public static function refresh_uploads_cache() {
        $root = get_option('kgsweb_upload_root_folder_id', '');
        if (!$root) return false;

        KGSweb_Google_Drive_Docs::cache_upload_folders($root);
        return true;
    }
}

/// AFTER CLASS

add_action('wp_login', function($user_login, $user) {
    // Check if the login was via Google OAuth
    $meta = get_user_meta($user->ID, 'nsl_current_user_id', true);
    if (!empty($meta) && strtolower($meta['provider'] ?? '') === 'google') {
        // Optionally check group membership
        $allowedGroupsRaw = get_option('kgsweb_upload_google_groups', []);
        $email = strtolower(trim($user->user_email));
        $allowed = false;
        if (!is_array($allowedGroupsRaw)) {
            $allowedGroupsRaw = array_filter(array_map('trim', explode(',', (string)$allowedGroupsRaw)));
        }
        foreach ($allowedGroupsRaw as $entry) {
            $entry = strtolower(trim($entry));
            if ($entry === $email || (strpos($entry, '@') === 0 && substr($email, -strlen($entry)) === $entry)) {
                $allowed = true;
                break;
            }
        }

        if ($allowed) {
            setcookie('kgsweb_group_auth_verified', '1', time() + 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
            // KGSweb_Google_Helpers::start(); // REMOVED
            $_SESSION[KGSweb_Google_Secure_Upload::SESSION_KEY] = time();
        }
    }
}, 10, 2);