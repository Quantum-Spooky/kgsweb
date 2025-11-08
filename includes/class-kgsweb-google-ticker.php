<?php
// includes/class-kgsweb-google-ticker.php
if (!defined('ABSPATH')) exit;

use Google\Service\Drive;

class KGSweb_Google_Ticker {

    /*******************************
     * Drive / Docs Fetching
     *******************************/
    public static function get_latest_file_from_folder($folderId) {
        try {
            $files = KGSweb_Google_Drive_Docs::list_drive_children($folderId, 'date-desc');

            if (empty($files)) {
                error_log("KGSWEB: [Ticker] No files returned from folder {$folderId}");
                return null;
            }

            $latestFile = $files[0];
            if (!isset($latestFile['id'])) {
                error_log("KGSWEB: [Ticker] Latest file missing ID in folder {$folderId}");
                return null;
            }

            return (object) ($latestFile ?: []);

        } catch (Exception $e) {
            error_log("KGSWEB: [Ticker] Error fetching latest file from folder {$folderId} - " . $e->getMessage());
            return null;
        }
    }

    public static function extract_ticker_text($fileId) {
        $docs = KGSweb_Google_Integration::get_docs_service();
        if (!$docs) {
            error_log("KGSWEB: [Ticker] Docs service unavailable, cannot fetch file {$fileId}");
            return '';
        }

        try {
            $doc = $docs->documents->get($fileId);
            $content = '';
            foreach ($doc->body->content as $element) {
                if (isset($element->paragraph->elements)) {
                    foreach ($element->paragraph->elements as $pe) {
                        if (isset($pe->textRun->content)) {
                            $content .= $pe->textRun->content;
                        }
                    }
                }
            }
            return trim(preg_replace('/\s+/', ' ', $content));
        } catch (\Exception $e) {
            error_log("KGSWEB: [Ticker] Error extracting text from file {$fileId} - " . $e->getMessage());
            return '';
        }
    }

    /*******************************
     * Caching Helpers
     *******************************/
    private static function make_cache_key(string $folder_id, string $file_id): string {
        return 'kgsweb_cache_ticker_' . md5("{$folder_id}:{$file_id}");
    }

    private static function set_ticker_cache(string $folder_id, string $file_id, string $text, ?string $modifiedTime = null): void {
        $cache_key = self::make_cache_key($folder_id, $file_id);
        set_transient($cache_key, $text, 60 * 60); // 1 hour

        $index = get_option('kgsweb_ticker_cache_index', []);
        if (!is_array($index)) $index = [];
        if (!isset($index[$folder_id])) $index[$folder_id] = [];

        $index[$folder_id][$file_id] = [
            'cache_key' => $cache_key,
            'modifiedTime' => $modifiedTime ?? current_time('mysql')
        ];
        update_option('kgsweb_ticker_cache_index', $index);

        error_log("KGSWEB: [Ticker] Cache SET for file {$file_id} (length=" . strlen($text) . ")");
    }

    private static function clear_folder_cache(string $folder_id): void {
        $index = get_option('kgsweb_ticker_cache_index', []);
        if (!is_array($index)) $index = [];
        if (empty($index[$folder_id])) $index[$folder_id] = [];

        foreach ($index[$folder_id] as $entry) {
            if (isset($entry['cache_key'])) {
                delete_transient($entry['cache_key']);
            }
        }

        $index[$folder_id] = [];
        update_option('kgsweb_ticker_cache_index', $index);

        error_log("KGSWEB: [Ticker] Cleared cache for folder {$folder_id}");
    }

    public static function get_cached_ticker(string $folder_id = '', string $file_id = '', bool $force_refresh = false): string {
        $settings = KGSweb_Google_Integration::get_settings();
        $folder_id = $folder_id ?: ($settings['ticker_folder_id'] ?? '');
        if (!$folder_id) {
            error_log("KGSWEB: [Ticker] No folder ID available.");
            return '';
        }

        if (!$file_id) {
            $latest_file = self::get_latest_file_from_folder($folder_id);
            $file_id = $latest_file->id ?? '';
            $modifiedTime = $latest_file->modifiedTime ?? null;
            if ($file_id) {
                error_log("KGSWEB: [Ticker] Candidate file {$latest_file->name} ({$file_id}) modified {$modifiedTime}");
            }
        } else {
            $modifiedTime = null;
        }

        $cache_key = self::make_cache_key($folder_id, $file_id);
        if ($force_refresh) {
            delete_transient($cache_key);
        }

        $text = get_transient($cache_key);
        if ($text === false) {
            $text = self::extract_ticker_text($file_id);
            if ($text) {
                self::set_ticker_cache($folder_id, $file_id, $text, $modifiedTime);
                update_option('kgsweb_ticker_last_file_id', $file_id);
                $preview = substr(str_replace("\n", " ⏎ ", $text), 0, 150);
                error_log("KGSWEB: [Ticker] Cache populated successfully (preview={$preview})");
            } else {
                error_log("KGSWEB: [Ticker] Fresh fetch returned empty string for {$file_id}");
            }
        } else {
            error_log("KGSWEB: [Ticker] Cache hit for file {$file_id} (length=" . strlen($text) . ")");
        }

        return $text ?: '';
    }

