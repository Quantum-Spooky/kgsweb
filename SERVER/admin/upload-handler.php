#!/usr/bin/env php
<?php
/* admin/upload-handler.php */
require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Unauthorized.");
}

try {
    $drive = GoogleService::drive();
    $imageId = '';

    // 1. Upload to Shared Drive
    if (!empty($_FILES['image']['tmp_name'])) {
        $meta = new \Google\Service\Drive\DriveFile([
            'name' => 'feed_' . time() . '.png',
            'parents' => [config('live_feed_images_folder_id')]
        ]);
        $file = $drive->files->create($meta, [
            'data' => file_get_contents($_FILES['image']['tmp_name']),
            'mimeType' => 'image/png',
            'uploadType' => 'multipart',
            'supportsAllDrives' => true,
            'fields' => 'id'
        ]);
        $imageId = $file->id;
    }

    // 2. Append to Sheet
    $row = [date('F j, Y'), date('g:i a'), $_POST['content'], $imageId, $_SESSION['user_name']];
    GoogleService::sheets()->spreadsheets_values->append(
        config('live_feed_sheet_id'), 
        'Sheet1!A:E', 
        new \Google\Service\Sheets\ValueRange(['values' => [$row]]), 
        ['valueInputOption' => 'RAW']
    );
    
    // 3. Trigger immediate worker refresh
    exec("php " . ROOT_PATH . "kgs-core/workers/refresh-drive-cache.php > /dev/null 2>&1 &");
    header("Location: live-feed-post.php?success=1");

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}