<?php
// includes/class-kgsweb-google-sheets.php
if (!defined('ABSPATH')) exit;

class KGSweb_Google_Sheets {

    public static function init() { /* no-op */ }

    /*******************************
     * Shortcode renderer
     *******************************/
    public static function shortcode_render($atts = []) {
        $settings = KGSweb_Google_Integration::get_settings();
        $atts = shortcode_atts([
            'sheet_id' => $settings['sheets_file_id'] ?? '',
            'range'    => 'A1:Z100'
        ], $atts, 'kgsweb_sheets');

        $payload = self::get_sheet_payload($atts['sheet_id'], $atts['range']);
        if (is_wp_error($payload)) {
            return '<div class="kgsweb-sheets-empty">Sheet not available.</div>';
        }

        return '<div class="kgsweb-sheets">' . esc_html(json_encode($payload)) . '</div>';
    }

    /*******************************
     * Cache helpers
     *******************************/
	public static function get_sheet_data(string $sheet_id): array {
		if (empty($sheet_id)) {
			error_log("KGSWEB: Missing sheet ID in get_sheet_data()");
			return [];
		}

		$cache_key = 'kgsweb_sheet_' . md5($sheet_id);

		// Try cached data first
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			return $cached;
		}

		// Fetch from API
		try {
			$data = self::fetch_sheet_data_from_api($sheet_id); // your API call
		} catch (Exception $e) {
			error_log("KGSWEB: Sheets API fetch failed for {$sheet_id}: " . $e->getMessage());
			return [];
		}

		// Only cache non-empty results
		if (!empty($data)) {
			set_transient($cache_key, $data, HOUR_IN_SECONDS);
			update_option('kgsweb_last_refresh_sheet_' . $sheet_id, current_time('timestamp'), false);
		} else {
			error_log("KGSWEB: Sheets API returned empty data for {$sheet_id}. Not caching.");
		}

		return $data;
	}

    public static function refresh_cache($sheet_id, $range) {
        $data = self::fetch_sheet_from_google($sheet_id, $range);
        $key = 'kgsweb_cache_sheets_' . $sheet_id . '_' . md5($range);
        KGSweb_Google_Helpers::set_transient($key, $data, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_sheets_' . $sheet_id, current_time('timestamp'));
    }

    /*******************************
     * Google fetch stub
     *******************************/
    private static function fetch_sheet_from_google($sheet_id, $range) {
        // TODO: Implement Google Sheets API fetch
        return [
            'sheet_id'   => $sheet_id,
            'range'      => $range,
            'values'     => [],
            'updated_at' => current_time('timestamp')
        ];
    }
}
