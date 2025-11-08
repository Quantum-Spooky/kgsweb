<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// includes/class-kgsweb-google-admin.php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/*******************************
 * Admin menu + Settings screen
 *******************************/							 
class KGSweb_Google_Admin {

    /**
     * Initialize admin hooks
     */
	public static function init() {
		$instance = new self();
		add_action('admin_menu', [$instance, 'menu']);
		add_action('admin_enqueue_scripts', [$instance, 'enqueue_admin']); 
	}

    /*******************************
     * Enqueue admin assets
     *******************************/
    public function enqueue_admin($hook_suffix): void {
        if ($hook_suffix !== 'toplevel_page_kgsweb-settings') return;

        wp_enqueue_style('kgsweb-style');

        $admin_js = [
            'admin','cache','calendar','documents','helpers',
            'display','ticker','upload','slides','sheets'
        ];
        foreach ($admin_js as $mod) {
            wp_enqueue_script("kgsweb-$mod");
        }       	
    }

    /*******************************
     * Admin menu
     *******************************/
    public function menu() {
        add_menu_page(
            __('KGS Web Integration', 'kgsweb'),
            __('KGS Web', 'kgsweb'),
            'manage_options',
            'kgsweb-settings',
            [$this, 'render_settings_page'],
            'dashicons-google',
            82
        );
    }

    /*******************************
     * Helper: Extract Drive folder ID
     *******************************/
    public static function extract_drive_folder_id(string $input): string {
        $input = trim($input);
        if (preg_match('#/folders/([a-zA-Z0-9-_]+)#', $input, $matches)) {
            return $matches[1];
        }
        return $input;
    }


    /*******************************
     * Render Settings Page
     *******************************/
    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        $integration = KGSweb_Google_Integration::init();

