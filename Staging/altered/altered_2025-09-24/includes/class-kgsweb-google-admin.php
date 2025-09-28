<?php 
// includes/class-kgsweb-google-admin.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/*******************************
 * Admin menu + Settings screen
 *******************************/
class KGSweb_Google_Admin {

    // Initialize the admin
    public static function init() {
        $instance = new self();
        add_action('admin_menu', [$instance, 'menu']);
        add_action('admin_enqueue_scripts', [$instance, 'enqueue_admin']); 
    }

    /*******************************
     * Admin Assets
     *******************************/
    public function enqueue_admin($hook_suffix): void {
        // Only load on your plugin’s settings page
        if ($hook_suffix !== 'toplevel_page_kgsweb-settings') {
            return;
        }

        // CSS
        wp_enqueue_style('kgsweb-style');

        // Admin JS (already registered in register_assets in Integration)
        $admin_js = [
            'admin',
            'cache',
            'calendar',
            'documents',
            'helpers',
            'menus',
            'ticker',
            'upload',
			'format',
            'slides',
            'sheets'
        ];

        foreach ($admin_js as $mod) {
            wp_enqueue_script("kgsweb-$mod");
        }
    }

    // Menu callback
    public function menu() {
        add_menu_page(
            __( 'KGS Web Integration', 'kgsweb' ),
            __( 'KGS Web', 'kgsweb' ),
            'manage_options',
            'kgsweb-settings',
            [$this, 'render_settings_page'], // instance callback
            'dashicons-google',
            82
        );
    }

    // Render Settings Page
    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        $integration = KGSweb_Google_Integration::init();

        // -------------------------------
        // Save Settings
        // -------------------------------							  
        if (isset($_POST['kgsweb_save_settings'])) {
            if (!isset($_POST['kgsweb_save_settings_nonce']) || !wp_verify_nonce($_POST['kgsweb_save_settings_nonce'], 'kgsweb_save_settings_action')) {
                wp_die('Security check failed.');
            }
			
			// Upload settings
			$auth_mode = sanitize_text_field($_POST['upload_auth_mode'] ?? 'password');
			update_option('kgsweb_upload_auth_mode', $auth_mode);

		if (!empty($_POST['upload_password'])) {
			$plain = sanitize_text_field($_POST['upload_password']);
			// Save plaintext for admin display
			update_option('kgsweb_upload_password_plaintext', $plain);
			// Save hash for frontend validation
			update_option('kgsweb_upload_password_hash', hash('sha256', $plain));
		}

            // Google Integration options									 
            update_option('kgsweb_service_account_json', stripslashes($_POST['service_account_json']));
            update_option('kgsweb_root_folder_id', $_POST['root_folder_id']);
            update_option('kgsweb_breakfast_folder_id', $_POST['breakfast_folder_id']);
            update_option('kgsweb_lunch_folder_id', $_POST['lunch_folder_id']);
            update_option('kgsweb_ticker_file_id', $_POST['ticker_file_id'] ?? '');
            update_option('kgsweb_calendar_ids', $_POST['calendar_ids']);
			update_option('kgsweb_calendar_url', esc_url_raw($_POST['calendar_url'] ?? ''));
			update_option('kgsweb_upload_root_folder_id', $_POST['upload_root_folder_id']);
			update_option('kgsweb_upload_google_groups', array_map('trim', explode(',', $_POST['google_groups'] ?? '')));
			update_option('kgsweb_upload_destination', sanitize_text_field($_POST['upload_destination'] ?? 'drive'));
			update_option('kgsweb_wp_upload_root', sanitize_text_field($_POST['wp_upload_root'] ?? ''));

			// Refresh cache
            $integration->cron_refresh_all_caches();

            echo "<div class='updated'><p>Settings saved!</p></div>";

            // Initialize or refresh Google Drive client
            $integration->get_drive();
        }	
	
        // -------------------------------
        // Reset Lockouts
        // -------------------------------							  
        /*
        if (isset($_POST['kgsweb_reset_locked'])) {
            delete_option('kgsweb_upload_lockouts');
            global $wpdb;
            $transients = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '\_transient\_kgsweb\_attempts\_%'", ARRAY_A);
            foreach ($transients as $t) delete_transient(str_replace('_transient_', '', $t['option_name']));
            echo "<div class='updated'><p>Upload lockouts cleared.</p></div>";
        }
		*/
        
