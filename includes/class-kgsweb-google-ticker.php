<?php
// includes/class-kgsweb-google-ticker.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Ticker {
    public static function init() { /* no-op */ }

    public static function refresh_cache_cron() {
        $default_id = KGSweb_Google_Integration::get_settings()['ticker_file_id'] ?? '';
        if ( $default_id ) {
            self::refresh_ticker_cache( $default_id );
        }
    }

    public static function get_ticker_payload( $id = '' ) {
        $id = $id ?: ( KGSweb_Google_Integration::get_settings()['ticker_file_id'] ?? '' );
        if ( ! $id ) return new WP_Error( 'no_id', __( 'Ticker source not configured.', 'kgsweb' ), [ 'status'=>400 ] );
        $key = 'kgsweb_cache_ticker_' . $id;
        $cached = get_transient( $key );
        if ( false === $cached ) {
            $cached = self::fetch_and_normalize_text( $id );
            set_transient( $key, $cached, HOUR_IN_SECONDS );
        }
        return [ 'id'=>$id, 'text'=> $cached['text'], 'updated_at'=>$cached['updated_at'] ];
    }

    public static function refresh_ticker_cache( $id ) {
        $data = self::fetch_and_normalize_text( $id );
        set_transient( 'kgsweb_cache_ticker_' . $id, $data, HOUR_IN_SECONDS );
        update_option( 'kgsweb_cache_last_refresh_ticker_'.$id, time() );
    }

    private static function fetch_and_normalize_text( $id ) {
        // TODO: if .txt: download file; if Google Doc: export plain text via Drive/Docs API
        $text = '';
        $text = trim( wp_strip_all_tags( $text ) );
        return [ 'text' => $text, 'updated_at' => time() ];
    }
}