        // Handle save settings, cache update/clear
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['kgsweb_save_settings'])) {
                if (!isset($_POST['kgsweb_save_settings_nonce']) || !wp_verify_nonce($_POST['kgsweb_save_settings_nonce'], 'kgsweb_save_settings_action')) {
                    wp_die('Security check failed.');
                }
			
            // -------------------------------
            // Upload settings
            // -------------------------------
            $auth_mode = sanitize_text_field($_POST['upload_auth_mode'] ?? 'password');
			
            // Upload authorization flags
			update_option('kgsweb_allow_password_auth', isset($_POST['allow_password_auth']));
			update_option('kgsweb_allow_group_auth', isset($_POST['allow_group_auth']));

			// Backward compatibility migration cleanup
			delete_option('kgsweb_upload_auth_mode');

            if (!empty($_POST['upload_password'])) {
                $plain = sanitize_text_field($_POST['upload_password']);
                update_option('kgsweb_upload_password_plaintext', $plain); // plaintext for admin display
                update_option('kgsweb_upload_password_hash', hash('sha256', $plain)); // hash for frontend validation
            }

            // -------------------------------
            // Google Integration settings
            // -------------------------------
            update_option('kgsweb_service_account_json', stripslashes($_POST['service_account_json']));
            update_option('kgsweb_public_documents_root_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['public_documents_root_folder_id'] ?? ''));
            update_option('kgsweb_ticker_folder_id', $_POST['ticker_folder_id'] ?? '');
            update_option('kgsweb_calendar_ids', $_POST['calendar_ids']);
            update_option('kgsweb_calendar_url', esc_url_raw($_POST['calendar_url'] ?? ''));
            update_option('kgsweb_upload_root_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['upload_root_folder_id'] ?? ''));
			update_option('kgsweb_upload_google_groups', array_filter(array_map('trim', preg_split('/\r?\n/', $_POST['google_groups'] ?? ''))));
            update_option('kgsweb_upload_destination', sanitize_text_field($_POST['upload_destination'] ?? 'drive'));
            update_option('kgsweb_wp_upload_root_folder_id', sanitize_text_field($_POST['wp_upload_root_folder_id'] ?? ''));

            // -------------------------------
            // New Display Folders: Normalize Drive URLs
            // -------------------------------
            update_option('kgsweb_breakfast_menu_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['breakfast_menu_folder_id'] ?? ''));
            update_option('kgsweb_lunch_menu_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['lunch_menu_folder_id'] ?? ''));
            update_option('kgsweb_monthly_calendar_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['monthly_calendar_folder_id'] ?? ''));
            update_option('kgsweb_academic_calendar_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['academic_calendar_folder_id'] ?? ''));
            update_option('kgsweb_athletic_calendar_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['athletic_calendar_folder_id'] ?? ''));
            update_option('kgsweb_feature_image_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['feature_image_folder_id'] ?? ''));
            update_option('kgsweb_pto_feature_image_folder_id', KGSweb_Google_Admin::extract_drive_folder_id($_POST['pto_feature_image_folder_id'] ?? ''));
			
			// Clear client-side cookie, I think
			update_option('kgsweb_upload_settings_version', time());

            // Refresh caches																				    
                $integration->cron_refresh_all_caches();
                $integration->get_drive();
                if (!defined('DOING_AJAX') || !DOING_AJAX) echo "<div class='updated'><p>Settings saved!</p></div>";
            }

        // -------------------------------
        // Update Cache Button
        // -------------------------------							  
            if (isset($_POST['kgsweb_update_cache'])) {
                if (!isset($_POST['kgsweb_update_cache_nonce']) || !wp_verify_nonce($_POST['kgsweb_update_cache_nonce'], 'kgsweb_update_cache_action')) {
                    wp_die('Security check failed.');
                }
                $integration->cron_refresh_all_caches();
				// Force ticker cache refresh 
				if (class_exists('KGSweb_Google_Ticker')) { KGSweb_Google_Ticker::refresh_ticker_cache(); }
                if (!defined('DOING_AJAX') || !DOING_AJAX) echo "<div class='updated'><p>Cache updated successfully!</p></div>";
            }
        // -------------------------------
        // Clear Cache
        // -------------------------------				  
            if (isset($_POST['kgsweb_clear_cache'])) {
                if (!isset($_POST['kgsweb_clear_cache_nonce']) || !wp_verify_nonce($_POST['kgsweb_clear_cache_nonce'], 'kgsweb_clear_cache_action')) {
                    wp_die('Security check failed.');
                }
                global $wpdb;
                   // Delete all KGSWEB transients
					$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_kgsweb\_%'");
					$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_kgsweb\_%'");
					// Clear ticker cache index
					delete_option('kgsweb_ticker_cache_index');
					delete_option('kgsweb_ticker_last_file_id');
                if (!defined('DOING_AJAX') || !DOING_AJAX) echo "<div class='updated'><p>All KGSWEB caches cleared!</p></div>";
            }
        }

        // -------------------------------
        // Load Saved Options for display
        // -------------------------------
        $service_json       		= get_option('kgsweb_service_account_json', '');
        $public_documents_root      = get_option('kgsweb_public_documents_root_folder_id', '');
		$ticker        			    = get_option('kgsweb_ticker_folder_id', '');
        $calendars      		    = get_option('kgsweb_calendar_ids', '');
        $calendar_url       		= get_option('kgsweb_calendar_url', '');
        $upload_root 				= get_option('kgsweb_upload_root_folder_id', '');
		$allow_password_auth 		= (bool) get_option('kgsweb_allow_password_auth', true);
		$allow_group_auth 			= (bool) get_option('kgsweb_allow_group_auth', false);        
		$upload_pass     			= get_option('kgsweb_upload_password_plaintext', '');
        $google_groups				= get_option('kgsweb_upload_google_groups', []);
        $upload_dest				= get_option('kgsweb_upload_destination', 'drive');
        $wp_upload_root				= get_option('kgsweb_wp_upload_root_folder_id', '');

        // Display folders
        $breakfast_menu     = get_option('kgsweb_breakfast_menu_folder_id', '');
        $lunch_menu         = get_option('kgsweb_lunch_menu_folder_id', '');
        $monthly_calendar   = get_option('kgsweb_monthly_calendar_folder_id', '');
        $academic_calendar  = get_option('kgsweb_academic_calendar_folder_id', '');
        $athletic_calendar  = get_option('kgsweb_athletic_calendar_folder_id', '');
        $feature_image      = get_option('kgsweb_feature_image_folder_id', '');
        $pto_feature_image  = get_option('kgsweb_pto_feature_image_folder_id', '');

        // Ensure calendar IDs are always an array
        $calendars_array = is_array($calendars) ? $calendars : explode(',', (string)$calendars);
        $calendars_array = array_map('trim', $calendars_array);

        $last      = (int) get_option('kgsweb_last_refresh', 0);
        $last_text = $last > 0 ? date_i18n('m/d/Y g:i A T', $last) : '';

        // -------------------------------
        // Render Settings HTML Form
        // -------------------------------
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
			?>
			<div class="wrap">
				<h1><?php esc_html_e('KGS Web Google Integration Plugin Settings', 'kgsweb'); ?></h1>
				<div class="main-plugin-settings">
					<form method="post">
						<?php wp_nonce_field('kgsweb_save_settings_action', 'kgsweb_save_settings_nonce'); ?>
						<?php wp_nonce_field('kgsweb_update_cache_action', 'kgsweb_update_cache_nonce'); ?>
						<?php wp_nonce_field('kgsweb_clear_cache_action', 'kgsweb_clear_cache_nonce'); ?>

						<!-- Service Account JSON -->
						<h2>Google Service Account JSON</h2>
						<p>Paste the JSON content of your service account key file.</p>
						<button type="button" id="toggle-service-json" class="button">Show JSON</button>
						<textarea id="service-account-json" name="service_account_json" rows="12" cols="80" style="display:none;"><?php echo esc_textarea($service_json); ?></textarea>

						<!-- Drive Folder IDs -->
						<h2>Google Drive Folder IDs (Global Defaults)</h2>
						<table class="form-table">
							<tr><th>Public Documents Root Folder</th><td><input type="text" id="public_documents_root_folder_id" name="public_documents_root_folder_id" value="<?php echo esc_attr($public_documents_root); ?>" size="50"></td></tr>
							<tr><th>Documents Upload Root Folder</th><td><input type="text" id="upload_root_folder_id" name="upload_root_folder_id" value="<?php echo esc_attr($upload_root); ?>" size="50"></td></tr>
							<tr><th>Ticker Folder</th><td><input type="text" id="ticker_folder_id" name="ticker_folder_id" value="<?php echo esc_attr($ticker); ?>" size="50"></td></tr>
							<tr><th>Breakfast Menu Folder</th><td><input type="text" id="breakfast_menu_folder_id" name="breakfast_menu_folder_id" value="<?php echo esc_attr($breakfast_menu); ?>" size="50"></td></tr>
							<tr><th>Lunch Menu Folder</th><td><input type="text" id="lunch_menu_folder_id" name="lunch_menu_folder_id" value="<?php echo esc_attr($lunch_menu); ?>" size="50"></td></tr>
							<tr><th>Monthly Calendar Folder</th><td><input type="text" id="monthly_calendar_folder_id" name="monthly_calendar_folder_id" value="<?php echo esc_attr($monthly_calendar); ?>" size="50"></td></tr>
							<tr><th>Academic Calendar Folder</th><td><input type="text" id="academic_calendar_folder_id" name="academic_calendar_folder_id" value="<?php echo esc_attr($academic_calendar); ?>" size="50"></td></tr>
							<tr><th>Athletic Calendar Folder</th><td><input type="text" id="athletic_calendar_folder_id" name="athletic_calendar_folder_id" value="<?php echo esc_attr($athletic_calendar); ?>" size="50"></td></tr>
							<tr><th>Feature Image Folder</th><td><input type="text" id="feature_image_folder_id" name="feature_image_folder_id" value="<?php echo esc_attr($feature_image); ?>" size="50"></td></tr>
							<tr><th>PTO Feature Image Folder</th><td><input type="text" id="pto_feature_image_folder_id" name="pto_feature_image_folder_id" value="<?php echo esc_attr($pto_feature_image); ?>" size="50"></td></tr>
						</table>

						<!-- Calendar Settings -->
						<h2>Calendar IDs</h2>
						<p>Enter one or more Google Calendar IDs (comma-separated) to display upcoming events.</p>
						<input type="text" name="calendar_ids" id="calendar_ids" value="<?php echo esc_attr(implode(',', $calendars_array)); ?>" size="50">

						<h2>Calendar URL</h2>
						<p>Enter the URL for the Calendar page view (used for "View Calendar" link).</p>
						<input type="url" name="calendar_url" id="calendar_url" value="<?php echo esc_attr($calendar_url); ?>" size="50">

						<!-- Secure Upload Settings -->
						<h2>Secure Upload Settings</h2>
						<table class="form-table">
							<tr>
								<th>Authorization Methods</th>
								<td>
									<label>
										<input type="checkbox" name="allow_password_auth" value="1" <?php checked($allow_password_auth); ?>>
										Allow Password Authentication
									</label><br>
									<label>
										<input type="checkbox" name="allow_group_auth" value="1" <?php checked($allow_group_auth); ?>>
										Allow Google Group Authentication
									</label>
								</td>
							</tr>
							<tr>
								<th>Upload Password</th>
								<td>
									<div class="password-container">
										<input type="password" id="upload_password_admin" name="upload_password" value="<?php echo esc_attr($upload_pass); ?>">
										<i class="fas fa-eye toggle_password"></i>
									</div>
								</td>
							</tr>
							<tr>
								<th>Google Groups Allowed</th>
								<td>
									<textarea name="google_groups" rows="10" cols="60" style="resize: vertical;"><?php
										echo esc_textarea(implode("\n", $google_groups));
									?></textarea><br>
									<small>Enter email addresses or groups (e.g. staff@kellgradeschool.com, user1@gmail.com). One per line.</small><br>
								

								</td>
							</tr>
							<tr>
								<th>Upload Destination</th>
								<td>
									<select name="upload_destination">
										<option value="drive" <?php selected($upload_dest, 'drive'); ?>>Google Drive (default)</option>
										<option value="wordpress" <?php selected($upload_dest, 'wordpress'); ?>>WordPress (optional)</option>
									</select>
								</td>
							</tr>
							<tr class="wp-upload-row">
								<th>WordPress Upload Root</th>
								<td>
									    <input type="text" name="wp_upload_root_folder_id" id="wp_upload_root_folder_id" value="<?php echo esc_attr($wp_upload_root); ?>" size="50"><br>
									<small>Used only if destination is WordPress</small>
								</td>
							</tr>
						</table>

						<p class="submit">
							<input type="submit" name="kgsweb_save_settings" id="kgsweb_save_settings" class="button button-primary" value="Save Settings">
						</p>

						<!-- Cache Management -->
						<hr />
						<h2>Cache Management</h2>
						<p>Last cache refresh: <?php echo esc_html($last_text); ?></p>
						<button type="submit" name="kgsweb_update_cache"  id="kgsweb_update_cache"  class="button">Update Cache Now</button>
						<button type="submit" name="kgsweb_clear_cache" id="kgsweb_clear_cache" class="button">Clear All Cache</button>
					</form>
				</div>

				<hr />
				<div class="kgsweb-shortcode-help">
					<h2>Available Shortcodes</h2>
					<p>Use these shortcodes in posts, pages, or widgets</p>
					<ul class="kgsweb-shortcode-list">
						<li><code>[kgsweb_documents doc-folder="FOLDER_ID"]</code> &nbsp; <i>Accordion folder tree from Drive; excludes empty folders</i></li>
						<li><code>[kgsweb_secure_upload upload-folder="FOLDER_ID"]</code> &nbsp; <i>Upload form with password/Google Group gating</i></li>
						<li><code>[kgsweb_events calendar_id="CALENDAR_ID"]</code> &nbsp; <i>Displays upcoming events with pagination</i></li>
						<li><code>[kgsweb_ticker folder="FOLDER_ID"]</code> &nbsp; <i>Displays scrolling text from a Doc or .txt file</i></li>
						<li><code>[kgsweb_slides file="FILE_ID"]</code> &nbsp; <i>Embeds Google Slides presentation</i></li>
						<li><code>[kgsweb_sheets sheet_id="SHEET_ID" range="A1:Z100"]</code> &nbsp; <i>Displays Google Sheets data</i></li>
						<li><code>[kgsweb_img_display type="monthly-calendar"]</code>, <code>[kgsweb_img_display type="breakfast-menu"]</code>, <code>[kgsweb_img_display folder="FOLDER_ID"]</code> &nbsp; <i>Displays first image/PDF from a Drive folder</i></li>
					</ul>
				</div>
			</div>
			<?php
		} // end !ajax wrapper
    } // end render_settings_page
} // end class					 