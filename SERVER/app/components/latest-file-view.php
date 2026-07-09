<?php
/**
 * UI COMPONENT: Latest File View
 */

$folderId = $data['folder_id'] ?? config('latest_file_folder_id');
if (empty($folderId) || str_starts_with($folderId, '@')) {
    echo "<!-- Latest File View Hidden: No folder id set in config -->";
    return;
}

$masterRootId = config('master_root_folder_id', '1o0X89kQL_cXEUpPOSZXcCUFz8bzCtmAp');
$masterTree = GoogleDriveCache::get('drive-trees', 'tree_' . $masterRootId, 0);

if (!function_exists('kgs_find_folder_in_tree')) {
    function kgs_find_folder_in_tree($items, string $targetId) {
        if (!is_array($items)) return null;
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (($item['id'] ?? '') === $targetId) return $item['children'] ?? [];
            if (!empty($item['children'])) {
                $found = kgs_find_folder_in_tree($item['children'], $targetId);
                if ($found !== null) return $found;
            }
        }
        return null;
    }
}

// Search master tree directly (no 'items' wrapper needed with Cache::get)
$items = kgs_find_folder_in_tree($masterTree, $folderId);

if (empty($items)) {
    // Final fallback: check for standalone cache
    $items = GoogleDriveCache::get('drive-trees', 'tree_' . $folderId, 0);
}

if (empty($items)) return;

// Sort newest first
usort($items, function($a, $b) {
    $da = function_exists('kgs_fl_extract_sort_date') ? kgs_fl_extract_sort_date($a['name'] ?? '') : null;
    $db = function_exists('kgs_fl_extract_sort_date') ? kgs_fl_extract_sort_date($b['name'] ?? '') : null;
    if ($da && $db) return strcmp($db, $da); 
    return strcmp($b['modifiedTime'] ?? '', $a['modifiedTime'] ?? '');
});

$latest = $items[0];
$fileId = $latest['id'];
$ext = strtolower(pathinfo($latest['name'], PATHINFO_EXTENSION));
$isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
$title = $data['title'] ?? $latest['name'];
?>

<div class="kgs-latest-file card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($title) ?></h6>
        <a href="<?= $latest['webViewLink'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0">View</a>
    </div>
    <div class="kgs-file-body">
        <?php if ($isImage): ?>
            <img src="<?= get_drive_url($fileId, 1200) ?>" class="img-fluid d-block mx-auto" style="max-height:600px; width:auto;">
			
			
		<?php elseif ($ext === 'pdf'): ?>
			<div class="kgs-pdf-wrapper shadow-sm rounded overflow-hidden" style="background-color: #fff; border: 1px solid #ddd;">
				<!-- We use a custom height here to show the full height of a portrait PDF -->
				<iframe 
					src="https://drive.google.com/file/d/<?= $fileId ?>/preview?view=FitH" 
					style="width: 100%; height: 800px; border: none;" 
					allow="autoplay">
				</iframe>
			</div>
		<?php endif; ?>	
    </div>
</div>