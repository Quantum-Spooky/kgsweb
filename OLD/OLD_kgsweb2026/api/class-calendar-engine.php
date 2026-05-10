<?php
// kgsweb2026/api/class-calendar-engine.php

class KGSCalendar {
    public static function get_upcomingEvents($calendarId = null, $filter = null) {
        try {
            $base_dir = dirname(__DIR__);
            $config = require $base_dir . '/config/config.php';

            $calendarId = $calendarId ?? $config['calendars']['main'];

            // Unique cache key
            $cacheKey = 'cal_' . md5($calendarId . $filter);
            
            // 1. TRUST THE HELPER: It returns the array if it's JSON, or string if not.
            $cached = KGSHelper::get_cache($cacheKey);
            if ($cached) return $cached; 

            $client = KGSHelper::getClient();
            $service = new \Google\Service\Calendar($client);

            $timeMin = new \DateTime('today', new \DateTimeZone('America/Chicago')); 
            
            $optParams = [
                'maxResults'   => 10,
                'orderBy'      => 'startTime',
                'singleEvents' => true, 
                'timeMin'      => $timeMin->format(\DateTime::RFC3339),
            ];

            if ($filter) {
                $optParams['q'] = $filter;
            }

            $results = $service->events->listEvents($calendarId, $optParams);
            $events = [];

            foreach ($results->getItems() as $event) {
                $events[] = [
                    'summary'  => $event->getSummary(),
                    'start'    => $event->start->dateTime ?: $event->start->date,
                    'end'      => $event->end->dateTime ?: $event->end->date,
                    'isAllDay' => empty($event->start->dateTime),
                    'location' => $event->getLocation() ?? ''
                ];
            }

            // 2. TRUST THE HELPER: Pass the RAW ARRAY.
            // Helper will see it's not a string and json_encode it for you.
            KGSHelper::set_cache($cacheKey, $events);
            
            return $events;

        } catch (\Exception $e) {
            error_log("KGS Calendar Error: " . $e->getMessage());
            return ['error' => 'Calendar temporarily unavailable.'];
        }
    }
}