<?php
// includes/class-kgsweb-google-display.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;

class KGSweb_Google_Display {

    /**
     * Map shortcode "type" to option keys in admin settings.
     * Used for predefined folder types (breakfast/lunch menus, calendars, feature images, etc.).
     */
    public static $types = [
        'breakfast-menu'    => 'kgsweb_breakfast_menu_folder_id',
        'lunch-menu'        => 'kgsweb_lunch_menu_folder_id',
        'monthly-calendar'  => 'kgsweb_monthly_calendar_folder_id',
        'academic-calendar' => 'kgsweb_academic_calendar_folder_id',
        'athletic-calendar' => 'kgsweb_athletic_calendar_folder_id',
        'feature-image'     => 'kgsweb_feature_image_folder_id',
        'pto-feature-image' => 'kgsweb_pto_feature_image_folder_id',
    ];

    /**
     * Initialize shortcode
     */
    public static function init() {
        add_shortcode('kgsweb_img_display', [__CLASS__, 'shortcode']);
    }

    /**
     * Shortcode handler
     * Usage examples:
     * [kgsweb_img_display type="monthly-calendar" width="80%"]
     * [kgsweb_img_display folder="FOLDER_ID" width="600px"]
     */
public static function shortcode($atts) {
    $atts = shortcode_atts([
        'type'   => '',
        'folder' => '', // allows specifying arbitrary folder
        'width'  => '', // optional width, px or %
    ], $atts, 'kgsweb_img_display');

    // Resolve folder ID: folder="" takes precedence over type
    $folder_id = '';
    $type      = trim($atts['type']); // keep type separate
    if (!empty($atts['folder'])) {
        $folder_id = sanitize_text_field($atts['folder']);
        if (empty($type)) $type = 'folder-display';
    } elseif (!empty($type) && isset(self::$types[$type])) {
        $folder_id = get_option(self::$types[$type]);
    }

    if (empty($folder_id)) return ''; // nothing to display
	
	
	
							error_log("Monthly Calendar folder ID: " . $folder_id);
							$payload = self::get_display_payload($type, $folder_id);
							if (is_wp_error($payload)) {
								error_log("Payload error: " . $payload->get_error_message());
							}

    // Fetch display payload (cached)	
    $payload = self::get_display_payload($type, $folder_id);
    if (is_wp_error($payload) || empty($payload['image_url'])) return '';
	


   

    // Optional inline width
    $inner_width_style = '';
    $raw_w = trim($atts['width']);
    if ($raw_w !== '') {
        $w = esc_attr($raw_w);
        if (is_numeric($w)) $w .= 'px';
        $inner_width_style = "width:{$w};";
    }

    // Prepare HTML output
    $img_url = esc_url($payload['image_url']);
    $alt     = esc_attr($type);

    $outer_style = 'style="text-align:center;"';
    $inner_style = 'style="display:inline-block;position:relative;max-width:100%;' . $inner_width_style . '"';
    $img_style   = 'style="display:block;width:100%;height:auto;"';

    $zoom_button = '<button type="button" class="kgsweb-display-zoom-btn" aria-hidden="true" 
        style="position:absolute;top:8px;right:8px;width:36px;height:36px;border-radius:50%;
               background:rgba(0,0,0,0.45);border:0;color:#fff;display:flex;
               align-items:center;justify-content:center;padding:0;cursor:pointer;">
        <i class="fa fa-search-plus" aria-hidden="true"></i>
    </button>';

    return
        '<div class="kgsweb-display kgsweb-display-' . esc_attr($type) . '" ' . $outer_style . '>' .
            '<div class="kgsweb-display-inner" ' . $inner_style . '>' .
                '<img src="' . $img_url . '" alt="' . $alt . '" loading="lazy" ' . $img_style . '>' .
                $zoom_button .
            '</div>' .
        '</div>';
}


    /**
     * Get display payload from cache or fresh
     * Supports both type-based and folder-based retrieval
     */
    public static function get_display_payload($type, $folder_id) {
        $cache_key = "kgsweb_cache_display_" . md5($type . $folder_id);
        $data = get_transient($cache_key);

        if ($data === false) {
            $data = self::build_latest_display_image($type, $folder_id);
            set_transient($cache_key, $data, HOUR_IN_SECONDS);
        }

        if (empty($data['image_url'])) {
            return new WP_Error('no_display', 'Display not available');
        }
        return $data;
    }

    /**
     * Refresh cache manually
     */
    public static function refresh_display_cache($type, $folder_id) {
        $data = self::build_latest_display_image($type, $folder_id);
        set_transient("kgsweb_cache_display_" . md5($type . $folder_id), $data, HOUR_IN_SECONDS);
        update_option("kgsweb_cache_last_refresh_display_" . $type, current_time("timestamp"));
    }

