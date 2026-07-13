<?php
/**
 * UI COMPONENT: Link Tiles
 * 
 * Responsibility:
 * - Recursively finds children of the current page (e.g., Activities -> Sports -> Baseball).
 * - Pulls categorized link groups from the 'Links' tab.
 * - Resolves icons dynamically via get_icon() with keyword-seeking logic.
 * - Respects 'Icon Style' (Solid vs Regular/Outline) from the spreadsheet.
 */

$links = $data['links'] ?? [];
$sourceType = $data['source_type'] ?? 'registry'; 
$category = $data['category'] ?? null;

// 1. DYNAMIC POPULATION LOGIC
if (empty($links)) {
    if ($sourceType === 'site_menu') {
        /**
         * DEEP CONTEXT DETECTION
         * Looks at the URL path to find the current section's child pages.
         */
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $base = config('base_url', '/');
        
        // Strip base path and slashes to isolate the route segments
        $cleanPath = trim(str_replace($base, '', $uri), '/');
        $parts = explode('/', $cleanPath);
        
        // Get the last segment (e.g. 'sports' or 'clubs')
        $currentSlug = end($parts);
        
        // Fetch from the recursive menu tree built by the worker
        $links = get_site_menu($currentSlug);
		
		// Get the last segment (e.g. 'baseball')
        $currentSlug = end($parts);
        
			// 1. Try to find children of the current page
			$links = get_site_menu($currentSlug);
			
			// 2. FALLBACK: If no children, find the sibling pages (Parent's children)
			if (empty($links) && count($parts) > 1) {
				$parentSlug = $parts[count($parts)-2];
				$links = get_site_menu($parentSlug);
			}
        
        echo "<!-- Link Tiles Debug: Context Identified as [$currentSlug]. Found " . count($links) . " links. -->";

    } elseif (!empty($category)) {
        /**
         * CATEGORY MODE
         * Pulls from the 'Links' tab by Category name.
         */
        $links = get_link_group($category);
    }
}

// 2. EARLY EXIT: Hide component silently if no data exists
if (empty($links)) {
    echo "<!-- Link Tiles Hidden: No links resolved for context. -->";
    return;
}
?>

<div class="container py-4">
	<div class="kgs-link-tiles d-flex flex-wrap justify-content-center g-3 my-4">
		<?php foreach ($links as $link): ?>
			<?php 
				// SAFETY: Skip row if URL is missing
				if (!isset($link['url'])) continue;
				
				$finalUrl = url($link['url']); // Resolves subdirectory pathing
				$label = $link['label'] ?? 'Link';
				
				// Priority 1: Column H manual entry | Priority 2: Google Sheet Keyword seek
				$iconClass = !empty($link['icon']) ? $link['icon'] : get_icon($label);

				// Standardize shorthand: "fa-bus" -> "fa-solid fa-bus"
				if (str_starts_with($iconClass, 'fa-') && !str_contains($iconClass, ' ')) {
					$iconClass = "fa-solid " . $iconClass;
				}
			?>
			<div class="kgs-tile-wrapper">
				<a href="<?= htmlspecialchars($finalUrl) ?>" 
				   class="kgs-tile card h-100 shadow-sm text-decoration-none border-0 transition-transform"
				   <?= (!empty($link['external'])) ? 'target="_blank" rel="noopener"' : '' ?>>
					<div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
						<i class="<?= $iconClass ?> display-5 mb-2 text-primary"></i>
						<span class="fw-bold small text-uppercase text-center text-dark"><?= htmlspecialchars($label) ?></span>
					</div>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>