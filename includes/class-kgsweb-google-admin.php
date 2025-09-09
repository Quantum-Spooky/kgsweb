<?php
// includes/class-kgsweb-google-admin.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );

        // Optional debug: logs incoming settings arrays on POST (keep or remove as needed)
        add_action( 'admin_init', [ __CLASS__, 'log_settings_post' ] );
    }

    public static function menu() {
        add_menu_page(
            __( 'KGS Web Integration', 'kgsweb' ),
            __( 'KGS Web', 'kgsweb' ),
            'manage_options',
            'kgsweb-settings',
            [ __CLASS__, 'render_settings_page' ],
            'dashicons-media-spreadsheet',
            82
        );
    }

    public static function register_settings() {
        // Registering is still useful to centralize section/field definitions, even though we save inline.
        register_setting( 'kgsweb_settings_group', KGSWEB_SETTINGS_OPTION, [
            'type'              => 'array',
            // We're not using the Settings API submission, but keep this for consistency.
            'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
            'default'           => []
        ] );

        // Google Configuration
        add_settings_section( 'kgsweb_google', __( 'Google Configuration', 'kgsweb' ), '__return_false', 'kgsweb-settings' );
        add_settings_field(
            'service_account_json',
            'Service Account JSON',
            [ __CLASS__, 'field_textarea' ],
            'kgsweb-settings',
            'kgsweb_google',
            [
                'key'         => 'service_account_json',
                'rows'        => 10,
                'label'       => 'Service Account JSON',
                'description' => 'Paste the full JSON credentials for your Google service account. Enables access to Drive, Calendar, Slides, and Sheets APIs.'
            ]
        );

        // Default IDs
        add_settings_section( 'kgsweb_ids', __( 'Default IDs', 'kgsweb' ), '__return_false', 'kgsweb-settings' );
        $default_id_fields = [
            'public_docs_root_id'      => [ 'Public Documents Root Folder ID', 'Google Drive folder ID containing public-facing documents for the school community.' ],
            'upload_root_id'           => [ 'Documents Upload Root Folder ID', 'Google Drive folder ID where uploaded files will be stored. Must be writable by the service account.' ],
            'menu_breakfast_folder_id' => [ 'Breakfast Menu Folder ID', 'Google Drive folder ID containing breakfast menu PDFs or images.' ],
            'menu_lunch_folder_id'     => [ 'Lunch Menu Folder ID', 'Google Drive folder ID containing lunch menu PDFs or images.' ],
            'ticker_file_id'           => [ 'Ticker File ID', 'Google Drive file ID for the scrolling ticker text. Should be a plain text file readable by the service account.' ],
            'calendar_id'              => [ 'Google Calendar ID', 'Calendar ID for upcoming events. Can be a public calendar or one shared with the service account.' ],
            'slides_file_id'           => [ 'Default Slides File ID', 'Google Slides file ID for the homepage slideshow.' ],
            'sheets_file_id'           => [ 'Default Sheets File ID', 'Google Sheets file ID for tabular data display.' ],
            'sheets_default_range'     => [ 'Default Sheets Range', 'Range to fetch from the default Sheets file (e.g., A1:Z100). Controls which cells are displayed.' ],
            'calendar_page_url'        => [ 'Full Calendar Page URL', 'Optional: URL of a full calendar page for users to view all events.' ],
        ];
        foreach ( $default_id_fields as $key => [ $label, $desc ] ) {
            add_settings_field(
                $key,
                $label,
                [ __CLASS__, 'field_text' ],
                'kgsweb-settings',
                'kgsweb_ids',
                [
                    'key'         => $key,
                    'label'       => $label,
                    'description' => $desc
                ]
            );
        }

        // Upload Settings
        add_settings_section( 'kgsweb_upload', __( 'Upload Settings', 'kgsweb' ), '__return_false', 'kgsweb-settings' );
        $upload_fields = [
            'upload_auth_mode' => [
                'Upload Auth Mode',
                'Choose how users authenticate before uploading: password-based or Google Group membership.',
                'select',
                [ 'password' => 'Password', 'google_group' => 'Google Group' ]
            ],
            'upload_google_group' => [
                'Approved Google Group',
                'If using Google Group auth, specify the group email (e.g., staff@school.org). Only members can upload.',
                'text'
            ],
            'upload_destination' => [
                'Upload Destination',
                'Choose whether uploaded files go to Google Drive or the WordPress Media Library.',
                'select',
                [ 'drive' => 'Google Drive', 'wordpress' => 'WordPress' ]
            ],
            'wp_upload_root_path' => [
                'WP Upload Root Path',
                'If using WordPress uploads, specify the subfolder path under /wp-content/uploads/ where files should be stored.',
                'text'
            ],
            'upload_password_plaintext' => [
                'Upload Password (plaintext, admin-only)',
                'Password used for secure uploads. Stored hashed for validation, but shown here for admin reference. Never shared publicly.',
                'text'
            ],
        ];
        foreach ( $upload_fields as $key => $field ) {
            [ $label, $desc, $type ] = $field;
            $options  = isset( $field[3] ) ? $field[3] : [];
            $callback = $type === 'select' ? 'field_select' : 'field_text';
            $args     = [
                'key'         => $key,
                'label'       => $label,
                'description' => $desc,
            ];
            if ( $type === 'select' ) {
                $args['options'] = $options;
            }
            add_settings_field( $key, $label, [ __CLASS__, $callback ], 'kgsweb-settings', 'kgsweb_upload', $args );
        }

        // Misc & Debug
        add_settings_section( 'kgsweb_misc', __( 'Misc & Debug', 'kgsweb' ), '__return_false', 'kgsweb-settings' );
        add_settings_field(
            'debug_enabled',
            'Enable Debug',
            [ __CLASS__, 'field_checkbox' ],
            'kgsweb-settings',
            'kgsweb_misc',
            [
                'key'         => 'debug_enabled',
                'label'       => 'Enable Debug',
                'description' => 'Enable verbose logging and REST diagnostics. Useful for troubleshooting API calls and caching behavior.'
            ]
        );
    }

    public static function sanitize_settings( $input ) {
        $out = is_array( $input ) ? $input : [];

        $keys_text = [
            'public_docs_root_id','upload_root_id','menu_breakfast_folder_id','menu_lunch_folder_id',
            'ticker_file_id','calendar_id','slides_file_id','sheets_file_id','sheets_default_range',
            'upload_auth_mode','upload_google_group','upload_destination','wp_upload_root_path',
            'upload_password_plaintext','calendar_page_url'
        ];
        foreach ( $keys_text as $k ) {
            if ( isset( $out[ $k ] ) ) {
                $out[ $k ] = sanitize_text_field( $out[ $k ] );
            }
        }

        if ( isset( $out['service_account_json'] ) ) {
            // Store as-is but validate structure
            $try = json_decode( $out['service_account_json'], true );
            if ( ! is_array( $try ) || empty( $try['client_email'] ) || empty( $try['private_key'] ) ) {
                add_settings_error( KGSWEB_SETTINGS_OPTION, 'invalid_sa', __( 'Invalid service account JSON.', 'kgsweb' ) );
            }
        }

        $out['debug_enabled'] = ! empty( $out['debug_enabled'] );

        if ( isset( $out['upload_password_plaintext'] ) && ! empty( $out['upload_password_plaintext'] ) ) {
            $key = defined( 'KGSWEB_PASSWORD_SECRET_KEY' ) ? KGSWEB_PASSWORD_SECRET_KEY : '';
            if ( $key ) {
                $out['upload_password_hash'] = hash_hmac( 'sha256', $out['upload_password_plaintext'], $key );
            }
        }

        return $out;
    }

    public static function field_text( $args ) {
        $opt  = KGSweb_Google_Integration::get_settings();
        $k    = esc_attr( $args['key'] );
        $desc = isset( $args['description'] ) ? esc_html( $args['description'] ) : '';
        echo '<label for="' . $k . '">' . $args['label'];
        if ( $desc ) {
            echo ' <span class="kgsweb-tooltip"><i class="fas fa-question-circle" aria-hidden="true"></i><span class="kgsweb-tooltip-text">' . $desc . '</span></span>';
        }
        echo '</label><br />';
        printf(
            '<input type="text" class="regular-text" name="%s[%s]" value="%s"/>',
            esc_attr( KGSWEB_SETTINGS_OPTION ),
            $k,
            isset( $opt[ $k ] ) ? esc_attr( $opt[ $k ] ) : ''
        );
    }

    public static function field_textarea( $args ) {
        $opt  = KGSweb_Google_Integration::get_settings();
        $k    = esc_attr( $args['key'] );
        $rows = isset( $args['rows'] ) ? intval( $args['rows'] ) : 5;
        printf(
            '<textarea rows="%d" class="large-text code" name="%s[%s]">%s</textarea>',
            $rows,
            esc_attr( KGSWEB_SETTINGS_OPTION ),
            $k,
            isset( $opt[ $k ] ) ? esc_textarea( $opt[ $k ] ) : ''
        );
        echo '<p class="description">' . esc_html__( 'Paste the full Service Account JSON here. Never shared publicly.', 'kgsweb' ) . '</p>';
    }

    public static function field_select( $args ) {
        $opt  = KGSweb_Google_Integration::get_settings();
        $k    = esc_attr( $args['key'] );
        $val  = isset( $opt[ $k ] ) ? $opt[ $k ] : '';
        $desc = isset( $args['description'] ) ? esc_html( $args['description'] ) : '';
        echo '<label for="' . $k . '">' . $args['label'];
        if ( $desc ) {
            echo ' <span class="kgsweb-tooltip"><i class="fas fa-question-circle" aria-hidden="true"></i><span class="kgsweb-tooltip-text">' . $desc . '</span></span>';
        }
        echo '</label><br />';
        echo '<select name="' . esc_attr( KGSWEB_SETTINGS_OPTION ) . '[' . $k . ']">';
        foreach ( $args['options'] as $v => $label ) {
            printf( '<option value="%s" %s>%s</option>', esc_attr( $v ), selected( $val, $v, false ), esc_html( $label ) );
        }
        echo '</select>';
    }

    public static function field_checkbox( $args ) {
        $opt     = KGSweb_Google_Integration::get_settings();
        $k       = esc_attr( $args['key'] );
        $checked = ! empty( $opt[ $k ] );
        printf(
            '<label><input type="checkbox" name="%s[%s]" value="1" %s/> %s</label>',
            esc_attr( KGSWEB_SETTINGS_OPTION ),
            $k,
            checked( $checked, true, false ),
            esc_html__( 'Enable', 'kgsweb' )
        );
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

    // Handle settings save (inline)
	if ( isset( $_POST['kgsweb_save_settings'] ) ) {
		check_admin_referer( 'kgsweb_inline_save', '_wpnonce_kgsweb_save' );
		$input     = $_POST['kgsweb_settings'] ?? [];
		$sanitized = self::sanitize_settings( $input );
		update_option( KGSWEB_SETTINGS_OPTION, $sanitized );

		add_settings_error(
			'kgsweb_messages',
			'kgsweb_message',
			__( 'Settings saved successfully.', 'kgsweb' ),
			'updated'
		);

		// Redirect to avoid resubmission and show notice
		wp_redirect( add_query_arg( 'settings-updated', 'true', menu_page_url( 'kgsweb-settings', false ) ) );
		exit;
	}

	// Display any notices
	settings_errors( 'kgsweb_messages' );

        // Handle cache rebuild (inline)
        if ( isset( $_POST['kgsweb_rebuild_caches'] ) ) {
            check_admin_referer( 'kgsweb_inline_rebuild', '_wpnonce_kgsweb_rebuild_inline' );
            KGSweb_Google_Integration::cron_refresh_all_caches();
            update_option( 'kgsweb_last_refresh', time() );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Caches rebuilt successfully.', 'kgsweb' ) . '</p></div>';
        }

        // Last cache refresh
        $last = (int) get_option( 'kgsweb_last_refresh', 0 );
        if ( $last > 0 ) {
            $last_text = date_i18n( 'M j, Y g:i a', $last );
            echo '<div style="margin:12px 0; padding:8px 12px; background:#f6f7f7; border:1px solid #dcdcde; border-radius:4px;"><em>' .
                 esc_html__( 'Last cache refresh:', 'kgsweb' ) . ' ' . esc_html( $last_text ) . '</em></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'KGS Web Integration', 'kgsweb' ); ?></h1>

            <h2><?php esc_html_e( 'Plugin Settings', 'kgsweb' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'kgsweb_inline_save', '_wpnonce_kgsweb_save' ); ?>
                <input type="hidden" name="kgsweb_save_settings" value="1">
                <?php do_settings_sections( 'kgsweb-settings' ); ?>
                <?php submit_button( __( 'Save Settings', 'kgsweb' ) ); ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Cache Controls', 'kgsweb' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'kgsweb_inline_rebuild', '_wpnonce_kgsweb_rebuild_inline' ); ?>
                <input type="hidden" name="kgsweb_rebuild_caches" value="1">
                <?php submit_button( __( 'Rebuild Caches Now', 'kgsweb' ), 'secondary' ); ?>
            </form>

            <hr>

            <div class="kgsweb-shortcode-help">
                <h2><?php esc_html_e( 'Available Shortcodes', 'kgsweb' ); ?></h2>
                <p><?php esc_html_e( 'You can use these shortcodes anywhere in posts, pages, or widgets:', 'kgsweb' ); ?></p>
                <ul style="list-style-type: none; padding-left: 0;">
                    <li><code>[kgsweb_documents doc-folder="FOLDER_ID"]</code> &nbsp; <i>Accordion-style folder tree from Drive; excludes empty folders</i></li>
                    <li><code>[kgsweb_secure_upload_form upload-folder="FOLDER_ID"]</code> &nbsp; <i>Upload form gated by password or Google Group; one file per upload</i></li>
                    <li><code>[kgsweb_events calendar_id="CALENDAR_ID"]</code> &nbsp; <i>Displays 10 upcoming Google Calendar events with pagination (caches 100 events)</i></li>
                    <li><code>[kgsweb_menu type="breakfast"]</code>, <code>[kgsweb_menu type="lunch"]</code> &nbsp; <i>Displays latest image from Drive folder; converts PDF to PNG if needed</i></li>
                    <li><code>[kgsweb_ticker folder="FOLDER_ID"]</code> &nbsp; <i>Displays horizontally scrolling text from a Google Doc or .txt file</i></li>
                    <li><code>[kgsweb_slides file="FILE_ID"]</code> &nbsp; <i>Embeds Google Slides presentation</i></li>
                    <li><code>[kgsweb_sheets sheet_id="SHEET_ID" range="A1:Z100"]</code> &nbsp; <i>Displays Google Sheets data in specified range</i></li>
                    <li><code>[kgsweb_current_datetime format="FORMAT"]</code> &nbsp; <i>Displays current time/date in specified format or alias</i></li>
                </ul>
                <h3><?php esc_html_e( 'Notes', 'kgsweb' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'You must have a valid Google service account with access to the specified Drive folders and Calendars.', 'kgsweb' ); ?></li>
                    <li><?php esc_html_e( 'Menus and ticker fetch the latest file from the folder.', 'kgsweb' ); ?></li>
                    <li><?php esc_html_e( 'Sheets and Slides require IDs passed in the shortcode.', 'kgsweb' ); ?></li>
                    <li><?php esc_html_e( 'Shortcodes can be used independently on different pages.', 'kgsweb' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
		}

    /* Debug */
    public static function log_settings_post() {
        if ( isset( $_POST['kgsweb_settings'] ) ) {
            error_log( print_r( $_POST['kgsweb_settings'], true ) );
        }
    }
}
