<?php
// includes/class-kgsweb-google-menus.php
if (!defined("ABSPATH")) {
    exit();
}

/**
 * KGSweb_Google_Menus
 *
 * Handles fetching, caching, and rendering of breakfast/lunch menus from Google Drive.
 * This class is now purely feature-specific; all generic logic is in KGSweb_Google_Helpers.
 */
class KGSweb_Google_Menus
{
    /**
     * Initialize class (placeholder for future hooks)
     */
    public static function init()
    {
        /* no-op */
    }

    /*******************************
     * Shortcode renderer
     *******************************/
    public static function shortcode_render(array $atts = []): string
    {
        $atts = shortcode_atts([
            "type" => "lunch",
            "width" => "",
        ], $atts, "kgsweb_menu");

        $type = strtolower(sanitize_text_field($atts["type"]));
        if (!in_array($type, ["breakfast", "lunch"], true)) {
            $type = "lunch";
        }

        $payload = self::get_menu_payload($type);
        if (is_wp_error($payload) || empty($payload["image_url"])) {
            return sprintf(
                '<div class="kgsweb-menu-empty">%s menu not available.</div>',
                ucfirst($type)
            );
        }

        $img_url = esc_url($payload["image_url"]);
        $alt     = sprintf("%s Menu", ucfirst($type));

        $inner_width_style = "";
        $raw_w = trim($atts["width"] ?? "");
        if ($raw_w !== "") {
            $w = esc_attr($raw_w);
            if (is_numeric($w)) $w .= "px";
            $inner_width_style = "width:{$w};";
        }

        $outer_style = 'style="text-align:center;"';
        $inner_style = 'style="display:inline-block;position:relative;max-width:100%;' . $inner_width_style . '"';
        $img_style   = 'style="display:block;width:100%;height:auto;"';

        $zoom_button = '<button type="button" class="kgsweb-menu-zoom-btn" aria-hidden="true" 
            style="position:absolute;top:8px;right:8px;width:36px;height:36px;border-radius:50%;
                   background:rgba(0,0,0,0.45);border:0;color:#fff;display:flex;
                   align-items:center;justify-content:center;padding:0;cursor:pointer;">
            <i class="fa fa-search-plus" aria-hidden="true"></i>
        </button>';

        return
            '<div class="kgsweb-menu kgsweb-menu-' . esc_attr($type) . '" ' . $outer_style . '>' .
                '<div class="kgsweb-menu-inner" ' . $inner_style . '>' .
                    '<img src="' . $img_url . '" alt="' . esc_attr($alt) . '" loading="lazy" ' . $img_style . '>' .
                    $zoom_button .
                '</div>' .
            '</div>';
    }

    /*******************************
     * Menus (Breakfast / Lunch)
     *******************************/
    public static function get_menu_payload(string $type)
    {
        $key = 'kgsweb_cache_menu_' . $type;
        $data = get_transient($key);

        if ($data === false) {
            $data = self::build_latest_menu_image($type);
            set_transient($key, $data, HOUR_IN_SECONDS);
        }

        if (empty($data['image_url'])) {
            return new WP_Error('no_menu', __('Menu not available.', 'kgsweb'), ['status' => 404]);
        }

        return $data;
    }

    public static function refresh_menu_cache(string $type): void
    {
        $data = self::build_latest_menu_image($type);
        set_transient('kgsweb_cache_menu_' . $type, $data, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_menu_' . $type, current_time('timestamp'));
    }

    private static function build_latest_menu_image(string $type): array
    {
        // Menu-specific folder IDs
        $settings = KGSweb_Google_Integration::get_settings();
        $folder_map = [
            'breakfast' => $settings['menu_breakfast_folder_id'] ?? '',
            'lunch'     => $settings['menu_lunch_folder_id'] ?? '',
        ];
        $folder_id = $folder_map[$type] ?? '';
        if (!$folder_id) {
            return [
                'type' => $type,
                'image_url' => '',
                'width' => 0,
                'height' => 0,
                'updated_at' => current_time('timestamp')
            ];
        }

        // --- Generic logic moved to Helpers ---
        $files = KGSweb_Google_Helpers::list_files_in_folder($folder_id);
        if (empty($files)) {
            return [
                'type' => $type,
                'image_url' => '',
                'width' => 0,
                'height' => 0,
                'updated_at' => current_time('timestamp')
            ];
        }

        // Filter for PDFs/images
        $files = KGSweb_Google_Helpers::filter_menu_files($files);

        // Sort newest first
        $latest = KGSweb_Google_Helpers::sort_files_newest_first($files)[0];

        $local_path = KGSweb_Google_Helpers::fetch_and_cache_file(
            $latest['id'],
            $latest['name'],
            $latest['mimeType'],
            $type
        );

        if (!$local_path || !file_exists($local_path)) {
            return [
                'type' => $type,
                'image_url' => '',
                'width' => 0,
                'height' => 0,
                'updated_at' => current_time('timestamp')
            ];
        }

        // Get width/height metadata
        [$width, $height] = KGSweb_Google_Helpers::get_image_dimensions($local_path);

        $url = KGSweb_Google_Helpers::get_cached_file_url($local_path);

        return [
            'type' => $type,
            'image_url' => esc_url($url),
            'width' => $width,
            'height' => $height,
            'updated_at' => current_time('timestamp')
        ];
    }
}
