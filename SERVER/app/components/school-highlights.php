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
  
/**
 * school-highlights.php
 * School Highlights Section
 */

// Check the Toggle from the Google Sheet
if (strtoupper(config('show_highlights')) !== 'TRUE') return;
 
// Use the ID passed from data (ideally via a token in JSON) 
// or fallback to the config.
$docId = $data['doc_id'] ?? config('school_highlights');

// EXIT: If no ID is found, hide the component entirely
if (empty($docId)) {
    echo "<!-- School Highlights Hidden: docId is empty in cfg -->";
    return;
}

// Corrected syntax: the key and value must both be inside the brackets
render_component('google-doc-content', ['doc_id' => $docId]);