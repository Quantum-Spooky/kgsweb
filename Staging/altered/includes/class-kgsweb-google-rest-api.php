<?php
// includes/class-kgsweb-google-rest-api.php
if (!defined('ABSPATH')) exit;

class KGSweb_Google_REST_API {

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
    }

    public static function register_rest_routes() {
        $ns = 'kgsweb/v1';

        // Ticker
        register_rest_route($ns, '/ticker', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_ticker_request'],
            'args' => [
                'folder' => ['type'=>'string','required'=>false,'sanitize_callback'=>'sanitize_text_field'],
                'file'   => ['type'=>'string','required'=>false,'sanitize_callback'=>'sanitize_text_field'],
            ],
            'permission_callback' => '__return_true',
        ]);

        // Calendar Events
        register_rest_route($ns, '/events', [
            'methods' => 'GET',
            'callback' => [new KGSweb_Google_Upcoming_Events(), 'rest_calendar_events'],
            'args' => [
                'calendar_id' => ['type'=>'string','required'=>false,'sanitize_callback'=>'sanitize_text_field'],
                'page' => ['type'=>'integer','required'=>false,'default'=>1],
                'per_page' => ['type'=>'integer','required'=>false,'default'=>10],
            ],
            'permission_callback' => '__return_true',
        ]);

        // Menu
        register_rest_route($ns, '/menu', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_menu'],
            'args' => [
                'type' => ['type'=>'string','required'=>true,'enum'=>['breakfast','lunch']],
            ],
            'permission_callback' => '__return_true',
        ]);

        // Documents
        register_rest_route($ns, '/documents', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_documents'],
            'args' => ['root'=>['type'=>'string','required'=>false]],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($ns, '/documents/refresh', [
            'methods' => 'POST',
            'callback' => function(WP_REST_Request $req) {
                $folder_id = $req->get_param('root') ?: KGSweb_Google_Drive_Docs::get_public_root_id();
                if (!current_user_can('manage_options')) {
                    return new WP_Error('forbidden','No permission',['status'=>403]);
                }
                KGSweb_Google_Drive_Docs::rebuild_documents_tree_cache($folder_id);
                return rest_ensure_response(['success'=>true,'folder'=>$folder_id]);
            },
            'args' => ['root'=>['type'=>'string','required'=>false]],
            'permission_callback' => '__return_true',
        ]);

        // Slides
        register_rest_route($ns, '/slides', [
            'methods'=>'GET',
            'callback'=>[__CLASS__,'get_slides'],
            'args'=>['file_id'=>['type'=>'string','required'=>false,'sanitize_callback'=>'sanitize_text_field']],
            'permission_callback'=>'__return_true',
        ]);

        // Sheets
        register_rest_route($ns, '/sheets', [
            'methods'=>'GET',
            'callback'=>[__CLASS__,'get_sheets'],
            'args'=>[
                'sheet_id'=>['type'=>'string','required'=>false,'sanitize_callback'=>'sanitize_text_field'],
                'range'=>['type'=>'string','required'=>false,'sanitize_callback'=>'sanitize_text_field'],
            ],
            'permission_callback'=>'__return_true',
        ]);

        // Upload
        register_rest_route($ns, '/upload', [
            'methods'=>'POST',
            'callback'=>[__CLASS__,'post_upload'],
            'args'=>['upload-folder'=>['type'=>'string','required'=>true,'sanitize_callback'=>'sanitize_text_field']],
            'permission_callback'=>function($request){
                return wp_verify_nonce($request->get_header('X-WP-Nonce'),'wp_rest');
            },
        ]);
    }

    // ------------------------
    // Ticker
    // ------------------------
    public static function handle_ticker_request(WP_REST_Request $request) {
        $folder = sanitize_text_field($request->get_param('folder') ?: '');
        $file   = sanitize_text_field($request->get_param('file') ?: '');

        if (!$file && $folder) {
            $files = KGSweb_Google_Ticker::get_ticker_items($folder);
            $file = $files[0]['id'] ?? null;
        }

        $text = KGSweb_Google_Ticker::get_cached_ticker($folder, $file);

        if (!$text && method_exists('KGSweb_Google_Ticker','refresh_cache_cron')) {
            KGSweb_Google_Ticker::refresh_cache_cron();
            $text = KGSweb_Google_Ticker::get_cached_ticker($folder, $file);
        }

        return rest_ensure_response([
            'success' => (bool)$text,
            'ticker' => $text ?: '',
        ]);
    }

    // ------------------------
    // Menu
    // ------------------------
    public static function get_menu(WP_REST_Request $req) {
        $data = KGSweb_Google_Drive_Docs::get_menu_payload($req->get_param('type'));
        return rest_ensure_response($data);
    }

    // ------------------------
    // Documents
    // ------------------------
    public static function get_documents(WP_REST_Request $req) {
        $folder_id = $req->get_param('root') ?: KGSweb_Google_Drive_Docs::get_public_root_id();
        if (!$folder_id) return new WP_Error('no_root','No document root configured',['status'=>403]);

        $payload = KGSweb_Google_Helpers::get_cached_documents_tree($folder_id);
        return rest_ensure_response($payload);
    }

    // ------------------------
    // Slides
    // ------------------------
    public static function get_slides(WP_REST_Request $req) {
        $file_id = $req->get_param('file_id');
        if (!$file_id) return new WP_Error('missing_file','Slide file ID required',['status'=>400]);

        $cache_key = "kgsweb_cache_slides_{$file_id}";

        $data = KGSweb_Google_Helpers::get_transient_or_fetch($cache_key, function() use ($file_id) {
            return [
                'file_id'   => $file_id,
                'embed_url' => "https://docs.google.com/presentation/d/{$file_id}/embed",
                'message'   => 'Slides fetched or placeholder',
            ];
        }, HOUR_IN_SECONDS);

        return rest_ensure_response($data);
    }

    // ------------------------
    // Sheets
    // ------------------------
    public static function get_sheets(WP_REST_Request $req) {
        $sheet_id = $req->get_param('sheet_id');
        $range    = $req->get_param('range') ?: (KGSweb_Google_Integration::get_settings()['sheets_default_range'] ?? 'A1:Z100');

        if (!$sheet_id) return new WP_Error('missing_sheet','Sheet ID required',['status'=>400]);

        $cache_key = "kgsweb_cache_sheet_{$sheet_id}_" . md5($range);

        $data = KGSweb_Google_Helpers::get_transient_or_fetch($cache_key, function() use ($sheet_id,$range){
            return [
                'sheet_id'=>$sheet_id,
                'range'=>$range,
                'rows'=>[],
                'headers'=>[],
                'message'=>'Sheet fetched or placeholder',
            ];
        }, HOUR_IN_SECONDS);

        return rest_ensure_response($data);
    }

    // ------------------------
    // Upload
    // ------------------------
    public static function post_upload(WP_REST_Request $req) {
        return KGSweb_Google_Secure_Upload::handle_upload_rest($req);
    }
}
