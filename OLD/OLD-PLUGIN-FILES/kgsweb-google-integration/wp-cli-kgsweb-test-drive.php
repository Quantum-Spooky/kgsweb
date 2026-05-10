<?php
/**
 * WP-CLI command to test KGSweb Google Drive functions.
 *
 * Usage: wp kgsweb test-drive
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'kgsweb test-drive', function() {
        // 1. Test list_files_in_folder
        $folder_id = get_option('kgsweb_google_drive_root_folder_id');
        if (!$folder_id) {
            WP_CLI::warning("Root folder ID not set in options.");
            return;
        }

        WP_CLI::log("=== LIST FILES IN ROOT FOLDER ===");
        $files = KGSweb_Google_Drive_Docs::list_files_in_folder($folder_id);
        if (empty($files)) {
            WP_CLI::warning("No files returned.");
        } else {
            foreach ($files as $file) {
                WP_CLI::log(sprintf(
                    "%s (%s) size=%s modified=%s",
                    $file['name'] ?? 'unknown',
                    $file['mimeType'] ?? '',
                    $file['size'] ?? 0,
                    $file['modifiedTime'] ?? ''
                ));
            }
        }

        // 2. Test get_file_contents (first non-folder file)
        $first_file = null;
        foreach ($files as $f) {
            if (!str_starts_with($f['mimeType'], 'application/vnd.google-apps.folder')) {
                $first_file = $f;
                break;
            }
        }

        if ($first_file) {
            WP_CLI::log("=== FETCH CONTENTS OF: {$first_file['name']} ===");
            $contents = KGSweb_Google_Drive_Docs::get_file_contents($first_file['id']);
            WP_CLI::log(substr($contents, 0, 200) . (strlen($contents) > 200 ? '...' : ''));
        } else {
            WP_CLI::warning("No regular file found to test contents.");
        }

        // 3. Test build_documents_tree
        WP_CLI::log("=== BUILD DOCUMENTS TREE ===");
        $tree = KGSweb_Google_Drive_Docs::build_documents_tree();
        WP_CLI::log(json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        WP_CLI::success("Drive tests completed.");
    });
}
