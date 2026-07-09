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
$photoGallery = $data['photo_gallery_id'] ?? config('photo_gallery_id');

// If the stakeholder cleared the cell, $photoGallery is empty. WE MUST EXIT.
if (empty($photoGallery)) {
    echo "<!-- Photo Gallery Hidden: $photoGallery is empty -->";
    return;
}

?>