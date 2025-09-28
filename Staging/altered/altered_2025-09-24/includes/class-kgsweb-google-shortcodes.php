<?php
// includes/class-kgsweb-google-shortcodes.php
if (!defined('ABSPATH')) { exit; }


class KGSweb_Google_Shortcodes {

    private static ?self $instance = null;

    /*******************************
     * Singleton init
     *******************************/
    public static function init(): self {
        if (self::$instance === null) {
            self::$instance = new self();
            // Hook shortcodes registration into 'init'
            add_action('init', [self::$instance, 'register_shortcodes']);
        }
        return self::$instance;
    }

    /*******************************
     * Register all shortcodes
     *******************************/
    public function register_shortcodes() {
        add_shortcode('kgsweb_documents', [$this, 'documents']);
        add_shortcode('kgsweb_secure_upload', [__CLASS__, 'secure_upload_form']);
        add_shortcode('kgsweb_menu', [KGSweb_Google_Menus::class, 'shortcode_render']);
        // add_shortcode('kgsweb_ticker', [$this, 'render_ticker']);
        // add_shortcode('kgsweb_sheets', [KGSweb_Google_Sheets::class, 'shortcode_render']);
        add_shortcode('kgsweb_slides', [KGSweb_Google_Slides::class, 'shortcode_render']);
        add_shortcode('kgsweb_events', [__CLASS__, 'kgsweb_events_shortcode']);
        add_shortcode('kgsweb_calendar', [__CLASS__, 'kgsweb_events_shortcode']);
    }

    /*******************************
     * Asset registration
     *******************************/
    public static function enqueue_if_needed($handles = []) {
        wp_enqueue_style('kgsweb-style');
        foreach ((array)$handles as $h) wp_enqueue_script($h);
    }

    public static function register_assets() {
        $ver = '0.1.0';
        $settings = KGSweb_Google_Integration::get_settings();

        wp_register_style(
            'kgsweb-style',
            plugins_url('/css/kgsweb-style.css', KGSWEB_PLUGIN_FILE),
            [],
            $ver
        );

        wp_register_script(
            'kgsweb-folders',
            plugins_url('/js/kgsweb-folders.js', KGSWEB_PLUGIN_FILE),
            [],
            $ver,
            true
        );

        wp_register_script(
            'kgsweb-ticker',
            plugins_url('/js/kgsweb-ticker.js', KGSWEB_PLUGIN_FILE),
            [],
            $ver,
            true
        );

        wp_localize_script('kgsweb-folders', 'KGSwebFolders', [
            'restUrl' => esc_url_raw(rest_url('kgsweb/v1/documents')),
            'rootId'  => $settings['public_docs_root_id'] ?? '',
        ]);
    }

    public static function enqueue_documents_assets() {
        wp_enqueue_style('kgsweb-style');
        wp_enqueue_script('kgsweb-folders');
    }

    /*******************************
     * Documents shortcode
     *******************************/					  
 
    public static function documents($atts = []) {
        $atts = shortcode_atts([
			'doc-folder' => '',
			'folder'     => '',
			'folders'    => '',
			'class'      => '',
			'id'         => 'kgsweb-documents-tree',
			'sort_by'    => 'alpha-asc',         // new: default alphabetical ascending
			'collapsed'  => 'true',              // new: default collapsed
		], $atts, 'kgsweb_documents');

        // Enqueue assets only when shortcode is used
        self::enqueue_documents_assets();

        $settings = KGSweb_Google_Integration::get_settings();
        $root = $atts['doc-folder'] ?: $atts['folder'] ?: $atts['folders'] ?: ($settings['public_docs_root_id'] ?? '');

        $id   = preg_replace('/[^a-zA-Z0-9\-\_\:]/', '-', $atts['id']);
        $class = esc_attr($atts['class']);

        $data_attrs = sprintf(' data-root-id="%s"', esc_attr($root));
		
		$data_attrs .= sprintf(
			' data-sort="%s" data-collapsed="%s"',
			esc_attr($atts['sort_by']),
			esc_attr($atts['collapsed'])
		);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($id); ?>" class="kgsweb-documents <?php echo $class; ?>"<?php echo $data_attrs; ?>>
            <div class="kgsweb-docs-loading" role="status" aria-live="polite">Loading documents…</div>
            <noscript>JavaScript is required to view the documents tree.</noscript>
        </div>
        <?php
        return ob_get_clean();
    }

