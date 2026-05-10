<?php
/*
Plugin Name: Rename Documents to Lowercase
Description: Renames files and folders in the documents directory to lowercase.
Version: 1.0
Author: KGS
*/

function rename_files_to_lowercase($directory) {
    $dir = opendir($directory);
    while (($file = readdir($dir)) !== false) {
        $full_path = $directory . DIRECTORY_SEPARATOR . $file;
        if ($file != '.' && $file != '..') {
            $new_name = strtolower($file);
            if ($full_path != $directory . DIRECTORY_SEPARATOR . $new_name) {
                rename($full_path, $directory . DIRECTORY_SEPARATOR . $new_name);
            }

            if (is_dir($directory . DIRECTORY_SEPARATOR . $new_name)) {
                rename_files_to_lowercase($directory . DIRECTORY_SEPARATOR . $new_name);
            }
        }
    }
    closedir($dir);
}

function trigger_file_rename() {
    if (current_user_can('manage_options')) {
        $uploads_dir = wp_upload_dir()['basedir'];
        $documents_dir = trailingslashit($uploads_dir) . 'documents';
        rename_files_to_lowercase($documents_dir);
        echo '<div class="updated"><p>All files and folders in the /documents folder have been renamed to lowercase.</p></div>';
    }
}

function add_rename_button() {
    echo '<div class="wrap"><h2>Rename Documents to Lowercase</h2>';
    echo '<form method="post"><input type="submit" name="rename_files" class="button button-primary" value="Rename Documents to Lowercase"></form></div>';
    
    if (isset($_POST['rename_files'])) {
        trigger_file_rename();
    }
}

// Add the Rename Files page under the Tools menu
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php', // Parent menu (Tools)
        'Rename Documents to Lowercase', // Page title
        'Rename Documents', // Menu title
        'manage_options', // Capability required
        'rename-documents-to-lowercase', // Menu slug
        'add_rename_button' // Callback function to display the page
    );
});
?>
