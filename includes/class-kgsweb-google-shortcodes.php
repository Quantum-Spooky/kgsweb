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
        //add_shortcode('kgsweb_secure_upload_form', [$this, 'secure_upload_form']);
        //add_shortcode('kgsweb_menu', [KGSweb_Google_Menus::class, 'shortcode_render']);
        //add_shortcode('kgsweb_ticker', [$this, 'render_ticker']);
        //add_shortcode('kgsweb_calendar', [$this, 'events']);
        //add_shortcode('kgsweb_sheets', [KGSweb_Google_Sheets::class, 'shortcode_render']);
        add_shortcode('kgsweb_slides', [KGSweb_Google_Slides::class, 'shortcode_render']);
    }
	
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

    // Other shortcodes: documents, events, menu, upload, slides
   
    public static function events($atts) { /* ... */ }
    public static function menu($atts) { /* ... */ }
    public static function slides($atts) { /* ... */ }
	
	public static function documents ( $atts = [] ) {
		$atts = shortcode_atts( [
			'doc-folder' => '',
			'folder'     => '',
			'folders'    => '',
			'class'      => '',
			'id'         => 'kgsweb-documents-tree',
		], $atts, 'kgsweb_documents' );

		// Enqueue assets only when shortcode is used
		self::enqueue_documents_assets();

		$settings = KGSweb_Google_Integration::get_settings();
		$root = $atts['doc-folder'] ?: $atts['folder'] ?: $atts['folders'] ?: ($settings['public_docs_root_id'] ?? '');

		$id   = preg_replace( '/[^a-zA-Z0-9\-\_\:]/', '-', $atts['id'] );
		$class = esc_attr( $atts['class'] );

		$data_attrs = sprintf( ' data-root-id="%s"', esc_attr( $root ) );

		ob_start();
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="kgsweb-documents <?php echo $class; ?>"<?php echo $data_attrs; ?>>
			<div class="kgsweb-docs-loading" role="status" aria-live="polite">Loading documents…</div>
			<noscript>JavaScript is required to view the documents tree.</noscript>
		</div>
		<?php
		return ob_get_clean();
	}
	
																	  
													   
    public static function secure_upload_form( $atts ) {
        $a = shortcode_atts( [ 'upload-folder' => '', 'folders' => '', 'folder' => '' ], $atts, 'kgsweb_secure_upload_form' );
        $settings = KGSweb_Google_Integration::get_settings();
        $root = $a['upload-folder'] ?? $a['folders'] ?? $a['folder'] ?? ($settings['upload_root_id'] ?? '');
        self::enqueue_if_needed( ['kgsweb-helpers','kgsweb-upload'] );
		
		// Minimal shell; JS controls password gate and group auth UI

        ob_start(); ?>
        <div class="kgsweb-upload" data-upload-folder="<?php echo esc_attr($root); ?>">
            <div class="kgsweb-upload-gate"></div>
            <form class="kgsweb-upload-form" method="post" enctype="multipart/form-data" hidden>
                <label><?php esc_html_e('Destination Folder','kgsweb'); ?>
                    <select name="folder_id" class="kgsweb-upload-dest"></select>
                </label>
                <label><?php esc_html_e('File','kgsweb'); ?>
                    <input type="file" name="file" required />
                </label>
                <button type="submit"><?php esc_html_e('Upload','kgsweb'); ?></button>
            </form>
            <div class="kgsweb-upload-status" aria-live="polite"></div>
        </div>
        <?php return ob_get_clean();
    }

    public static function sheets( $atts ) {
        $a = shortcode_atts( [ 'sheet_id' => '', 'range' => '' ], $atts, 'kgsweb_sheets' );
        $id = $a['sheet_id'] ?: ( KGSweb_Google_Integration::get_settings()['sheets_file_id'] ?? '' );
        $range = $a['range'] ?: ( KGSweb_Google_Integration::get_settings()['sheets_default_range'] ?? 'A1:Z100' );
        return '<div class="kgsweb-sheets" data-sheet-id="'.esc_attr($id).'" data-range="'.esc_attr($range).'"></div>';
    }																  
}
