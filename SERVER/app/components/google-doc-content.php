<?php
/**
 * UI COMPONENT: Google Doc Content
 * Sanitizes and renders exported HTML from a cached Google Doc.
 * Fixes: Overlapping/Floats, Inline Style Bloat, and restores Auto-Hide.
 */

$docId = $data['doc_id'] ?? null;
if (!$docId) return;

$cachePath = ROOT_PATH . "kgs-cache/google/html-content/{$docId}.html";

if (file_exists($cachePath)) {
    $rawHtml = file_get_contents($cachePath);

    // 1. Extract only the content inside the <body> tags
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $rawHtml, $matches)) {
        $content = $matches[1];
    } else {
        $content = $rawHtml;
    }

    // 2. Remove Google's internal <style> blocks (The ones that break the whole site width)
    $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
    
    // 3. Remove inline style="..." attributes (The ones that cause overlapping/floats)
    $content = preg_replace('/style="[^"]*"/', '', $content);

    // 4. RESTORED: Silent Auto-Hide logic
    // If the doc is empty or just contains an empty paragraph, we hide the component
    $textOnly = trim(strip_tags($content));
    if (empty($textOnly)) {
        echo "<!-- Google Doc $docId hidden: No text content found -->";
        return;
    }

    /**
     * 5. FINAL RENDER
     * - We removed the 'container' class so the Layout Wrapper handles the width.
     * - Added 'flow-root' and 'clearfix' to force the Smart Grid to stay BELOW this text.
     */
    echo '<div class="container py-4">';
	echo '<div class="kgs-google-doc-content d-block clearfix w-100 mb-4" style="display: flow-root; clear: both;">';
    echo $content;
    echo '</div>';
	echo '</div>';

} else {
    echo "<!-- Content not found for Doc ID: {$docId} -->";
}