<?php
// includes/class-kgsweb-google-shortcodes.php
if ( ! defined( 'ABSPATH' ) ) { exit; }


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
        add_shortcode('kgsweb_secure_upload_form', [$this, 'secure_upload_form']);		
        add_shortcode('kgsweb_menu', [$this, 'menu']);
        add_shortcode('kgsweb_ticker', [$this, 'ticker']);
        add_shortcode('kgsweb_calendar', [$this, 'events']);
        add_shortcode('kgsweb_datetime', [$this, 'current_datetime']);
        add_shortcode('kgsweb_sheets', [$this, 'sheets']);
        add_shortcode('kgsweb_slides', [$this, 'slides']);
    }
	  
	public static function enqueue_if_needed( $handles = [] ) {
        wp_enqueue_style( 'kgsweb-style' );
        foreach ( (array)$handles as $h ) { wp_enqueue_script( $h ); }
    }

    public static function current_datetime( $atts ) {
        $atts = shortcode_atts( [ 'format' => 'long' ], $atts, 'kgsweb_current_datetime' );
        // Resolve alias to PHP date format
        $map = [
            'short'     => 'h:ii xm n/j/yy',
            'med'       => 'h:ii D M j, Y',
            'long'      => 'h:ii a, l, F d, Y',
            'time'      => 'h:ii xm',
            'shortdate' => 'n/j/yy',
            'meddate'   => 'D M j, Y',
            'longdate'  => 'l, F d, Y',
        ];
        $fmt = isset($map[$atts['format']]) ? $map[$atts['format']] : $atts['format'];
        // Render server-side for reliability; JS can enhance if needed.
        $tz = wp_timezone();
        $dt = new DateTime( 'now', $tz );
        return '<span class="kgsweb-datetime" data-format="'.esc_attr($fmt).'">'.esc_html( $dt->format( $fmt ) ).'</span>';
    }

    public static function ticker( $atts ) {
        $a = shortcode_atts( [ 'folder' => '' ], $atts, 'kgsweb_ticker' );
        self::enqueue_if_needed( ['kgsweb-helpers','kgsweb-cache','kgsweb-ticker'] );
        $folder = $a['folder'] ?: ( KGSweb_Google_Integration::get_settings()['ticker_file_id'] ?? '' );
        ob_start(); ?>
        <div class="kgsweb-ticker" data-folder="<?php echo esc_attr($folder); ?>" data-speed="0.5">
            <div class="kgsweb-ticker-track"></div>
            <div class="kgsweb-ticker-controls">
                <button class="kgsweb-ticker-toggle" aria-label="Pause/Play"></button>
                <button class="kgsweb-ticker-expand" aria-expanded="false" aria-controls="kgsweb-ticker-full">Show</button>
            </div>
            <div class="kgsweb-ticker-full" id="kgsweb-ticker-full" hidden></div>
        </div>
        <?php return ob_get_clean();
    }

    public static function events( $atts ) {
        $a = shortcode_atts( [ 'calendar_id' => '' ], $atts, 'kgsweb_events' );
        self::enqueue_if_needed( ['kgsweb-helpers','kgsweb-cache','kgsweb-calendar'] );
        $cal = $a['calendar_id'] ?: ( KGSweb_Google_Integration::get_settings()['calendar_id'] ?? '' );
        $page_url = KGSweb_Google_Integration::get_settings()['calendar_page_url'] ?? '#';
        return '<div class="kgsweb-events" data-calendar-id="'.esc_attr($cal).'" data-page-url="'.esc_url($page_url).'"></div>';
    }

    public static function menu( $atts ) {
        $a = shortcode_atts( [ 'type' => 'breakfast' ], $atts, 'kgsweb_menu' );
        self::enqueue_if_needed( ['kgsweb-helpers','kgsweb-cache','kgsweb-menus'] );
        return '<div class="kgsweb-menu" data-type="'.esc_attr($a['type']).'"><div class="kgsweb-menu-image"></div></div>';
    }
		
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
	
	
	public static function register_assets() {
		$ver = '0.1.0';

		wp_register_style(
			'kgsweb-style',
			plugins_url( '/css/kgsweb-style.css', KGSWEB_PLUGIN_FILE ),
			[],
			$ver
		);

		wp_register_script(
			'kgsweb-folders',
			plugins_url( '/js/kgsweb-folders.js', KGSWEB_PLUGIN_FILE ),
			[],
			$ver,
			true
		);

		wp_localize_script( 'kgsweb-folders', 'KGSwebFolders', [
			'restUrl' => esc_url_raw( rest_url( 'kgsweb/v1/documents' ) ),
		]);
	}

	public static function enqueue_documents_assets() {
		wp_enqueue_style( 'kgsweb-style' );
		wp_enqueue_script( 'kgsweb-folders' );
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


    public static function slides( $atts ) {
        $a = shortcode_atts( [ 'file' => '' ], $atts, 'kgsweb_slides' );
        $file = $a['file'] ?: ( KGSweb_Google_Integration::get_settings()['slides_file_id'] ?? '' );
        // For now, embed via Google Slides public URL if available (cache layer to be implemented in REST)
        return '<div class="kgsweb-slides" data-file-id="'.esc_attr($file).'"></div>';
    }

    public static function sheets( $atts ) {
        $a = shortcode_atts( [ 'sheet_id' => '', 'range' => '' ], $atts, 'kgsweb_sheets' );
        $id = $a['sheet_id'] ?: ( KGSweb_Google_Integration::get_settings()['sheets_file_id'] ?? '' );
        $range = $a['range'] ?: ( KGSweb_Google_Integration::get_settings()['sheets_default_range'] ?? 'A1:Z100' );
        return '<div class="kgsweb-sheets" data-sheet-id="'.esc_attr($id).'" data-range="'.esc_attr($range).'"></div>';
    }
}