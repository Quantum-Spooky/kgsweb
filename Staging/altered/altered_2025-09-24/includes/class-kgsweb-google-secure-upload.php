<?php
// includes/class-kgsweb-google-secure-upload.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class KGSweb_Google_Secure_Upload {
	
	public static function init() {
		add_action('init', [__CLASS__, 'check_for_upload_and_process']);
		add_action('wp_ajax_kgsweb_check_password', [__CLASS__, 'ajax_check_password']);
		add_action('wp_ajax_nopriv_kgsweb_check_password', [__CLASS__, 'ajax_check_password']);
		add_action('wp_ajax_kgsweb_get_cached_folders', [__CLASS__, 'ajax_get_cached_folders']);
		add_action('wp_ajax_nopriv_kgsweb_get_cached_folders', [__CLASS__, 'ajax_get_cached_folders']);
		add_action('wp_ajax_kgsweb_handle_upload', [__CLASS__, 'ajax_handle_upload']);
		add_action('wp_ajax_nopriv_kgsweb_handle_upload', [__CLASS__, 'ajax_handle_upload']);

		// ADDED: accept an alternate AJAX endpoint name used in the Suggested code
		// This does not remove your existing hooks; it just provides compatibility.
		add_action('wp_ajax_kgsweb_secure_upload', [__CLASS__, 'ajax_handle_upload']); // ADDED
		add_action('wp_ajax_nopriv_kgsweb_secure_upload', [__CLASS__, 'ajax_handle_upload']); // ADDED

		// ADDED: alternate AJAX password-check endpoint name for compatibility
		add_action('wp_ajax_kgsweb_check_upload_password', [__CLASS__, 'ajax_check_password']); // ADDED
		add_action('wp_ajax_nopriv_kgsweb_check_upload_password', [__CLASS__, 'ajax_check_password']); // ADDED
		
		
		
									// TESTING
									add_action('init', function() {
									if (current_user_can('manage_options')) {
										$root = get_option('kgsweb_upload_root_folder_id', '');
										$tree = KGSweb_Google_Drive_Docs::get_cached_folders($root);
										error_log("UPLOAD ROOT = $root");
										error_log("FOLDER TREE = " . print_r($tree, true));
										}
									});



	}
	
	 /*******************************
     * AJAX validate password input
     *******************************/
	 
	public static function ajax_check_password() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		$lock_key = 'kgsweb_lockout_' . md5($ip);
		$fail_key = 'kgsweb_failures_' . md5($ip);

		// 🔒 Bypass lockout if logged-in admin
		if (!current_user_can('manage_options')) {
			$locked_until = get_transient($lock_key);
			if ($locked_until && time() < $locked_until) {
				wp_send_json_error([
					'message' => 'Too many failed attempts. Try again later.'
				]);
			}
		}

		$password = sanitize_text_field($_POST['password'] ?? '');

		if (self::check_password($password)) {
			// ✅ Success: reset failure counter
			delete_transient($fail_key);
			wp_send_json_success();
		} else {
			// ❌ Failure: increment counter
			$failures = (int) get_transient($fail_key);
			$failures++;
			set_transient($fail_key, $failures, HOUR_IN_SECONDS);

			if ($failures >= 50) {
				// Lock for 1 hour
				set_transient($lock_key, time() + HOUR_IN_SECONDS, HOUR_IN_SECONDS);
				wp_send_json_error([
					'message' => 'Too many failed attempts. Locked for 1 hour.'
				]);
			}

			wp_send_json_error(['message' => 'Invalid password']);
		}
	}

	 /*******************************
     * Validate password input
     * broadened to support both older single-option/hash pattern
     * and the suggested 'kgsweb_secure_upload_options' array for compatibility.
     *******************************/
	 
	public static function check_password($input) {
		// Try the newer secure_upload_options array first (Suggested)
		$secure_opts = get_option('kgsweb_secure_upload_options', []);
		if (!empty($secure_opts['upload_password_hash'] ?? '') || !empty($secure_opts['upload_password'] ?? '')) {
			// ADDED: support either plaintext or hash stored under secure_upload_options
			$stored_hash = $secure_opts['upload_password_hash'] ?? '';
			$stored_plain = $secure_opts['upload_password'] ?? '';
			if ($stored_hash) {
				return hash('sha256', $input) === $stored_hash;
			}
			if ($stored_plain) {
				return hash('sha256', $input) === hash('sha256', $stored_plain);
			}
		}

		// FALLBACK: original behavior (Current)
		$stored_hash = get_option('kgsweb_upload_password_hash', '');
		if (!$stored_hash) return false;
		return hash('sha256', $input) === $stored_hash;
	}

	
	public static function save_password($plain_password) {
		$plain_password = sanitize_text_field($plain_password);

		// original options for admin display + frontend validation (Current)
		update_option('kgsweb_upload_password', $plain_password); // plaintext for admin
		update_option('kgsweb_upload_password_hash', hash('sha256', $plain_password)); // hash for frontend

		// ADDED: also store in secure_upload_options for Suggested compatibility
		$secure_opts = get_option('kgsweb_secure_upload_options', []);
		$secure_opts['upload_password'] = $plain_password; // plaintext (admin)
		$secure_opts['upload_password_hash'] = hash('sha256', $plain_password); // hash (frontend)
		update_option('kgsweb_secure_upload_options', $secure_opts); // ADDED
	}

	 /*******************************
     * Detect upload requests
     *******************************/
	 
    public static function check_for_upload_and_process() {
		
		if (empty($_FILES['kgsweb_upload_file']) && empty($_POST['upload_folder_id'])) {
			// ADDED: also check for the alternate 'file' + 'folder_id' names (Suggested compatibility)
			if (empty($_FILES['file']) && empty($_POST['folder_id'])) {
				return;
			}
		}

        $file = $_FILES['kgsweb_upload_file'] ?? $_FILES['file'] ?? null; // CHANGED: accept either name
        $folder_id = sanitize_text_field($_POST['upload_folder_id'] ?? $_POST['folder_id'] ?? '');

        $result = self::handle_upload($file, $folder_id);

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json($result);
        }
    }

	 /*******************************
     * Handle upload based on admin settings
     *******************************/
	 
	public static function handle_upload($file, $folder_id) {
		if (!isset($_POST['kgsweb_upload_pass']) && !isset($_POST['password'])) {
			// ADDED: accept 'password' param (Suggested) in addition to your existing 'kgsweb_upload_pass'
			return ['success' => false, 'message' => 'No password provided'];
		}

		$pass = sanitize_text_field($_POST['kgsweb_upload_pass'] ?? $_POST['password'] ?? ''); // CHANGED
		if (!self::check_password($pass)) {
			if (defined('DOING_AJAX') && DOING_AJAX) {
				wp_send_json_error(['message' => 'Upload authorization failed']);
			} else {
				wp_die('Upload authorization failed.');
			}
		}
		
		// Default to Google Drive
		$destination = get_option('kgsweb_upload_destination', 'drive');

		if ($destination === 'wordpress') {
			return self::upload_to_wordpress($file, $folder_id);
		}

		// Default path: Google Drive
		return self::upload_to_drive($file, $folder_id);
	}

	
	// UPLOAD TO WORDPRESS
	protected static function upload_to_wordpress($file, $folder_id) {
		if (!$file || empty($file['name'])) {
			return ['success' => false, 'message' => 'No file uploaded'];
		}

		// Base folder
		$base_folder = get_option('kgsweb_wp_upload_root_folder_id', '');
		if (empty($base_folder)) $base_folder = 'documents';

		$wp_upload_dir = wp_upload_dir();
		$root_path = trailingslashit($wp_upload_dir['basedir']) . $base_folder;
		$root_url  = trailingslashit($wp_upload_dir['baseurl']) . $base_folder;

		if (!file_exists($root_path)) wp_mkdir_p($root_path);

		// Get cached Drive folders tree
		$folders_tree = KGSweb_Google_Drive_Docs::get_cached_folders(get_option('kgsweb_upload_root_folder_id', ''));

		// Build relative path from Drive folder names
		$relative_folder = self::get_drive_folder_path($folder_id, $folders_tree);
		if (!$relative_folder) $relative_folder = sanitize_file_name($folder_id); // fallback

		$target_path = trailingslashit($root_path) . $relative_folder;
		if (!file_exists($target_path)) wp_mkdir_p($target_path);

		// Save file
		$filename = sanitize_file_name($file['name']);
		$destination = trailingslashit($target_path) . $filename;

		if (!move_uploaded_file($file['tmp_name'], $destination)) {
			return ['success' => false, 'message' => 'Failed to move uploaded file'];
		}

		// ----------------------------------------
		// NEW CLEAN HELPER METHOD
		// ----------------------------------------
		$attach_id = self::register_attachment_in_media_library($destination, $file['type']);

		// Return URL
		$file_url = trailingslashit($root_url) . $relative_folder . '/' . $filename;

		return [
			'success' => true,
			'url'     => $file_url,
			'id'      => $attach_id,
			'name'    => $filename,
		];
	}


	// ADDED: helper that the Suggested code included (kept as additional helper — does not remove your get_drive_folder_path)
	// This uses the same underlying Drive tree but returns the relative path; if you want to switch
	// other code to use this function it's available.  // ADDED
	protected static function get_relative_path_from_drive_folder_id($folder_id) {
		// ADDED: use the same cached tree approach your code uses
		$folders_tree = KGSweb_Google_Drive_Docs::get_cached_folders(get_option('kgsweb_upload_root_folder_id', ''));
		$relative = self::get_drive_folder_path($folder_id, $folders_tree);
		if (!$relative) {
			// ADDED: fallback to sanitized id if no readable name found
			$relative = $folder_id ? sanitize_file_name($folder_id) : '';
		}
		return $relative;
	}


	// Translate folder ID to folder name

	protected static function get_drive_folder_path($folder_id, $folders_tree) {
		$path = [];

		// Recursive search function
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



	// Google Drive upload
	protected static function upload_to_drive($file, $folder_id) {
		if (!$file || empty($file['name'])) {
			return ['success' => false, 'message' => 'No file uploaded'];
		}

		if (empty($folder_id)) {
			return ['success' => false, 'message' => 'No Google Drive folder specified'];
		}

		$service_json = get_option('kgsweb_service_account_json');
		if (!$service_json) {
			return ['success' => false, 'message' => 'Google service account not configured'];
		}

		try {
			$client = new Client();
			$client->setAuthConfig(json_decode($service_json, true));
			$client->addScope(Drive::DRIVE_FILE);

			$drive = new Drive($client);

			$fileMetadata = new DriveFile([
				'name'    => $file['name'],
				'parents' => [$folder_id]
			]);

			$content = file_get_contents($file['tmp_name']);
			$createdFile = $drive->files->create($fileMetadata, [
				'data'       => $content,
				'mimeType'   => $file['type'],
				'uploadType' => 'multipart'
			]);

			// ----------------------------------------
			// Copy to WP uploads so Media Library can track it
			// ----------------------------------------
			$wp_upload_dir = wp_upload_dir();
			$target_dir    = trailingslashit($wp_upload_dir['path']); // e.g. .../uploads/2025/09/
			if (!file_exists($target_dir)) {
				wp_mkdir_p($target_dir);
			}

			$filename   = sanitize_file_name($file['name']);
			$targetFile = $target_dir . $filename;

			// Copy from tmp to uploads
			if (!copy($file['tmp_name'], $targetFile)) {
				// If copy fails, still return Drive success
				return [
					'success' => true,
					'id'      => $createdFile->id,
					'name'    => $createdFile->name,
					'warning' => 'File saved to Drive but not copied locally.'
				];
			}

			// Register in Media Library
			$attach_id = KGSweb_Google_Helpers::register_wp_attachment($destination, $file['type']);

			// ----------------------------------------
			// NEW: Return both Drive + WP info
			// ----------------------------------------
			return [
				'success'   => true,
				'id'        => $createdFile->id,
				'name'      => $createdFile->name,
				'wp_attach' => $attach_id,
				'wp_url'    => trailingslashit($wp_upload_dir['url']) . $filename,
			];

		} catch (\Exception $e) {
			return [
				'success' => false,
				'message' => 'Google Drive upload failed: ' . $e->getMessage()
			];
		}
	}


	/*******************************
	 * Return cached folder list (folders only, recursive) for AJAX
	 *******************************/
	public static function ajax_get_cached_folders() {
		$root = sanitize_text_field($_REQUEST['root'] ?? '');
		if (!$root) {
			wp_send_json_error(['message' => 'No root folder specified']);
		}

		if (!class_exists('KGSweb_Google_Drive_Docs')) {
			wp_send_json_error(['message' => 'Drive helper not available']);
		}

		// Get full recursive tree
		$allItems = KGSweb_Google_Drive_Docs::get_cached_folders($root);

		// Filter function: keep only folders, recursively
		$filterFolders = function($items) use (&$filterFolders) {
			$folders = [];
			foreach ($items as $item) {
				if ($item['mimeType'] === 'application/vnd.google-apps.folder') {
					$folder = [
						'id' => $item['id'],
						'name' => $item['name'],
					];
					if (!empty($item['children']) && is_array($item['children'])) {
						$folder['children'] = $filterFolders($item['children']);
					}
					$folders[] = $folder;
				}
			}
			return $folders;
		};

		$foldersOnly = $filterFolders($allItems);

		wp_send_json_success($foldersOnly);
	}

	/*******************************
	 * Refresh cached folder list
	 *******************************/
	 
	public static function refresh_folder_cache() {
		$root = get_option('kgsweb_upload_root_folder_id', '');
		if (!$root) return false;

		KGSweb_Google_Drive_Docs::cache_upload_folders($root);
		return true; // signal success
	}

	/*******************************
     * Wrap handle_upload for admin-ajax
     *******************************/
	 
	public static function ajax_handle_upload() {
		// Note: add nonce check here later if you want CSRF protection
		$file = $_FILES['kgsweb_upload_file'] ?? $_FILES['file'] ?? null; // CHANGED: accept both names (Current + Suggested)
		$folder_id = sanitize_text_field($_POST['upload_folder_id'] ?? $_POST['folder_id'] ?? ''); // CHANGED
		
		$result = self::handle_upload($file, $folder_id);

		// Ensure consistent JSON response
		if (is_array($result)) {
			wp_send_json($result);
		} else {
			wp_send_json_error(['message' => 'Unexpected upload result']);
		}
	}



}
