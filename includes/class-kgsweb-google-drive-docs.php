<?php
// includes/class-kgsweb-google-drive-docs.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class KGSweb_Google_Drive_Docs {
    public static function init() { /* no-op */ }

    // Cron refresh
    public static function refresh_cache_cron() {
        // Rebuild public docs tree
        self::rebuild_documents_tree_cache( self::get_public_root_id() );
        // Rebuild upload folder list
        self::rebuild_upload_tree_cache( self::get_upload_root_id() );
        // Menus
        self::refresh_menu_cache( 'breakfast' );
        self::refresh_menu_cache( 'lunch' );
    }

    public static function get_public_root_id() {
        return KGSweb_Google_Integration::get_settings()['public_docs_root_id'] ?? '';
    }
    public static function get_upload_root_id() {
        return KGSweb_Google_Integration::get_settings()['upload_root_id'] ?? '';
    }

    // Public documents tree payload
    public static function get_documents_tree_payload( $folder_id = '' ) {
        $root = $folder_id ?: self::get_public_root_id();
        $key  = 'kgsweb_cache_documents_' . $root;
        $tree = KGSweb_Google_Integration::get_transient( $key );
        if ( false === $tree ) {
            $tree = self::build_documents_tree( $root );
            $tree = self::filter_empty_branches( $tree );
            KGSweb_Google_Integration::set_transient( $key, $tree, HOUR_IN_SECONDS );
        }
        if ( empty( $tree ) ) return new WP_Error( 'no_docs', __( 'No documents available.', 'kgsweb' ), [ 'status'=>404 ] );
        return [ 'root_id' => $root, 'tree' => $tree, 'updated_at' => time() ];
    }

	public static function rebuild_documents_tree_cache( $root ) {
		if ( empty( $root ) ) return;
		delete_transient( 'kgsweb_docs_tree_' . md5( $root ) );
		$tree = KGSweb_Google_Integration::get_cached_documents_tree( $root );
		KGSweb_Google_Integration::set_transient( 'kgsweb_cache_documents_' . $root, $tree, HOUR_IN_SECONDS );
		update_option( 'kgsweb_cache_last_refresh_documents_' . $root, time() );
	}

    public static function rebuild_upload_tree_cache( $root ) {
        if ( empty( $root ) ) return;
        $tree = self::build_folders_only_tree( $root ); // includes empty folders
        KGSweb_Google_Integration::set_transient( 'kgsweb_cache_upload_tree_' . $root, $tree, HOUR_IN_SECONDS );
        update_option( 'kgsweb_cache_last_refresh_uploadtree_'.$root, time() );
    }

    // Drive traversal
	public static function build_documents_tree( $root_id ) {
		$client = KGSweb_Google_Integration::get_drive_client();
		if ( ! $client || empty( $root_id ) ) return [];

		$tree = [];

		$queue = [ [ 'id' => $root_id, 'name' => '', 'path' => [] ] ];

		while ( ! empty( $queue ) ) {
			$current = array_shift( $queue );
			$folder_id = $current['id'];
			$path = $current['path'];

			$items = self::list_drive_children( $client, $folder_id );
			$children = [];

			foreach ( $items as $item ) {
				$node = [
					'id' => $item['id'],
					'name' => $item['name'],
					'type' => $item['mimeType'] === 'application/vnd.google-apps.folder' ? 'folder' : 'file',
				];

				if ( $node['type'] === 'file' ) {
					$node['mime'] = $item['mimeType'];
					$node['size'] = $item['size'] ?? 0;
					$node['modifiedTime'] = $item['modifiedTime'] ?? '';
					$ext = strtolower( pathinfo( $item['name'], PATHINFO_EXTENSION ) );
					$node['icon'] = KGSweb_Google_Helpers::icon_for_mime_or_ext( $item['mimeType'], $ext );
					$children[] = $node;
				} else {
					$queue[] = [
						'id' => $item['id'],
						'name' => $item['name'],
						'path' => array_merge( $path, [ $item['name'] ] )
					];
					$children[] = $node + [ 'children' => [] ]; // placeholder
				}
			}

			if ( $folder_id === $root_id ) {
				$tree = $children;
			} else {
				self::inject_children( $tree, $folder_id, $children );
			}
		}

		return $tree;
	}
	
	
	/* Helper functions */

	private static function list_drive_children( $client, $folder_id ) {
		$service = new Google_Service_Drive( $client );
		$params = [
			'q' => sprintf( "'%s' in parents and trashed = false", $folder_id ),
			'fields' => 'files(id,name,mimeType,size,modifiedTime)',
			'pageSize' => 1000
		];
		$results = $service->files->listFiles( $params );
		return $results->getFiles();
	}


	private static function inject_children( &$nodes, $target_id, $children ) {
		foreach ( $nodes as &$node ) {
			if ( isset( $node['id'] ) && $node['id'] === $target_id && $node['type'] === 'folder' ) {
				$node['children'] = $children;
				return true;
			}
			if ( ! empty( $node['children'] ) ) {
				if ( self::inject_children( $node['children'], $target_id, $children ) ) return true;
			}
		}
		return false;
	}


    private static function build_folders_only_tree( $root_id ) {
        // TODO: Retrieve folders/subfolders including empty ones
        return [];
    }

    public static function folder_exists_in_upload_tree( $folder_id ) {
        $root = self::get_upload_root_id();
        $tree = get_transient( 'kgsweb_cache_upload_tree_' . $root );
        if ( false === $tree ) {
            $tree = self::build_folders_only_tree( $root );
            KGSweb_Google_Integration::set_transient( 'kgsweb_cache_upload_tree_' . $root, $tree, HOUR_IN_SECONDS );
        }
        return self::search_tree_for_id( $tree, $folder_id );
    }

    public static function folder_path_from_id( $folder_id ) {
        // TODO: Resolve a path-like string from the cached upload tree for folder_id
        return sanitize_title( $folder_id );
    }

    private static function search_tree_for_id( $nodes, $id ) {
        foreach ( (array) $nodes as $n ) {
            if ( isset($n['id']) && $n['id'] === $id ) return true;
            if ( ! empty( $n['children'] ) && self::search_tree_for_id( $n['children'], $id ) ) return true;
        }
        return false;
    }

    public static function filter_empty_branches( $node ) {
        if ( empty( $node ) ) return $node;
        if ( isset( $node['type'] ) && $node['type'] === 'file' ) return $node; // files are leaves
        if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
            $filtered = [];
            foreach ( $node['children'] as $child ) {
                $c = self::filter_empty_branches( $child );
                if ( $c ) $filtered[] = $c;
            }
            // Keep folder only if it has at least one file somewhere below
            $has_file_descendant = self::has_file_descendant( $filtered );
            if ( ! $has_file_descendant ) return null;
            $node['children'] = $filtered;
            return $node;
        }
        return null;
    }

    private static function has_file_descendant( $nodes ) {
        foreach ( (array) $nodes as $n ) {
            if ( isset( $n['type'] ) && $n['type'] === 'file' ) return true;
            if ( ! empty( $n['children'] ) && self::has_file_descendant( $n['children'] ) ) return true;
        }
        return false;
    }

    // Menus (Breakfast/Lunch)
    public static function get_menu_payload( $type ) {
        $key = 'kgsweb_cache_menu_' . $type;
        $data = get_transient( $key );
        if ( false === $data ) {
            $data = self::build_latest_menu_image( $type );
            set_transient( $key, $data, HOUR_IN_SECONDS );
        }
        if ( empty( $data['image_url'] ) ) return new WP_Error( 'no_menu', __( 'Menu not available.', 'kgsweb' ), [ 'status'=>404 ] );
        return $data;
    }

    public static function refresh_menu_cache( $type ) {
        $data = self::build_latest_menu_image( $type );
        set_transient( 'kgsweb_cache_menu_' . $type, $data, HOUR_IN_SECONDS );
        update_option( 'kgsweb_cache_last_refresh_menu_'.$type, time() );
    }

    private static function build_latest_menu_image( $type ) {
        // TODO: From folder ID, find most recent file; if PDF → convert via Imagick; optimize size; store locally; return URL + dims.
        return [ 'type'=>$type, 'image_url'=>'', 'width'=>0, 'height'=>0, 'updated_at'=>time() ];
    }
}



























