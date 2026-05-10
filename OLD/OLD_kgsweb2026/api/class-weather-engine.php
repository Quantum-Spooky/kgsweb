<?php
// kgsweb2026/api/class-weather-engine.php

class KGSWeather {
    
    // Static class - no constructor needed, but we can add an init if required
    
    public static function get_weatherData() {
        $cached = KGSHelper::get_cache('weather_data'); 
        if ($cached) {
            return is_array($cached) ? $cached : json_decode($cached, true);
        }

        $url = 'https://api.weather.gov/gridpoints/LSX/141,70/forecast';
        
        $options = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: KGSWebProject (contact@kellgradeschool.com)\r\n"
            ]
        ];

        try {
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) throw new \Exception("NWS API unreachable");

            $data = json_decode($response, true);
            if (!isset($data['properties']['periods'])) throw new \Exception("Invalid API response");

            $periods = $data['properties']['periods'];
            
            $weatherPayload = [
                'current' => [
                    'temp'      => $periods[0]['temperature'] . '°F',
                    'condition' => $periods[0]['shortForecast'],
                    'detailed'  => $periods[0]['detailedForecast'],
                    'wind'      => $periods[0]['windSpeed'] . " from the " . $periods[0]['windDirection'],
                    'icon'      => $periods[0]['icon'],
                    'time'      => date('g:i A'),
                    'date'      => date('l, M d, Y')
                ],
                'forecast' => array_slice($periods, 1, 9) 
            ];

            KGSHelper::set_cache('weather_data', $weatherPayload);
            return $weatherPayload;

        } catch (\Exception $e) {
            error_log("Weather Error: " . $e->getMessage());
            return ['error' => 'Weather currently unavailable'];
        }
    }
}