        // -------------------------------
        // Update Cache Button
        // -------------------------------					  
        if (isset($_POST['kgsweb_update_cache'])) {
            if (!isset($_POST['kgsweb_update_cache_nonce']) || !wp_verify_nonce($_POST['kgsweb_update_cache_nonce'], 'kgsweb_update_cache_action')) {
                wp_die('Security check failed.');
            }
            $integration->cron_refresh_all_caches();
            echo "<div class='updated'><p>Cache updated successfully!</p></div>";
        }
													
									   
    // -------------------------------
    // Clear Cache
    // -------------------------------		  
        if (isset($_POST['kgsweb_clear_cache'])) {
            if (!isset($_POST['kgsweb_clear_cache_nonce']) || !wp_verify_nonce($_POST['kgsweb_clear_cache_nonce'], 'kgsweb_clear_cache_action')) {
                wp_die('Security check failed.');
            }
            global $wpdb;
            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_kgsweb\_%'");
            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_kgsweb\_%'");
            echo "<div class='updated'><p>All KGSWEB caches cleared!</p></div>";
        }
									  
    // -------------------------------	  
	// Load Saved Options
    // -------------------------------							  
        $service_json       = get_option('kgsweb_service_account_json', '');
        $root_folder        = get_option('kgsweb_root_folder_id', '');

		$breakfast          = get_option('kgsweb_breakfast_folder_id', '');
        $lunch              = get_option('kgsweb_lunch_folder_id', '');
        $ticker             = get_option('kgsweb_ticker_file_id', '');
        $calendars          = get_option('kgsweb_calendar_ids', '');
        $calendar_url       = get_option('kgsweb_calendar_url', '');

		$upload_root_folder = get_option('kgsweb_upload_root_folder_id', '');
        $upload_opts        = get_option('kgsweb_secure_upload_options', []);
		
		$upload_auth_mode = get_option('kgsweb_upload_auth_mode', 'password');
		$upload_pass      = get_option('kgsweb_upload_password_plaintext', '');
		$google_groups    = get_option('kgsweb_upload_google_groups', []);
		$upload_dest      = get_option('kgsweb_upload_destination', 'drive');
		$wp_upload_root   = get_option('kgsweb_wp_upload_root', '');

    // Ensure options are arrays																			 
	 /*
		if (!is_array($upload_opts)) $upload_opts = [];
		$upload_opts['google_groups'] = $upload_opts['google_groups'] ?? [];
		if (!is_array($upload_opts['google_groups'])) $upload_opts['google_groups'] = [];

		$upload_opts['upload_auth_mode']   = $upload_opts['upload_auth_mode'] ?? '';
		$upload_opts['upload_password']    = $upload_opts['upload_password'] ?? '';
		$upload_opts['upload_destination'] = $upload_opts['upload_destination'] ?? '';
		$upload_opts['wp_upload_root']     = $upload_opts['wp_upload_root'] ?? '';
	*/								
													   
        // Normalize calendar IDs as array
        $calendars_array = is_array($calendars) ? $calendars : explode(',', (string)$calendars);
        $calendars_array = array_map('trim', $calendars_array);

        $last      = (int) get_option('kgsweb_last_refresh', 0);
        $last_text = $last > 0 ? date_i18n('m/d/Y g:i A T', $last) : '';

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
					
					<!-- Google Drive Folder IDs -->
                    <h2>Google Drive Folder IDs (Global Defaults)</h2>
                    <table class="form-table">
                        <tr><th>Public Documents Root Folder (Google Drive)</th><td><input type="text" name="root_folder_id" value="<?php echo esc_attr($root_folder); ?>" size="50"></td></tr>
						<tr><th>Documents Upload Root Folder (Google Drive)</th><td><input type="text" name="upload_root_folder_id" value="<?php echo esc_attr($upload_root_folder); ?>" size="50"></td></tr>						
						<tr><th>Breakfast Folder</th><td><input type="text" name="breakfast_folder_id" value="<?php echo esc_attr($breakfast); ?>" size="50"></td></tr>
                        <tr><th>Lunch Folder</th><td><input type="text" name="lunch_folder_id" value="<?php echo esc_attr($lunch); ?>" size="50"></td></tr>
                        <tr><th>Ticker Folder</th><td><input type="text" name="ticker_file_id" value="<?php echo esc_attr($ticker); ?>" size="50"></td></tr>

                    </table>

                    <!-- Calendar IDs -->
                    <h2>Calendar IDs</h2>
                    <p>Enter one or more Google Calendar IDs (comma-separated) to display upcoming events.</p>
                    <input type="text" name="calendar_ids" value="<?php echo esc_attr(implode(',', $calendars_array)); ?>" size="50">

                    <!-- Calendar URL -->
                    <h2>Calendar URL</h2>
                    <p>Enter the URL for the Calendar page view. This is used for the "View Calendar" link in the events shortcode.</p>
                    <input type="url" name="calendar_url" value="<?php echo esc_attr($calendar_url); ?>" size="50">


