<?php
// includes/class-kgsweb-google-menus.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Menus {

    public static function init() { /* no-op */ }

    /*******************************
     * Shortcode renderer
     *******************************/
    public static function shortcode_render($atts = []) {
        $atts = shortcode_atts([
            'type' => 'breakfast', // 'breakfast' or 'lunch'
        ], $atts, 'kgsweb_menu');

        $payload = self::get_menu_payload($atts['type']);
        if (is_wp_error($payload)) return '<div class="kgsweb-menu-empty">Menu not available.</div>';

        $img_url = esc_url($payload['image_url']);
        $width   = esc_attr($payload['width']);
        $height  = esc_attr($payload['height']);

        return sprintf(
            '<div class="kgsweb-menu"><img src="%s" width="%s" height="%s"><i class="fa fa-search-plus kgsweb-menu-zoom"></i></div>',
            $img_url, $width, $height
        );
    }

    /*******************************
     * Cache helpers
     *******************************/
    public static function get_menu_payload($type) {
        $key = 'kgsweb_cache_menu_' . $type;
        $data = get_transient($key);
        if ($data === false) {
            $data = self::build_latest_menu_image($type); // fetch from Google Drive if needed
            set_transient($key, $data, HOUR_IN_SECONDS);
        }

        if (empty($data['image_url'])) {
            return new WP_Error('no_menu', __('Menu not available.', 'kgsweb'), ['status' => 404]);
        }

        return $data;
    }

    public static function refresh_menu_cache($type) {
        $data = self::build_latest_menu_image($type);
        set_transient('kgsweb_cache_menu_' . $type, $data, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_menu_' . $type, current_time('timestamp'));
    }

    /*******************************
     * Google fetch stub
     *******************************/
    private static function build_latest_menu_image($type) {
		$settings = KGSweb_Google_Integration::get_settings();
		$folder_key = "menu_{$type}_folder_id";
		$folder_id  = $settings[$folder_key] ?? '';
		if (!$folder_id) return [
			'type'=>$type,'image_url'=>'','width'=>0,'height'=>0,'updated_at'=>current_time('timestamp')
		];

		$files = KGSweb_Google_Integration::list_files_in_folder($folder_id);
		if (empty($files)) return ['type'=>$type,'image_url'=>'','width'=>0,'height'=>0,'updated_at'=>current_time('timestamp')];

		$latest = end($files);
		$file_id = $latest['id'];

		$content = KGSweb_Google_Integration::get_file_contents($file_id);
		$image_url = ''; // Convert PDF or Docs to PNG here if needed

		return [
			'type'=>$type,
			'image_url'=>$image_url,
			'width'=>0,
			'height'=>0,
			'updated_at'=>current_time('timestamp')
		];
	}
}