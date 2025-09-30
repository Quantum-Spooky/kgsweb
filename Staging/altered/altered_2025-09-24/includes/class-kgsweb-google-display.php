<?php
// includes/class-kgsweb-google-display.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;

class KGSweb_Google_Display {

    // Map types to option keys in the admin settings
    public static $types = [
        'breakfast-menu'    => 'kgsweb_breakfast_menu_folder',
        'lunch-menu'        => 'kgsweb_lunch_menu_folder',
        'monthly-calendar'  => 'kgsweb_monthly_calendar_folder',
        'academic-calendar' => 'kgsweb_academic_calendar_folder',
        'athletic-calendar' => 'kgsweb_athletic_calendar_folder',
        'feature-image'     => 'kgsweb_feature_image_folder',
        'pto-feature-image' => 'kgsweb_pto_feature_image_folder',
    ];

    public static function init() {
        add_shortcode('kgsweb_img_display', [__CLASS__, 'shortcode']);
    }

    /**
     * Shortcode handler
     * [kgsweb_img_display type="monthly-calendar"]
     * [kgsweb_img_display folder="FOLDER_ID"]
     */
    public static function shortcode($atts) {
        $atts = shortcode_atts([
            'type'   => '',
            'folder' => '',
        ], $atts, 'kgsweb_img_display');

        $folder_id = '';

        // If folder attribute is set, use it
        if (!empty($atts['folder'])) {
            $folder_id = $atts['folder'];
        }
        // Else if type attribute is set, look up option
        elseif (!empty($atts['type']) && isset(self::$types[$atts['type']])) {
            $folder_id = get_option(self::$types[$atts['type']]);
        }

        if (empty($folder_id)) return ''; // nothing to display

        // Fetch first file from Google Drive folder
        $first_file = KGSweb_Google_Menus::get_first_drive_file($folder_id); // reuse menus helper
        if (!$first_file) return '';

        // Determine URL for display
        $url = '';
        if (KGSweb_Google_Menus::is_pdf($first_file)) {
            $url = KGSweb_Google_Menus::pdf_to_png_url($first_file); // existing helper
        } else {
            $url = $first_file['webContentLink'] ?? '';
        }

        if (empty($url)) return '';

        return '<img src="' . esc_url($url) . '" alt="' . esc_attr($atts['type'] ?: 'display') . '" />';
    }
}

// Initialize
KGSweb_Google_Display::init();
