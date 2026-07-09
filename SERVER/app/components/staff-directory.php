<?php
/**
 * UI COMPONENT
 *
 * Responsibility:
 * - Render HTML for a single component type
 * - Use only provided $data array
 *
 * Rules:
 * - MUST NOT access CMS
 * - MUST NOT access cache
 * - MUST NOT perform routing or data fetching
 *
 * This file is PURE PRESENTATION LAYER.
 */
 
// config() without a second parameter returns NULL/Empty if the cell is blank.
$sheetId = $data['sheet_id'] ?? config('staff_directory_sheet_id');

// If the stakeholder cleared the cell, $photoGallery is empty. WE MUST EXIT.
if (empty($sheetId)) {
    echo "<!-- Staff Directory Hidden: sheetID is empty in cfg -->";
    return;
}

?>
