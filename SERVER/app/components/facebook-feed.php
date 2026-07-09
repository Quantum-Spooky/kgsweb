<?php
/**
 * UI COMPONENT: Facebook Feed
 * app/components/facebook-feed.php
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

$fbUrl = $url ?? config('school_facebook_url');
$title = $title ?? ''; 
if (empty($fbUrl)) {
    echo "<!-- Facebook Hidden: No facebook id set in config -->";
    return;
}
?>

<div class="kgs-widget-container">
    <?php if (!empty($title) && $title !== 'None'): ?>
        <h5 class="rich-text-title">
            <span><?= htmlspecialchars($title) ?></span>
            <a href="<?= config('school_facebook_url') ?>" target="_blank" class="btn btn-sm btn-outline-primary">Visit FB</a>
        </h5>
    <?php endif; ?>
    
	<div class="container feather-bg">
		<div class="fb-container bg-light rounded-15 shadow-sm">
			<iframe
				src="https://www.facebook.com/plugins/page.php?href=<?= urlencode($fbUrl) ?>&tabs=timeline&width=500&height=800&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true"
				width="100%" 
				height="800" 
				style="border:none; overflow:hidden;" 
				scrolling="no" 
				frameborder="0" 
				allowfullscreen="true" 
				allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
			</iframe>
		</div>
    </div>
</div>