    /**
     * Build latest display image from Drive folder
     */
    private static function build_latest_display_image($type, $folder_id) {
		$empty = [
			'type'       => $type,
			'image_url'  => '',
			'width'      => 0,
			'height'     => 0,
			'updated_at' => current_time('timestamp'),
		];
		if (!$folder_id) return $empty;

		// List files in folder
		$files = KGSweb_Google_Helpers::list_files_in_folder($folder_id);
		error_log("📁 Files in folder $folder_id: " . json_encode($files));

		if (empty($files)) return $empty;

		// Filter PDFs and images
		$files = array_filter($files, function($f) {
			$ext  = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
			$mime = strtolower($f['mimeType'] ?? '');
			return in_array($ext, ['pdf','png','jpg','jpeg','gif','webp'])
				|| in_array($mime, ['application/pdf','image/png','image/jpeg','image/jpg','image/gif','image/webp']);
		});

		if (empty($files)) {
			error_log("⚠️ No supported PDFs/images in folder $folder_id");
			return $empty;
		}

		// Sort by modifiedTime descending
		usort($files, fn($a,$b) => strtotime($b['modifiedTime']) <=> strtotime($a['modifiedTime']));
		$latest = $files[0];

		error_log("🆕 Latest file selected: {$latest['name']} ({$latest['mimeType']})");

		// Fetch local cached version
		$local_path = self::fetch_file_for_display($latest['id'], $latest['name'], $latest['mimeType'], $type);
		if (!$local_path || !file_exists($local_path)) {
			error_log("❌ Failed to fetch local path for display");
			return $empty;
		}

		// Get image dimensions
		$meta_path = $local_path . '.meta.json';
		$width = $height = 0;
		if (file_exists($meta_path)) {
			$meta = json_decode(file_get_contents($meta_path), true);
			if (is_array($meta) && isset($meta['width'],$meta['height'])) {
				$width  = (int)$meta['width'];
				$height = (int)$meta['height'];
			}
		}
		if (!$width || !$height) {
			$size = @getimagesize($local_path);
			if ($size) {
				[$width,$height] = $size;
				file_put_contents($meta_path, json_encode(['width'=>$width,'height'=>$height]));
			}
		}

		$url = KGSweb_Google_Helpers::get_cached_file_url($local_path);

		error_log("✅ Display image ready: $url ({$width}x{$height})");

		return [
			'type'       => $type,
			'image_url'  => esc_url($url),
			'width'      => $width,
			'height'     => $height,
			'updated_at' => current_time('timestamp'),
		];
	}


    /**
     * Fetch file from Drive, convert if PDF, optimize images
     * Works for both type-based and folder-based caching
     */
	private static function fetch_file_for_display($file_id, $filename, $mime_type, $type) {
		$transient_key = 'kgsweb_display_' . sanitize_key($type);
		$cached_path   = get_transient($transient_key);
		
		if ($cached_path && file_exists($cached_path)) {
			return $cached_path; // fast return from transient
		}

		$upload_dir = wp_upload_dir();
		$cache_base = trailingslashit($upload_dir['basedir']) . 'kgsweb-cache';
		$cache_dir  = $cache_base . '/' . sanitize_key($type);
		if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);

		$basename = sanitize_file_name($filename);
		$ext      = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
		$local    = $cache_dir . '/' . pathinfo($basename, PATHINFO_FILENAME) . '.' . $ext;
		$png_path = $cache_dir . '/' . pathinfo($basename, PATHINFO_FILENAME) . '.png';

		// PDFs → convert to PNG
		if ($mime_type === 'application/pdf' || $ext === 'pdf') {
			$pdf = KGSweb_Google_Helpers::fetch_file_contents_raw($file_id);
			if (!$pdf) return false;
			file_put_contents($local, $pdf);
			$png = KGSweb_Google_Helpers::convert_pdf_to_png_cached($local, $filename);
			if ($png) set_transient($transient_key, $png, 12 * HOUR_IN_SECONDS);
			return $png;
		}

		// Images → just save locally once, then cache path
		if (strpos($mime_type, 'image/') === 0 || in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
			if (!file_exists($local)) {
				// fallback: fetch once if needed
				$img = KGSweb_Google_Helpers::fetch_file_contents_raw($file_id);
				if ($img) file_put_contents($local, $img);
			}
			if (file_exists($local)) {
				set_transient($transient_key, $local, 12 * HOUR_IN_SECONDS);
				return $local;
			}
			return false;
		}

		return false; // unsupported type
	}




}
KGSweb_Google_Display::init();