    /*******************************
     * Events shortcode
     *******************************/
	 public static function kgsweb_events_shortcode($atts) {
		$atts = shortcode_atts([
			'calendar_id' => get_option('kgsweb_default_calendar_id', ''),
		], $atts, 'kgsweb_events');

		// Get events from cache
		$integration = KGSweb_Google_Integration::init();
		$events = $integration->get_cached_events($atts['calendar_id']);

		// Decide which URL to use for the calendar link
		$calendar_url = get_option('kgsweb_calendar_url', '');
		if (empty($calendar_url) && !empty($atts['calendar_id'])) {
			// Fallback: auto-generate a Google Calendar embed link
			$calendar_url = 'https://calendar.google.com/calendar/embed?src=' . urlencode($atts['calendar_id']);
		}

		// Pass events to JS
		wp_enqueue_script('kgsweb-calendar');
		wp_localize_script('kgsweb-calendar', 'kgswebCalendarData', [
			'events' => array_values($events), // flatten to numeric array
		]);

		ob_start();
		?>
		<div class="kgsweb-calendar" data-calendar-id="<?php echo esc_attr($atts['calendar_id']); ?>">
			<ul class="events-list"></ul>

			<div class="calendar-controls">
				<button class="prev">&laquo; Prev</button>
				<?php if (!empty($calendar_url)) : ?>
					<div class="calendar-link">
						<a href="<?php echo esc_url($calendar_url); ?>" target="_top" rel="noopener">
							View Calendar
						</a>
					</div>
				<?php endif; ?>
				<button class="next">Next &raquo;</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

/*******************************
 * Secure Upload Form Shortcode
 *******************************/
public static function secure_upload_form($atts) {
    $atts = shortcode_atts([
        'upload-folder' => ''
    ], $atts);

    ob_start();
    ?>
    <div class="kgsweb-secure-upload-form" data-upload-folder="<?php echo esc_attr($atts['upload-folder']); ?>">
        <form class="kgsweb-password-form">
            <label for="upload_password">Password:</label>
            <div class="password-container">
                <input type="password" id="upload_password" name="kgsweb_upload_pass">
                <i class="fas fa-eye toggle_password"></i>
            </div>
            <button type="submit" class="kgsweb-password-submit">Unlock</button>
            <div class="kgsweb-password-error" style="color:red; margin-top:0.5rem; display:none;"></div>
        </form>
        <div class="kgsweb-upload-ui" style="display:none;">
            <select class="kgsweb-upload-folder"></select>
            <input type="file" class="kgsweb-upload-file" />
            <button class="kgsweb-upload-btn">Upload</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

	
    /*******************************
     * Sheets shortcode
     *******************************/
    public static function sheets($atts) {
        $a = shortcode_atts([
            'sheet_id' => '',
            'range'    => '',
        ], $atts, 'kgsweb_sheets');

        $id = $a['sheet_id'] ?: (KGSweb_Google_Integration::get_settings()['sheets_file_id'] ?? '');
        $range = $a['range'] ?: (KGSweb_Google_Integration::get_settings()['sheets_default_range'] ?? 'A1:Z100');

        return '<div class="kgsweb-sheets" data-sheet-id="' . esc_attr($id) . '" data-range="' . esc_attr($range) . '"></div>';
    }
	
	
	
    /*******************************	
    * Other shortcodes: documents, events, menu, upload, slides
    *******************************/
	  
    public static function menu($atts) { /* ... */ }
    public static function slides($atts) { /* ... */ }
	
	
}
