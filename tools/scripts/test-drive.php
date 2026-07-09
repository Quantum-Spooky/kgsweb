<?php

require_once 'cfg/config.php';

require_once ROOT_PATH . 'vendor/autoload.php';

require_once ROOT_PATH . 'kgs-core/google/GoogleService.php';
require_once ROOT_PATH . 'kgs-core/google/GoogleDrive.php';

$drive = new GoogleDrive();

$items = $drive->listFolder(
    config('drive_root_folder_id');
);

echo '<pre>';
print_r($items);
echo '</pre>';