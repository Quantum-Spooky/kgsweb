<?php
// includes/class-kgsweb-google-ticker.php
if (!defined('ABSPATH')) exit;

class KGSweb_Google_Ticker {

    public static function init() { /* no-op */ }

    /*******************************
     * Get ticker content (with cache)
     *******************************/
    public static function get_ticker_content(string $file_id): array {
        $file_id = trim($file_id);
        if (!$file_id) {
            error_log("KGSWEB: Ticker file ID is empty.");
            return [];
        }

        $cache_key = 'kgsweb_ticker_' . md5($file_id);

        // Try cached data first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return is_array($cached) ? $cached : [];
        }

        // Fetch fresh content via helper
        try {
            $content = KGSweb_Google_Helpers::get_file_contents($file_id);
        } catch (Exception $e) {
            error_log("KGSWEB: Failed to fetch ticker content for {$file_id}: " . $e->getMessage());
            return [];
        }

        if (empty($content)) {
            error_log("KGSWEB: Ticker content empty for {$file_id}, skipping cache.");
            return [];
        }

        $parsed = json_decode($content, true);
        if (!is_array($parsed) || empty($parsed)) {
            error_log("KGSWEB: Invalid or empty ticker content JSON for {$file_id}");
            return [];
        }

        // Cache valid data
        set_transient($cache_key, $parsed, HOUR_IN_SECONDS);

        // Update last refresh option
        update_option('kgsweb_ticker_last_refresh_' . $file_id, current_time('timestamp'));

        return $parsed;
    }
	
	
	 /*******************************
     * Refresh ticker cache cron
     *******************************/
	public static function refresh_cache_cron(): bool {
		$file_id = KGSweb_Google_Integration::get_settings()['ticker_file_id'] ?? '';
		if (!$file_id) return false;
		return self::refresh_cache($file_id);
	}

    /*******************************
     * Force refresh ticker cache
     *******************************/
    public static function refresh_cache(string $file_id): bool {
        $file_id = trim($file_id);
        if (!$file_id) return false;

        $data = self::get_ticker_content($file_id);
        return !empty($data);
    }

    /*******************************
     * Clear ticker cache
     *******************************/
    public static function clear_cache(string $file_id): void {
        $file_id = trim($file_id);
        if (!$file_id) return;

        $cache_key = 'kgsweb_ticker_' . md5($file_id);
        delete_transient($cache_key);
    }

    /*******************************
     * Output ticker HTML safely
     * Hides ticker when no items
     *******************************/
    public static function render_ticker(string $file_id): string {
        $items = self::get_ticker_content($file_id);
        if (empty($items)) return ''; // <-- hide ticker entirely

        $html = '<ul class="kgsweb-ticker">';
        foreach ($items as $item) {
            $text = esc_html($item['text'] ?? '');
            $url  = esc_url($item['url'] ?? '');
            $html .= sprintf('<li><a href="%s">%s</a></li>', $url, $text);
        }
        $html .= '</ul>';
        return $html;
    }
}
