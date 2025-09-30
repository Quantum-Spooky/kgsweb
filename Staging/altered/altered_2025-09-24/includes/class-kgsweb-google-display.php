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
        'breakfast-menu'    => 'kgsweb_breakfast_menu_folder',
        'lunch-menu'        => 'kgsweb_lunch_menu_folder',
        'monthly-calendar'  => 'kgsweb_monthly_calendar_folder',
        'academic-calendar' => 'kgsweb_academic_calendar_folder',
        'athletic-calendar' => 'kgsweb_athletic_calendar_folder',
        'feature-image'     => 'kgsweb_feature_image_folder',
        'pto-feature-image' => 'kgsweb_pto_feature_image_folder',
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
        if (!empty($atts['folder'])) {
            $folder_id = sanitize_text_field($atts['folder']);
        } elseif (!empty($atts['type']) && isset(self::$types[$atts['type']])) {
            $folder_id = get_option(self::$types[$atts['type']]);
        }
        if (empty($folder_id)) return ''; // nothing to display

        // Fetch display payload (cached)
        $payload = self::get_display_payload($atts['type'], $folder_id);
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
        $alt     = esc_attr($atts['type'] ?: 'display');

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
            '<div class="kgsweb-display kgsweb-display-' . esc_attr($atts['type']) . '" ' . $outer_style . '>' .
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
        if (empty($files)) return $empty;

        // Filter only PDFs and images
        $files = array_filter($files, function($f) {
            $ext  = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
            $mime = strtolower($f['mimeType'] ?? '');
            return in_array($ext, ['pdf','png','jpg','jpeg','gif','webp'])
                || in_array($mime, ['application/pdf','image/png','image/jpeg','image/jpg','image/gif','image/webp']);
        });
        if (empty($files)) return $empty;

        // Sort descending by modified time
        usort($files, fn($a,$b) => strtotime($b['modifiedTime']) <=> strtotime($a['modifiedTime']));
        $latest = $files[0];

        // Fetch local cached version
        $local_path = self::fetch_file_for_display($latest['id'], $latest['name'], $latest['mimeType'], $type);
        if (!$local_path || !file_exists($local_path)) return $empty;

        // Get image dimensions (cached metadata)
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
        $upload_dir = wp_upload_dir();
        $cache_base = trailingslashit($upload_dir['basedir']) . 'kgsweb-cache';
        $cache_dir  = $cache_base . '/' . sanitize_key($type);
        if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);

        $basename = sanitize_file_name($filename);
        $ext      = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $local    = $cache_dir . '/' . pathinfo($basename, PATHINFO_FILENAME) . '.' . $ext;
        $png_path = $cache_dir . '/' . pathinfo($basename, PATHINFO_FILENAME) . '.png';

        // Return cached PNG if available
        if (file_exists($png_path)) return $png_path;

        // PDF → PNG conversion
        if ($mime_type === 'application/pdf') {
            $pdf = KGSweb_Google_Helpers::fetch_file_contents_raw($file_id);
            if (!$pdf) return false;
            file_put_contents($local, $pdf);
            return KGSweb_Google_Helpers::convert_pdf_to_png_cached($local, $filename);
        }

        // Images (jpeg/png) → optimized cached image
        if (in_array($mime_type, ['image/png','image/jpeg','image/jpg'])) {
            $img = KGSweb_Google_Helpers::get_file_contents($file_id);
            if (!$img) return false;
            file_put_contents($local, $img);
            return KGSweb_Google_Helpers::optimize_image_cached($file_id, $filename, 1000);
        }

        return false; // unsupported type
    }
}

// Bootstrap
KGSweb_Google_Display::init();
