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
		// AJAX handler for upload
		add_action('wp_ajax_kgsweb_handle_upload', [KGSweb_Google_Secure_Upload::class, 'check_for_upload_and_process']);
		add_action('wp_ajax_nopriv_kgsweb_handle_upload', [KGSweb_Google_Secure_Upload::class, 'check_for_upload_and_process']);

    }
	
	// AJAX validate password input
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

    // Validate password input
	public static function check_password($input) {
		$stored_hash = get_option('kgsweb_upload_password_hash', '');
		if (!$stored_hash) return false;
		return hash('sha256', $input) === $stored_hash;
	}

	
	public static function save_password($plain_password) {
		$plain_password = sanitize_text_field($plain_password);
		update_option('kgsweb_upload_password', $plain_password); // plaintext for admin
		update_option('kgsweb_upload_password_hash', hash('sha256', $plain_password)); // hash for frontend
	}

    // Detect upload requests
    public static function check_for_upload_and_process() {
		
		if (empty($_FILES['kgsweb_upload_file']) && empty($_POST['upload_folder_id'])) {
			return;
		}

        $file = $_FILES['kgsweb_upload_file'] ?? null;
        $folder_id = sanitize_text_field($_POST['upload_folder_id'] ?? '');

        $result = self::handle_upload($file, $folder_id);

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json($result);
        }
    }

	// Handle upload based on admin settings
	public static function handle_upload($file, $folder_id) {
		if (!isset($_POST['kgsweb_upload_pass'])) {
			return ['success' => false, 'message' => 'No password provided'];
		}

		$pass = sanitize_text_field($_POST['kgsweb_upload_pass']);
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
			return self::upload_to_wordpress($file);
		}

		// Default path: Google Drive
		return self::upload_to_drive($file, $folder_id);
	}


    // WordPress media upload
    protected static function upload_to_wordpress($file) {
        if (!$file || empty($file['name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $overrides = ['test_form' => false];
        $uploaded_file = wp_handle_upload($file, $overrides);

        if (isset($uploaded_file['error'])) {
            return ['success' => false, 'message' => $uploaded_file['error']];
        }

        $attachment = [
            'post_mime_type' => $uploaded_file['type'],
            'post_title'     => sanitize_file_name($file['name']),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $uploaded_file['file']);
        $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return ['success' => true, 'url' => $uploaded_file['url']];
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
                'name' => $file['name'],
                'parents' => [$folder_id]
            ]);

            $content = file_get_contents($file['tmp_name']);
            $createdFile = $drive->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $file['type'],
                'uploadType' => 'multipart'
            ]);

            return [
                'success' => true,
                'id' => $createdFile->id,
                'name' => $createdFile->name
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Google Drive upload failed: ' . $e->getMessage()
            ];
        }
    }
	
	
	
	
	
	
	// REST endpoint for cached folders
	add_action('wp_ajax_kgsweb_get_cached_folders', function() {
		$root = sanitize_text_field($_GET['root'] ?? '');
		if (!$root) {
			wp_send_json_error(['message' => 'No root folder specified']);
		}

		$folders = KGSweb_Google_Integration::init()->get_cached_folders($root);
		$list = [];

		foreach ($folders as $f) {
			// Only show folders, not files
			if ($f['mimeType'] === 'application/vnd.google-apps.folder') {
				$list[] = [
					'id' => $f['id'],
					'name' => $f['name']
				];
			}
		}

		wp_send_json_success($list);
	});





}
