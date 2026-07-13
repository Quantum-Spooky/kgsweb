<?php
/**
 * UI COMPONENT: Live Feed
 * Parameters:
 * - limit (int): Total items to show (default 5).
 * - show_view_all (bool): Toggle the header button.
 * - scroll_mode (bool): If true, fixes height and enables internal scroll (best for grid slots).
 * - pagination (bool): If true, adds a "Load More" button.
 */

if (strtoupper(config('show_live_feed')) !== 'TRUE') return;

// 1. Setup Parameters
$sheetId      = $data['sheet_id'] ?? config('live_feed_sheet_id');
$limit        = (int)($data['limit'] ?? 5);
$showViewAll  = $data['show_view_all'] ?? true;
$scrollMode   = $data['scroll_mode'] ?? false;
$pagination   = $data['pagination'] ?? false;

// 2. Load Data
$cachePath = ROOT_PATH . "kgs-cache/google/sheets/feed_" . $sheetId . ".json";
$posts = file_exists($cachePath) ? json_decode(file_get_contents($cachePath), true) : [];

if (empty($posts)) return;

// If not paginating, slice now. If paginating, we'll handle it in JS/CSS.
$displayPosts = ($pagination) ? $posts : array_slice($posts, 0, $limit);
$displayTitle = !empty($title) ? $title : 'Latest News'; 

// 3. Instance unique ID for JS
$instanceId = 'feed-' . substr(md5($sheetId . ($data['row'] ?? '')), 0, 6);
?>

<div class="container py-4">
	<div class="kgs-live-feed mb-5" id="<?= $instanceId ?>">

		<h3 class="rich-text-title">
			<span><i class="fa-solid fa-tower-broadcast me-2 text-success"></i><?= htmlspecialchars($displayTitle) ?></span>
			<?php if ($showViewAll): ?>
				<a href="<?= url('news') ?>" class="btn btn-sm btn-outline-success">View All</a>
			<?php endif; ?>
		</h3>

		<!-- Container Logic: scrollMode adds a fixed height and scrollbar -->
		<div class="kgs-feed-container <?= $scrollMode ? 'kgs-feed-scrollable' : '' ?>" 
			 style="<?= $scrollMode ? 'max-height: 600px; overflow-y: auto; padding-right: 10px;' : '' ?>">
			
			<?php foreach ($displayPosts as $index => $post): ?>
				<?php 
					$hasImage = !empty($post['image_id']); 
					$hiddenClass = ($pagination && $index >= $limit) ? 'd-none kgs-feed-extra' : '';
				?>
				
				<div class="kgs-feed-item card border-0 shadow-sm mb-4 overflow-hidden <?= $hiddenClass ?>">
					<div class="row g-0">
						<?php if ($hasImage): ?>
							<div class="col-md-4">
								<img src="<?= get_drive_url($post['image_id'], 600) ?>" 
									 class="img-fluid h-100" 
									 style="object-fit:cover; min-height: 150px;" 
									 alt="News photo">
							</div>
						<?php endif; ?>
						
						<div class="<?= $hasImage ? 'col-md-8' : 'col-12' ?>">
							<div class="card-body p-3">
								<div class="d-flex justify-content-between align-items-start mb-2">
									<div>
										<span class="badge bg-success text-white me-2" style="font-size: 0.7rem;"><?= htmlspecialchars($post['date']) ?></span>
										<?php if(!empty($post['time'])): ?>
											<span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($post['time']) ?></span>
										<?php endif; ?>
									</div>
									<small class="text-muted italic" style="font-size: 0.7rem;">By <?= htmlspecialchars($post['author']) ?></small>
								</div>
								<div class="rich-text-body" style="font-size: 0.95rem; line-height: 1.5;">
									<?= nl2br(htmlspecialchars($post['text'])) ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ($pagination && count($posts) > $limit): ?>
			<div class="text-center mt-3">
				<button class="btn btn-sm btn-outline-primary kgs-load-more" data-target="<?= $instanceId ?>">
					<i class="fa-solid fa-circle-arrow-down me-1"></i> Load More
				</button>
			</div>
		<?php endif; ?>
	</div>
</div>

<script>
/**
 * Lazy reveal for paginated feeds
 */
if (typeof kgsFeedInit === 'undefined') {
    const kgsFeedInit = true;
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('kgs-load-more')) {
            const container = document.getElementById(e.target.dataset.target);
            const hiddenItems = container.querySelectorAll('.kgs-feed-extra.d-none');
            
            // Reveal next 5
            for (let i = 0; i < 5; i++) {
                if (hiddenItems[i]) {
                    hiddenItems[i].classList.remove('d-none');
                }
            }
            
            // Hide button if no more
            if (container.querySelectorAll('.kgs-feed-extra.d-none').length === 0) {
                e.target.style.display = 'none';
            }
        }
    });
}
</script>
