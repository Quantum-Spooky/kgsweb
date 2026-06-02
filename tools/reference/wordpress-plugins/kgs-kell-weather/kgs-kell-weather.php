<?php
/*
Plugin Name: KGS Kell Weather
Description: Display compact weather information for Kell, IL. Use shortcode: [kgs_kell_weather]
Version: 1.0
Author: KGS
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enqueue the CSS file
function kgs_kell_weather_enqueue_styles() {
    wp_enqueue_style( 'kell-weather-style', plugin_dir_url( __FILE__ ) . 'assets/css/kgs-kell-weather.css' );
}
add_action( 'wp_enqueue_scripts', 'kgs_kell_weather_enqueue_styles', 25 );

// Enqueue the Javascript files
function kgs_kell_weather_enqueue_scripts() {
    wp_enqueue_script('kgs-weather-accordion', plugins_url('assets/js/kgs-kell-weather.js', __FILE__), array('jquery'), null, true);
    wp_enqueue_script('skycons', 'https://darkskyapp.github.io/skycons/skycons.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'kgs_kell_weather_enqueue_scripts', 20);


// Include the weather display functionality
require_once plugin_dir_path( __FILE__ ) . 'includes/weather-display.php';

// Register the shortcode to display the weather widget
function register_kgs_kell_weather_shortcode() {
    add_shortcode('kgs_kell_weather', 'display_kgs_kell_weather', 25);
}
add_action('init', 'register_kgs_kell_weather_shortcode');