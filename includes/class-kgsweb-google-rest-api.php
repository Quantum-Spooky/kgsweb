<?php
// includes/class-kgsweb-google-rest-api.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_REST_API {
    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		// add_action( 'rest_api_init', [ 'KGSweb_Google_Integration', 'register_rest_routes' ] );
    }

    public static function register_routes() {
        $ns = 'kgsweb/v1';

        register_rest_route( $ns, '/ticker', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_ticker' ],
            'args'     => [
                'id' => [ 'type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field' ],
            ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( $ns, '/events', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_events' ],
            'args'     => [
                'calendar_id' => [ 'type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field' ],
                'page'        => [ 'type'=>'integer', 'required'=>false, 'default'=>1 ],
                'per_page'    => [ 'type'=>'integer', 'required'=>false, 'default'=>10 ],
            ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( $ns, '/menu', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_menu' ],
            'args'     => [
                'type' => [ 'type'=>'string', 'required'=>true, 'enum'=>['breakfast','lunch'] ],
            ],
            'permission_callback' => '__return_true',
        ]);

		register_rest_route( $ns, '/documents', [
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function ( WP_REST_Request $req ) {
				$root = sanitize_text_field( $req->get_param( 'root' ) ?: '' );
				$resp = KGSweb_Google_Drive_Docs::get_documents_tree_payload( $root );
				if ( is_wp_error( $resp ) ) {
					return $resp;
				}
				return rest_ensure_response( $resp );
			},
			'args' => [
				'root' => [
					'type' => 'string',
					'required' => false,
				],
			],
			'permission_callback' => '__return_true',
		]);	

        register_rest_route( $ns, '/slides', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_slides' ],
            'args'     => [
                'file_id' => [ 'type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field' ],
            ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( $ns, '/sheets', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_sheets' ],
            'args'     => [
                'sheet_id' => [ 'type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field' ],
                'range'    => [ 'type'=>'string', 'required'=>false, 'sanitize_callback'=>'sanitize_text_field' ],
            ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route( $ns, '/upload', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'post_upload' ],
            'args'     => [
                'upload-folder' => [ 'type'=>'string', 'required'=>true, 'sanitize_callback'=>'sanitize_text_field' ],
                // 'file' handled via $_FILES
            ],
            'permission_callback' => function( $request ) {
                // Require REST nonce for CSRF protection
                return wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
            },
        ]);
    }

    public static function get_ticker( WP_REST_Request $req ) {
        $id = $req->get_param( 'id' );
        $data = KGSweb_Google_Ticker::get_ticker_payload( $id );
        if ( is_wp_error( $data ) ) return $data;
        return rest_ensure_response( $data );
    }

    public static function get_events( WP_REST_Request $req ) {
        $calendar_id = $req->get_param( 'calendar_id' );
        $page = max(1, intval($req->get_param('page') ) );
        $per  = max(1, intval($req->get_param('per_page') ) );
        $data = KGSweb_Google_Upcoming_Events::get_events_payload( $calendar_id, $page, $per );
        if ( is_wp_error( $data ) ) return $data;
        return rest_ensure_response( $data );
    }

    public static function get_menu( WP_REST_Request $req ) {
        $type = $req->get_param( 'type' );
        $data = KGSweb_Google_Drive_Docs::get_menu_payload( $type );
        if ( is_wp_error( $data ) ) return $data;
        return rest_ensure_response( $data );
    }

    public static function get_documents( WP_REST_Request $req ) {
		$folder_id = $req->get_param( 'doc-folder' ) ?? $req->get_param( 'folder_id' );
        $data = KGSweb_Google_Drive_Docs::get_documents_tree_payload( $folder_id );
        if ( is_wp_error( $data ) ) return $data;
        return rest_ensure_response( $data );
    }

    public static function get_slides( WP_REST_Request $req ) {
        $file_id = $req->get_param( 'file_id' );
        $data = [ 'file_id' => $file_id, 'message' => 'TODO: implement slides cache + embed URL' ];
        return rest_ensure_response( $data );
    }

    public static function get_sheets( WP_REST_Request $req ) {
        $sheet_id = $req->get_param( 'sheet_id' );
        $range    = $req->get_param( 'range' );
        $data = [ 'sheet_id' => $sheet_id, 'range' => $range ?: (KGSweb_Google_Integration::get_settings()['sheets_default_range'] ?? 'A1:Z100'), 'rows'=>[], 'headers'=>[] ];
        return rest_ensure_response( $data );
    }

    public static function post_upload( WP_REST_Request $req ) {
        return KGSweb_Google_Secure_Upload::handle_upload_rest( $req );
    }
}