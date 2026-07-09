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
$calloutId = $data['callout_id'] ?? config('callout_id');

// If the stakeholder cleared the cell, $calloutId is empty. WE MUST EXIT.
if (empty($calloutId)) {
    echo "<!-- Callout Hidden: No callout_id ID set in config -->";
    return;
}

?>