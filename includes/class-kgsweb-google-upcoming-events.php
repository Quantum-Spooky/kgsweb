<?php
// includes/class-kgsweb-google-upcoming-events.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Upcoming_Events {
    public static function init() { /* no-op */ }

    public static function refresh_cache_cron() {
        $cal = KGSweb_Google_Integration::get_settings()['calendar_id'] ?? '';
        if ( $cal ) self::refresh_events_cache( $cal );
    }

    public static function get_events_payload( $calendar_id = '', $page = 1, $per = 10 ) {
        $calendar_id = $calendar_id ?: ( KGSweb_Google_Integration::get_settings()['calendar_id'] ?? '' );
        if ( ! $calendar_id ) return new WP_Error( 'no_calendar', __( 'Calendar not configured.', 'kgsweb' ), [ 'status'=>400 ] );
        $key = 'kgsweb_cache_events_' . md5( $calendar_id );
        $list = get_transient( $key );
        if ( false === $list ) {
            $list = self::fetch_and_normalize_events( $calendar_id ); // up to 100
            set_transient( $key, $list, HOUR_IN_SECONDS );
        }
        $total = count( $list );
        $offset = max(0, ($page-1)*$per );
        $slice = array_slice( $list, $offset, $per );
        return [
            'calendar_id' => $calendar_id,
            'events'      => $slice,
            'page'        => $page,
            'per_page'    => $per,
            'total'       => $total,
        ];
    }

    public static function refresh_events_cache( $calendar_id ) {
        $list = self::fetch_and_normalize_events( $calendar_id );
        set_transient( 'kgsweb_cache_events_' . md5( $calendar_id ), $list, HOUR_IN_SECONDS );
        update_option( 'kgsweb_cache_last_refresh_events_'.md5($calendar_id), time() );
    }

    private static function fetch_and_normalize_events( $calendar_id ) {
        // TODO: Use Calendar API to fetch upcoming events (max 100), normalize:
        // [ ['id','title','start','end','all_day'=>bool,'location'=>null,'updated'=>ts], ... ]
        return [];
    }
}