                    <!-- Secure Upload Settings -->
	           
                <h2>Secure Upload Settings</h2>
                <table class="form-table">
                   <tr>
						<th>Authorization Mode</th>
						<td>
							<select name="upload_auth_mode">
								<option value="password" <?php selected($upload_auth_mode, 'password'); ?>>Password</option>
								<option value="google_group" <?php selected($upload_auth_mode, 'google_group'); ?>>Google Group</option>
							</select>
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
							<input type="text" name="google_groups" value="<?php echo esc_attr(implode(',', $google_groups)); ?>" size="50"><br>
							<small>Comma-separated email addresses or groups</small>
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
							<input type="text" name="wp_upload_root" value="<?php echo esc_attr($wp_upload_root); ?>" size="50"><br>
							<small>Used only if destination is WordPress</small>
						</td>
					</tr>
                </table>


                    <!-- Save Settings -->
                    <p class="submit">
                        <input type="submit" name="kgsweb_save_settings" id="submit" class="button button-primary" value="Save Settings">
                    </p>

                    <hr />

                    <!-- Cache Buttons -->
                    <h2>Cache Management</h2>
                    <p>Last cache refresh: <?php echo esc_html($last_text); ?></p>
                    <button type="submit" name="kgsweb_update_cache" class="button">Update Cache Now</button>
                    <button type="submit" name="kgsweb_clear_cache" class="button">Clear All Cache</button>

	  
                </form>
            </div>
	  
		 
			<hr />
            <div class="kgsweb-shortcode-help">
			  <h2>Available Shortcodes</h2>
			  <p>You can use these shortcodes anywhere in posts, pages, or widgets</p>
			  <ul class="kgsweb-shortcode-list">
				<li>
				  <code>[kgsweb_documents doc-folder="FOLDER_ID"]</code> &nbsp; 
				  <i>Accordion-style folder tree from Drive; excludes empty folders</i>
				</li>
				<li>
				  <code>[kgsweb_secure_upload upload-folder="FOLDER_ID"]</code> &nbsp; 
				  <i>Upload form gated by password or Google Group; one file per upload</i>
				</li>
				<li>
				  <code>[kgsweb_events calendar_id="CALENDAR_ID"]</code> &nbsp; 
				  <i>Displays 10 upcoming Google Calendar events with pagination (caches 100 events)</i>
				</li>
				<li>
				  <code>[kgsweb_menu type="breakfast"]</code>, 
				  <code>[kgsweb_menu type="lunch"]</code> &nbsp; 
				  <i>Displays latest image from Drive folder; converts PDF to PNG if needed</i>
				</li>
				<li>
				  <code>[kgsweb_ticker folder="FOLDER_ID"]</code> &nbsp; 
				  <i>Displays horizontally scrolling text from a Google Doc or .txt file</i>
				</li>
				<li>
				  <code>[kgsweb_slides file="FILE_ID"]</code> &nbsp; 
				  <i>Embeds Google Slides presentation</i>
				</li>
				<li class="kgsweb-shortcode-example">
				  Example (slideshow): 
				  <code>[kgsweb_slides file="GOOGLE_SLIDES_FILE_ID" width="800px" height="600px"]</code>
				</li>
				<li class="kgsweb-shortcode-example">
				  Example (force PDFs): 
				  <code>[kgsweb_slides file="GOOGLE_SLIDES_FILE_ID" force_pdf="true" width="800px" height="600px"]</code>
				</li>
				<li>
				  <code>[kgsweb_sheets sheet_id="SHEET_ID" range="A1:Z100"]</code> &nbsp; 
				  <i>Displays Google Sheets data in specified range</i>
				</li>
				<li>
				  <code>[kgsweb_current_datetime format="FORMAT"]</code> &nbsp; 
				  <i>Displays current time/date in specified format or alias</i>
				</li>
			  </ul>
			  <h3>Notes</h3>
			  <ul>
				<li><?php esc_html_e( 'You must have a valid Google service account with access to the specified Drive folders and Calendars.', 'kgsweb' ); ?></li>
				<li><?php esc_html_e( 'Menus and ticker fetch the latest file from the folder.', 'kgsweb' ); ?></li>
				<li><?php esc_html_e( 'Sheets and Slides require IDs passed in the shortcode.', 'kgsweb' ); ?></li>
				<li><?php esc_html_e( 'Shortcodes can be used independently on different pages.', 'kgsweb' ); ?></li>
			  </ul>
			  <hr />
			  <div class="secure-upload-settings"></div>											   
				   
        </div>
        <?php
    } // end render_settings_page
 

} // end class KGSweb_Google_Admin