<?php
// includes/class-kgsweb-google-sheets.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Sheets {

    public static function init() { /* no-op */ }

    /*******************************
     * Shortcode renderer
     *******************************/
    public static function shortcode_render($atts = []) {
        $atts = shortcode_atts([
            'sheet_id' => KGSweb_Google_Integration::get_settings()['sheets_file_id'] ?? '',
            'range' => 'A1:Z100'
        ], $atts, 'kgsweb_sheets');

        $payload = self::get_sheet_payload($atts['sheet_id'], $atts['range']);
        if (is_wp_error($payload)) return '<div class="kgsweb-sheets-empty">Sheet not available.</div>';

        return '<div class="kgsweb-sheets">' . esc_html(json_encode($payload)) . '</div>';
    }

    /*******************************
     * Cache helpers
     *******************************/
    public static function get_sheet_payload($sheet_id, $range) {
        if (!$sheet_id) return new WP_Error('no_file', __('Sheet file not set.', 'kgsweb'), ['status'=>404]);

        $key = 'kgsweb_cache_sheets_' . $sheet_id . '_' . md5($range);
        $data = get_transient($key);

        if ($data === false) {
            $data = self::fetch_sheet_from_google($sheet_id, $range);
            set_transient($key, $data, HOUR_IN_SECONDS);
        }

        return $data;
    }

    public static function refresh_cache($sheet_id, $range) {
        $data = self::fetch_sheet_from_google($sheet_id, $range);
        set_transient('kgsweb_cache_sheets_' . $sheet_id . '_' . md5($range), $data, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_sheets_' . $sheet_id, current_time('timestamp'));
    }

    /*******************************
     * Google fetch stub
     *******************************/
    private static function fetch_sheet_from_google($sheet_id, $range) {
        // TODO: Use Google Sheets API to fetch sheet content
        return [
            'sheet_id' => $sheet_id,
            'range' => $range,
            'values' => [],
            'updated_at' => current_time('timestamp')
        ];
    }
}
