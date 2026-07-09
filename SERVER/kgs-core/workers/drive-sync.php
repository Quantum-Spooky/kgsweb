<?php

require_once __DIR__ . '/../../kgs-core/bootstrap.php';

$client = new GoogleDriveClient();

$root = config('drive_root_folder_id');

if (!$root) {
    die("Missing drive_root_folder_id");
}

/*
|--------------------------------------------------------------------------
| SYNC RECURSIVE DRIVE TREE
|--------------------------------------------------------------------------
*/
function syncFolder($client, $folderId, $path = '')
{
    $items = $client->listFolder($folderId);

    foreach ($items as $item) {

        $name = $item['name'];
        $type = $item['type']; // folder | file

        $currentPath = trim($path . '/' . $name, '/');

        if ($type === 'folder') {

            // create local folder
            $localDir = ROOT_PATH . 'kgs-cache/drive/' . $currentPath;

            if (!is_dir($localDir)) {
                mkdir($localDir, 0777, true);
            }

            // recurse
            syncFolder($client, $item['id'], $currentPath);
        }

        if ($type === 'file') {

            // only sync CMS files
            if (!in_array($name, ['content.html', 'meta.json'])) {
                continue;
            }

            $content = $client->downloadFile($item['id']);

            $localFile = ROOT_PATH . 'kgs-cache/drive/' . $currentPath;

            file_put_contents($localFile, $content);
        }
    }
}

syncFolder($client, $root);

echo "Drive sync complete\n";