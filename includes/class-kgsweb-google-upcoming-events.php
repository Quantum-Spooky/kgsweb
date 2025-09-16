<?php
// includes/class-kgsweb-google-upcoming-events.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Google\Service\Calendar;
use WP_REST_Request;
use WP_REST_Response;

class KGSweb_Google_Upcoming_Events {

    /** 
     * Init (no-op for now, placeholder for hooks) 
     */
    public static function init() { /* no-op */ }

    /**
     * Cron refresh for events
     */
    public static function refresh_cache_cron() {
        $cal = KGSweb_Google_Integration::get_settings()['calendar_id'] ?? '';
        if ( $cal ) self::refresh_events_cache( $cal );
    }

    /**
     * Get cached events payload (paged)
     */
    public static function get_events_payload( $calendar_id = '', $page = 1, $per = 10 ) {
        $calendar_id = $calendar_id ?: ( KGSweb_Google_Integration::get_settings()['calendar_id'] ?? '' );
        if ( ! $calendar_id ) return new WP_Error( 'no_calendar', __( 'Calendar not configured.', 'kgsweb' ), [ 'status'=>400 ] );

        $key = 'kgsweb_cache_events_' . md5( $calendar_id );
        $list = get_transient( $key );

        if ( false === $list ) {
            $list = self::fetch_and_normalize_events( $calendar_id );
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

    /**
     * Force refresh of events cache
     */
    public static function refresh_events_cache( $calendar_id ) {
        $list = self::fetch_and_normalize_events( $calendar_id );
        set_transient( 'kgsweb_cache_events_' . md5( $calendar_id ), $list, HOUR_IN_SECONDS );
        update_option( 'kgsweb_cache_last_refresh_events_'.md5($calendar_id), time() );
    }

    /**
     * Fetch events from Google Calendar and normalize
     * Returns array of events with keys:
     * [ 'id', 'title', 'start', 'end', 'all_day', 'location', 'updated' ]
     */
    private static function fetch_and_normalize_events( $calendar_id ) {
        $events = [];

        // Get Google Calendar service
        $calendar_service = KGSweb_Google_Integration::get_calendar();
        if ( ! $calendar_service ) {
            error_log("KGSWEB: Calendar service not initialized.");
            return $events; // fallback to empty array
        }

        try {
            // Fetch upcoming events (max 100)
            $optParams = [
                'maxResults'   => 100,
                'orderBy'      => 'startTime',
                'singleEvents' => true,
                'timeMin'      => gmdate('c'), // only future events
            ];

            $google_events = $calendar_service->events->listEvents($calendar_id, $optParams);

            foreach ($google_events->getItems() as $e) {
                $start = $e->start->dateTime ?: $e->start->date; // all-day events use 'date'
                $end   = $e->end->dateTime ?: $e->end->date;

                $events[] = [
                    'id'       => $e->getId(),
                    'title'    => $e->getSummary() ?: '',
                    'start'    => $start,
                    'end'      => $end,
                    'all_day'  => empty($e->start->dateTime),
                    'location' => $e->getLocation() ?: null,
                    'updated'  => strtotime($e->getUpdated()),
                ];
            }

        } catch (Exception $ex) {
            error_log("KGSWEB: Calendar fetch error for calendar $calendar_id - " . $ex->getMessage());
        }

        // Sort events by start date
        usort($events, fn($a, $b) => strtotime($a['start']) - strtotime($b['start']));

        return $events;
    }

    /**
     * REST endpoint for public GET /events
     */
		public function rest_calendar_events( WP_REST_Request $request ) {
			// Read input parameters
			$calendarId = sanitize_text_field( $request->get_param('calendar_id') )
						  ?: get_option( 'kgsweb_default_calendar_id', '' );
			$page       = max(1, intval( $request->get_param('page') ));
			$perPage    = max(1, intval( $request->get_param('per_page') ));

			if ( empty( $calendarId ) ) {
				return new WP_REST_Response( [
					'message' => 'No calendar ID configured.',
				], 404 );
			}

			// Try to get cached events (the normalized array)
			$cache_key = 'kgsweb_cache_events_' . md5( $calendarId );
			$cached = get_transient( $cache_key );

			if ( false === $cached ) {
				// Cache miss -> fetch fresh
				$events_all = self::fetch_and_normalize_events( $calendarId );
				// Always store an array
				set_transient( $cache_key, $events_all, HOUR_IN_SECONDS );
			} else {
				// If cached is not false, ensure it's an array
				if ( is_string( $cached ) ) {
					$maybe = json_decode( $cached, true );
					if ( is_array( $maybe ) ) {
						$events_all = $maybe;
					} else {
						// If for some reason the cached string is not valid JSON,
						// fallback: assume it was stored as an array already.
						$events_all = maybe_unserialize( $cached );
						if ( ! is_array( $events_all ) ) {
							// Extreme fallback: re‐fetch if everything is weird
							$events_all = self::fetch_and_normalize_events( $calendarId );
							set_transient( $cache_key, $events_all, HOUR_IN_SECONDS );
						}
					}
				} elseif ( is_array( $cached ) ) {
					$events_all = $cached;
				} else {
					// Not string nor array: fallback refetch
					$events_all = self::fetch_and_normalize_events( $calendarId );
					set_transient( $cache_key, $events_all, HOUR_IN_SECONDS );
				}
			}

			// Now we have an array $events_all
			$total   = count( $events_all );
			$offset  = ( $page - 1 ) * $perPage;
			$slice   = array_slice( $events_all, $offset, $perPage );
			$total_pages = $perPage > 0 ? ceil( $total / $perPage ) : 1;

			// Build response payload
			$response_data = [
				'calendar_id' => $calendarId,
				'events'      => $slice,
				'page'        => $page,
				'per_page'    => $perPage,
				'total'       => $total,
				'total_pages' => $total_pages,
			];

			return rest_ensure_response( $response_data );
		}
}
