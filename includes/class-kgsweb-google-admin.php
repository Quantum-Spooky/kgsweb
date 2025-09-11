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
            'datetime',
            'folders',
            'helpers',
            'menus',
            'ticker',
            'upload',
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

        // Google Integration options
        update_option('kgsweb_service_account_json', stripslashes($_POST['service_account_json']));
        update_option('kgsweb_root_folder_id', sanitize_text_field($_POST['root_folder_id']));
        update_option('kgsweb_breakfast_folder_id', sanitize_text_field($_POST['breakfast_folder_id']));
        update_option('kgsweb_lunch_folder_id', sanitize_text_field($_POST['lunch_folder_id']));
        update_option('kgsweb_ticker_folder_id', sanitize_text_field($_POST['ticker_folder_id']));
        update_option('kgsweb_calendar_ids', sanitize_text_field($_POST['calendar_ids']));
        update_option('kgsweb_upload_root_folder_id', sanitize_text_field($_POST['upload_root_folder_id']));

        // Secure Upload Settings
        $upload_opts = [
            'upload_auth_mode'   => sanitize_text_field($_POST['upload_auth_mode'] ?? 'password'),
            'upload_password'    => sanitize_text_field($_POST['upload_password'] ?? ''),
            'google_groups'      => array_map('trim', is_array($_POST['google_groups'] ?? null) ? $_POST['google_groups'] : explode(',', (string)($_POST['google_groups'] ?? ''))),
            'upload_destination' => sanitize_text_field($_POST['upload_destination'] ?? 'drive'),
            'wp_upload_root'     => sanitize_text_field($_POST['wp_upload_root'] ?? ''),
        ];
        update_option('kgsweb_secure_upload_options', $upload_opts);

        echo "<div class='updated'><p>Settings saved!</p></div>";

        // Initialize or refresh Google Drive client
        $integration->get_drive();
    }

    // -------------------------------
    // Reset Lockouts
    // -------------------------------
    if (isset($_POST['kgsweb_reset_locked'])) {
        delete_option('kgsweb_upload_lockouts');
        global $wpdb;
        $transients = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '\_transient\_kgsweb\_attempts\_%'", ARRAY_A);
        foreach ($transients as $t) delete_transient(str_replace('_transient_', '', $t['option_name']));
        echo "<div class='updated'><p>Upload lockouts cleared.</p></div>";
    }

    // -------------------------------
    // Update Cache
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
    $upload_root_folder = get_option('kgsweb_upload_root_folder_id', '');
    $breakfast          = get_option('kgsweb_breakfast_folder_id', '');
    $lunch              = get_option('kgsweb_lunch_folder_id', '');
    $ticker             = get_option('kgsweb_ticker_folder_id', '');
    $calendars          = get_option('kgsweb_calendar_ids', '');
    $upload_opts        = get_option('kgsweb_secure_upload_options', []);

    // Ensure options are arrays
    if (!is_array($upload_opts)) $upload_opts = [];
    $upload_opts['google_groups'] = $upload_opts['google_groups'] ?? [];
    if (!is_array($upload_opts['google_groups'])) $upload_opts['google_groups'] = [];

    $upload_opts['upload_auth_mode']   = $upload_opts['upload_auth_mode'] ?? '';
    $upload_opts['upload_password']    = $upload_opts['upload_password'] ?? '';
    $upload_opts['upload_destination'] = $upload_opts['upload_destination'] ?? '';
    $upload_opts['wp_upload_root']     = $upload_opts['wp_upload_root'] ?? '';

    // Normalize calendar IDs as array for implode
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
                <textarea name="service_account_json" rows="12" cols="80"><?php echo esc_textarea($service_json); ?></textarea>

                <!-- Google Drive Folder IDs -->
                <h2>Google Drive Folder IDs (Global Defaults)</h2>
                <table class="form-table">
                    <tr><th>Public Documents Root Folder</th><td><input type="text" name="root_folder_id" value="<?php echo esc_attr($root_folder); ?>" size="50"></td></tr>
                    <tr><th>Documents Upload Root Folder</th><td><input type="text" name="upload_root_folder_id" value="<?php echo esc_attr($upload_root_folder); ?>" size="50"></td></tr>
                    <tr><th>Breakfast Folder</th><td><input type="text" name="breakfast_folder_id" value="<?php echo esc_attr($breakfast); ?>" size="50"></td></tr>
                    <tr><th>Lunch Folder</th><td><input type="text" name="lunch_folder_id" value="<?php echo esc_attr($lunch); ?>" size="50"></td></tr>
                    <tr><th>Ticker Folder</th><td><input type="text" name="ticker_folder_id" value="<?php echo esc_attr($ticker); ?>" size="50"></td></tr>
                </table>

                <!-- Calendar IDs -->
                <h2>Calendar IDs</h2>
                <p>Enter one or more Google Calendar IDs (comma-separated) to display upcoming events.</p>
                <input type="text" name="calendar_ids" value="<?php echo esc_attr(implode(',', $calendars_array)); ?>" size="50">

                <!-- Secure Upload Settings -->
                <h2>Secure Upload Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Authorization Mode</th>
                        <td>
                            <select name="upload_auth_mode">
                                <option value="password" <?php selected($upload_opts['upload_auth_mode'], 'password'); ?>>Password</option>
                                <option value="google_group" <?php selected($upload_opts['upload_auth_mode'], 'google_group'); ?>>Google Group</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Upload Password</th>
                        <td>
                            <input type="text" name="upload_password" value="<?php echo esc_attr($upload_opts['upload_password']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Reset Locked Users</th>
                        <td>
                            <button type="submit" name="kgsweb_reset_locked" class="button">Reset Lockouts</button>
                        </td>
                    </tr>
                    <tr>
                        <th>Google Groups Allowed</th>
                        <td>
                            <input type="text" name="google_groups" value="<?php echo esc_attr(implode(',', $upload_opts['google_groups'])); ?>" size="50"><br>
                            <small>Comma-separated email addresses or groups</small>
                        </td>
                    </tr>
                    <tr>
                        <th>Upload Destination</th>
                        <td>
                            <select name="upload_destination">
                                <option value="drive" <?php selected($upload_opts['upload_destination'], 'drive'); ?>>Google Drive</option>
                                <option value="wordpress" <?php selected($upload_opts['upload_destination'], 'wordpress'); ?>>WordPress</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>WordPress Upload Root</th>
                        <td>
                            <input type="text" name="wp_upload_root" value="<?php echo esc_attr($upload_opts['wp_upload_root']); ?>" size="50"><br>
                            <small>Used only if destination is WordPress</small>
                        </td>
                    </tr>
                </table>

                <!-- Cache Buttons -->
                <h2>Cache Management</h2>
                <p>Last cache refresh: <?php echo esc_html($last_text); ?></p>
                <button type="submit" name="kgsweb_update_cache" class="button">Update Cache Now</button>
                <button type="submit" name="kgsweb_clear_cache" class="button">Clear All Cache</button>

                <!-- Save Settings -->
                <p class="submit">
                    <input type="submit" name="kgsweb_save_settings" id="submit" class="button button-primary" value="Save Settings">
                </p>
            </form>
        </div>
    </div>
    <?php
}

}
