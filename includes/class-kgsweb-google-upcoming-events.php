<?php
// includes/class-kgsweb-google-upcoming-events.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Google\Service\Calendar;

class KGSweb_Google_Upcoming_Events {

    /** 
     * Init (no-op for now, placeholder for hooks) 
     */
    public static function init() { /* no-op */ }

    /**
     * Refresh events cache.
     * If $calendar_id is provided, refresh that specific calendar.
     * If no $calendar_id is provided, uses the default calendar from settings.
     */
public static function refresh_events_cache(?string $calendar_id = null): bool {
    $settings = KGSweb_Google_Integration::get_settings();
    $calendar_id = $calendar_id ?? ($settings['calendar_id'] ?? '');
    if (empty($calendar_id)) {
        error_log("KGSWEB: No calendar ID available for refresh_events_cache.");
        return false;
    }

    try {
        $cache_key = 'kgsweb_cache_events_' . md5($calendar_id);
        $events = self::fetch_and_normalize_events($calendar_id);
        if (empty($events)) {
            error_log("KGSWEB: No events fetched for calendar {$calendar_id}");
            return false;
        }

        set_transient($cache_key, $events, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_events_' . md5($calendar_id), current_time('timestamp'));
        error_log("KGSWEB: Events cache refreshed for calendar ID {$calendar_id}");
        return true;

    } catch (Exception $e) {
        error_log("KGSWEB: Failed to refresh events cache for {$calendar_id} - " . $e->getMessage());
        return false;
    }
}


    /**
     * Get cached events payload (paged)
     */
    public static function get_events_payload( $calendar_id = '', $page = 1, $per = 10 ) {
        $calendar_id = $calendar_id ?: ( KGSweb_Google_Integration::get_settings()['calendar_id'] ?? '' );
        if ( ! $calendar_id ) {
            return new WP_Error('no_calendar', __( 'Calendar not configured.', 'kgsweb' ), ['status'=>400]);
        }

        $cache_key = 'kgsweb_cache_events_' . md5($calendar_id);
        $events = get_transient($cache_key);

        if ($events === false) {
            // Cache miss → fetch fresh
            $events = self::fetch_and_normalize_events($calendar_id);
            set_transient($cache_key, $events, HOUR_IN_SECONDS);
        }

        $total = count($events);
        $offset = max(0, ($page - 1) * $per);
        $slice = array_slice($events, $offset, $per);

        return [
            'calendar_id' => $calendar_id,
            'events'      => $slice,
            'page'        => $page,
            'per_page'    => $per,
            'total'       => $total,
        ];
    }


    /**
     * Fetch events from Google Calendar and normalize
     * Returns array of events with keys:
     * [ 'id', 'title', 'start', 'end', 'all_day', 'location', 'updated' ]
     */
    private static function fetch_and_normalize_events($calendar_id) {
        $events = [];

									  
        $calendar_service = KGSweb_Google_Integration::get_calendar();
        if (!$calendar_service) {
            error_log("KGSWEB: Calendar service not initialized.");
            return $events;
        }

        try {
            // Fetch upcoming events 										  
            $optParams = [
                'maxResults'   => 100, // (max 100)
                'orderBy'      => 'startTime',
                'singleEvents' => true,
                'timeMin'      => gmdate('c'), // only future events
            ];

            $google_events = $calendar_service->events->listEvents($calendar_id, $optParams);

            foreach ($google_events->getItems() as $e) {
                $start = $e->start->dateTime ?: $e->start->date;
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
            error_log("KGSWEB: Calendar fetch error for calendar {$calendar_id} - " . $ex->getMessage());
        }

        // Sort events by start date									
        usort($events, fn($a, $b) => strtotime($a['start']) - strtotime($b['start']));

        return $events;
    }

    /**
     * REST endpoint for public GET /events
     */
    public function rest_calendar_events(WP_REST_Request $request) {
		$calendarId = sanitize_text_field($request->get_param('calendar_id'))
					  ?: (KGSweb_Google_Integration::get_settings()['calendar_id'] ?? '');
		$page = max(1, intval($request->get_param('page')));
		$perPage = max(1, intval($request->get_param('per_page')));

		if (empty($calendarId)) {
			return new WP_REST_Response(['message' => 'No calendar ID configured.'], 404);
		}

		$cache_key = 'kgsweb_cache_events_' . md5($calendarId);
		$events = get_transient($cache_key);

		if (!is_array($events)) {
			$events = self::fetch_and_normalize_events($calendarId);
			set_transient($cache_key, $events, HOUR_IN_SECONDS);
		}

		$total = count($events);
		$offset = ($page - 1) * $perPage;
		$slice = array_slice($events, $offset, $perPage);
		$total_pages = $perPage > 0 ? ceil($total / $perPage) : 1;

		return rest_ensure_response([
			'calendar_id' => $calendarId,
			'events'      => $slice,
			'page'        => $page,
			'per_page'    => $perPage,
			'total'       => $total,
			'total_pages' => $total_pages,
		]);
	}

}