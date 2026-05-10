<?php
/*
Plugin Name: KGS Kell Weather
Description: Display Kell Weather. Use shortcode: [kgs_kell_weather]
Version: 1.2
Author: KGS
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function display_kgs_kell_weather() {
    // API URL for Kell, IL Weather
    $weather_url = 'https://api.weather.gov/gridpoints/LSX/141,70/forecast';

    // Fetch the JSON data from the weather API
    $response = wp_remote_get($weather_url);

    // Handle errors in the API request
    if (is_wp_error($response)) {
        return 'Unable to retrieve weather data. Please try again later.';
    }

    // Get the body content from the response and decode the JSON
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['properties']['periods'])) {
        return 'Error retrieving weather data.';
    }

    // Extract periods (weather forecasts)
    $periods = $data['properties']['periods'];

    // Ensure enough periods are available
    if (count($periods) < 10) {
        return 'Not enough forecast data available.';
    }

    // Timezone for Chicago
    date_default_timezone_set('America/Chicago');
    $current_time = date('g:i A');
    $current_date = date('l, M d, Y');

    // Display the current period (Period 1)
    $current_period = $periods[0];
    $location = "Kell, IL";
    $temperature = $current_period['temperature'];
    $wind_speed = $current_period['windSpeed'];
    $wind_direction = $current_period['windDirection'];
    $icon = $current_period['icon'];
    $detailed_forecast = $current_period['detailedForecast'];

    // Start capturing output
    ob_start(); ?>
   <div class="kgs-kell-weather-widget">
    <div class="weather-current">
        <h3 class="center"><?php echo esc_html($location); ?> </h3>
        <p class="weather-time center"><?php echo esc_html($current_time); ?></p>
        <p class="weather-date center"><?php echo esc_html($current_date); ?></p>
        <div class="weather-info">
            <canvas id="weather-icon" width="128" height="128" class="center"></canvas>
            <p id="temperature" class="center"><?php echo esc_html($temperature); ?>°F</p>
            <p id="detailed-forecast" class="center"><?php echo wp_strip_all_tags($detailed_forecast); ?></p>
            <p id="wind-info" class="center">Wind is <?php echo esc_html($wind_speed); ?> from the <?php echo esc_html($wind_direction); ?>. </p>
        </div>
    </div>
    
    <div class="weather-forecast">
        <button class="accordion center"><h4 class="">Upcoming Forecast<span class="caret">&#9660;</span></h4></button>
        <div class="panel">
            <?php
            for ($i = 1; $i < 10; $i++) {
                $period = $periods[$i];
                ?>
                <div class="forecast-box">
                    <strong class="period-name"><?php echo esc_html($period['name']); ?>:</strong>
                    <span class="detailed-forecast"><?php echo wp_strip_all_tags($period['detailedForecast']); ?></span>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>

    <?php

    // Return the captured output and prevent <p> tags wrapping
    return shortcode_unautop(ob_get_clean());
}

// Register the shortcode with WordPress
add_shortcode('kgs_kell_weather', 'display_kgs_kell_weather');
?>