	/*******************************
	 * Refresh / Cron
	 *******************************/
	public static function refresh_ticker_cache(): bool {
		$folderId = get_option('kgsweb_ticker_folder_id');
		if (!$folderId) {
			error_log("KGSWEB: [Ticker] No folder ID set, cannot refresh ticker cache.");
			return false;
		}

		$latestFile = self::get_latest_file_from_folder($folderId);
		if (!$latestFile) {
			error_log("KGSWEB: [Ticker] No file found in folder {$folderId}");
			return false;
		}

		$file_id = $latestFile->id;

		// Force fetch the latest content instead of returning cached transient
		$text = self::extract_ticker_text($file_id);
		if (!$text) {
			error_log("KGSWEB: [Ticker] No ticker content found in {$file_id}, ticker hidden.");
			return false;
		}

		self::clear_folder_cache($folderId);
		self::set_ticker_cache($folderId, $file_id, $text, $latestFile->modifiedTime ?? null);
		update_option('kgsweb_ticker_last_file_id', $file_id);

		error_log("KGSWEB: [Ticker] Ticker updated from file {$file_id}");
		return true;
	}


    /*******************************
     * REST API
     *******************************/
    public static function rest_get_ticker(WP_REST_Request $request): WP_REST_Response {
        $folder = $request->get_param('folder') ?: '';
        $file   = $request->get_param('file') ?: '';
        $text   = self::get_cached_ticker($folder, $file);

        return new WP_REST_Response([
            'success' => (bool) $text,
            'ticker'  => $text,
        ]);
    }

    /*******************************
     * Render / Shortcode
     *******************************/
    public static function render_ticker($atts = []): string {
        $settings = KGSweb_Google_Integration::get_settings();
        $default_folder_id = $settings['ticker_folder_id'] ?? '';

        $atts = shortcode_atts([
            'folder_id' => $default_folder_id,
            'file_id'   => '',
            'speed'     => '0.5',
        ], $atts, 'kgsweb_ticker');

        $folder_id = $atts['folder_id'];
        $file_id   = $atts['file_id'];

        if (!$folder_id && !$file_id) return '';

        if (!$file_id && $folder_id) {
            $latest_file = self::get_latest_file_from_folder($folder_id);
            if (!$latest_file) return '';
            $file_id = $latest_file->id;
        }

        $text = self::get_cached_ticker($folder_id, $file_id);
        if (!$text || trim($text) === 'No alerts at this time.') return '';

        wp_enqueue_script('kgsweb-ticker');

        $scroll_text = preg_replace("/(\r\n|\n|\r){2,}/", "\n", $text);
        $scroll_text = str_replace(["\r\n", "\n", "\r"], ' | ', $scroll_text);
        $scroll_text = trim($scroll_text) . ' | KGS |';

        $lines = array_map('rtrim', explode("\n", str_replace(["\r\n", "\r"], "\n", $text)));
        $full_lines = [];
        $prev_empty = false;
        foreach ($lines as $line) {
            $is_empty = trim($line) === '';
            if (!($is_empty && $prev_empty)) $full_lines[] = $line;
            $prev_empty = $is_empty;
        }
        $full_text = nl2br(esc_html(implode("\n", $full_lines)));

        return sprintf(
            '<div class="kgsweb-ticker" data-folder="%s" data-file="%s" data-speed="%s">
                <button class="kgsweb-ticker-expand" aria-label="Expand/Collapse">
                    <i class="fas fa-caret-down"></i>
                </button>
                <div class="kgsweb-ticker-wrapper">
                    <div class="kgsweb-ticker-container">
                        <div class="kgsweb-ticker-track"><span class="kgsweb-ticker-inner">%s</span></div>
                        <div class="kgsweb-ticker-full">%s</div>
                    </div>
                </div>
            </div>',
            esc_attr($folder_id),
            esc_attr($file_id),
            esc_attr(floatval($atts['speed'])),
            esc_html($scroll_text),
            $full_text
        );
    }

    /*******************************
     * Register Hooks
     *******************************/
    public static function register(): void {
        add_action('kgsweb_refresh_ticker_cache', [__CLASS__, 'refresh_ticker_cache']);
        if (!wp_next_scheduled('kgsweb_refresh_ticker_cache')) {
            wp_schedule_event(time(), 'hourly', 'kgsweb_refresh_ticker_cache');
        }

        add_shortcode('kgsweb_ticker', [__CLASS__, 'render_ticker']);

        add_action('rest_api_init', function () {
            register_rest_route('kgsweb/v1', '/ticker', [
                'methods' => 'GET',
                'callback' => [self::class, 'rest_get_ticker'],
                'permission_callback' => '__return_true',
            ]);
        });
    }
}
