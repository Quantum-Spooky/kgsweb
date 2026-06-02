<?php 
// includes/class-kgsweb-google-slides.php
if (!defined('ABSPATH')) { exit; }

use Google\Client;

class KGSweb_Google_Slides {

    public static function init() { /* no-op */ }

    /*******************************
     * Shortcode renderer
     *******************************/
    public static function shortcode_render($atts = []) {
        $settings = KGSweb_Google_Integration::get_settings();
        $atts = shortcode_atts([
            'file'            => $settings['slides_file_id'] ?? '',
            'width'           => '100%',
            'height'          => '480px',
            'force_pdf'       => false,
            'show_thumbnails' => false,
            'start_slide'     => 1,
            'end_slide'       => null,
            'autoplay'        => false,
            'loop'            => false,
            'full_carousel'   => false,
            'delay'           => 3000,
        ], $atts, 'kgsweb_slides');

        $file_id = $atts['file'];
        if (!$file_id) {
            return '<div class="kgsweb-slides-empty">Slides not available.</div>';
        }

        $metadata = self::get_slides_metadata($file_id);
        if (is_wp_error($metadata) || empty($metadata['slides'])) {
            return '<div class="kgsweb-slides-empty">Slides not available.</div>';
        }

        // Determine slides to show
        $slides_to_show = $metadata['slides'];
        if ($atts['show_thumbnails']) {
            $start = max(1, intval($atts['start_slide']));
            $end   = $atts['end_slide'] ? intval($atts['end_slide']) : count($slides_to_show);
            $slides_to_show = array_slice($slides_to_show, $start-1, $end-$start+1);
        }

        $pdf_url = self::get_pdf_url($file_id);

        // Embed URL for first slide
        $embed_url = "https://docs.google.com/presentation/d/{$file_id}/embed?start=false&loop=" 
            . ($atts['loop'] ? 'true' : 'false') 
            . "&delayms=" . ($atts['autoplay'] ? intval($atts['delay']) : 0);

        // Build thumbnails only
        $thumb_html = '';
        if ($atts['show_thumbnails']) {
            $thumb_html .= '<div class="kgsweb-slides-thumbnails">';
            foreach ($slides_to_show as $slide) {
                $thumb_html .= sprintf(
                    '<img class="kgsweb-slide-thumb" src="%s" alt="Slide %d" data-src="https://docs.google.com/presentation/d/%s/embed?start=false&loop=%s&slide=%d&delayms=%d">',
                    esc_url($slide['thumbnail']),
                    esc_attr($slide['slide_number']),
                    esc_attr($file_id),
                    $atts['loop'] ? 'true' : 'false',
                    esc_attr($slide['slide_number']),
                    $atts['autoplay'] ? intval($atts['delay']) : 0
                );
            }
            $thumb_html .= '</div>';
        }

        // Force PDF
        if ($atts['force_pdf']) {
            return sprintf(
                '<div class="kgsweb-slides-pdf-wrapper">
                    <iframe src="%s" width="%s" height="%s" frameborder="0"></iframe>
                </div>%s',
                esc_url($pdf_url),
                esc_attr($atts['width']),
                esc_attr($atts['height']),
                $thumb_html
            );
        }

        // Live embed output (JS handles everything else)
        return sprintf(
            '<div class="kgsweb-slides-wrapper" data-autoplay="%s" data-delay="%d">
                <iframe class="kgsweb-slides-iframe" src="%s" width="%s" height="%s" frameborder="0" allowfullscreen mozallowfullscreen webkitallowfullscreen></iframe>
                <noscript>
                    <iframe src="%s" width="%s" height="%s" frameborder="0"></iframe>
                </noscript>
                %s
            </div>',
            $atts['autoplay'] ? 'true' : 'false',
            intval($atts['delay']),
            esc_url($embed_url),
            esc_attr($atts['width']),
            esc_attr($atts['height']),
            esc_url($pdf_url),
            esc_attr($atts['width']),
            esc_attr($atts['height']),
            $thumb_html
        );
    }

    /*******************************
     * PDF fallback helper
     *******************************/
    private static function get_pdf_url($file_id): string {
        return "https://docs.google.com/presentation/d/{$file_id}/export/pdf";
    }

    /*******************************
     * Slide Metadata Cache
     *******************************/
    public static function get_slides_metadata($file_id) {
        if (!$file_id) return new WP_Error('no_file', __('Slides file not set.', 'kgsweb'), ['status'=>404]);
        $key = 'kgsweb_cache_slides_meta_' . $file_id;
        $data = get_transient($key);
        if ($data === false) {
            $data = self::fetch_slides_metadata_from_drive($file_id);
            set_transient($key, $data, HOUR_IN_SECONDS);
        }
        return $data;
    }

    public static function refresh_slides_cache($file_id) {
        $data = self::fetch_slides_metadata_from_drive($file_id);
        set_transient('kgsweb_cache_slides_meta_' . $file_id, $data, HOUR_IN_SECONDS);
        update_option('kgsweb_cache_last_refresh_slides_' . $file_id, current_time('timestamp'));
    }

    /*******************************
     * Fetch slide metadata from Drive
     *******************************/
    private static function fetch_slides_metadata_from_drive($file_id) {
        $drive = KGSweb_Google_Integration::get_drive();
        if (!$drive || !method_exists($drive, 'get_file_contents')) {
            return ['file_id' => $file_id, 'slides' => [], 'updated_at' => current_time('timestamp')];
        }

        try {
        $slides_service = new \Google\Service\Slides(KGSweb_Google_Integration::get_google_client());
        $presentation = $slides_service->presentations->get($file_id);

        $slides = [];
        foreach ($presentation->getSlides() as $index => $slide) {
            $slide_id = $slide->getObjectId();
            $slides[] = [
                'slide_number' => $index + 1,
                'slide_id'     => $slide_id,
                'thumbnail'    => "https://docs.google.com/presentation/d/{$file_id}/export/png?id={$file_id}&pageid={$slide_id}",
            ];
        }

                return ['file_id'=>$file_id,'slides'=>$slides,'updated_at'=>current_time('timestamp')];
		} catch (Exception $e) {
			error_log('KGSWEB: Failed to fetch slides metadata - ' . $e->getMessage());
			return ['file_id'=>$file_id,'slides'=>[],'updated_at'=>current_time('timestamp')];
		}
    }
}
