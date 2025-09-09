<?php
// includes/class-kgsweb-google-helpers.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Helpers {
    public static function init() { /* no-op */ }

    public static function sanitize_filename( $name ) {
        $name = wp_strip_all_tags( $name );
        $name = remove_accents( $name );
        $name = preg_replace( '/[^\w\.\-]+/u', '-', $name );
        $name = preg_replace( '/-+/', '-', $name );
        return trim( $name, '-' );
    }

    public static function icon_for_mime_or_ext( $mime, $ext ) {
        $ext = strtolower( $ext );
        $map = [
            'pdf'=>'file-pdf', 'doc'=>'file-word','docx'=>'file-word','rtf'=>'file-word',
            'xls'=>'file-excel','xlsx'=>'file-excel','csv'=>'file-csv',
            'ppt'=>'file-powerpoint','pptx'=>'file-powerpoint','ppsx'=>'file-powerpoint',
            'png'=>'file-image','jpg'=>'file-image','jpeg'=>'file-image','gif'=>'file-image','webp'=>'file-image',
            'mp3'=>'file-audio','wav'=>'file-audio',
            'mp4'=>'file-video','m4v'=>'file-video','mov'=>'file-video','avi'=>'file-video',
            'zip'=>'file-archive','txt'=>'file-lines'
        ];
        return $map[$ext] ?? 'file';
    }

    public static function format_event_datetime( $start_iso, $end_iso ) {
        // TODO: Respect WP timezone; return "Start" or "Start - End" or "All Day"
        return '';
    }
}