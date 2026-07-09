<?php
/**
 * navigation.php
 * Fully Automated Navigation - Spreadsheet Driven
 */
$baseUrl  = config('base_url', '/');
$logoUrl  = $baseUrl . 'assets/img/indian_head_img_ID.png';
$currentRoute = trim($route ?? '', '/');

// Get the full menu structure from the cache
$mainMenu = get_site_menu();

function isActive($target, $current) {
    if ($target === 'home') return ($current === '' || $current === 'home') ? 'active' : '';
    return str_starts_with($current, $target) ? 'active' : '';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold fs-4" href="<?= $baseUrl ?>">
            <img src="<?= $logoUrl ?>" alt="Logo" height="60" class="me-2">
            <span class="d-inline site-title-text"><?= htmlspecialchars(config('site_name')) ?></span>
        </a>

        <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
			<i class="fa-solid fa-bars open-icon"></i>
			<i class="fa-solid fa-xmark close-icon"></i>
        </button>

		<div class="collapse navbar-collapse" id="mainNav">
			<ul class="navbar-nav ms-auto">
				<!-- Home remains a standard link -->
				<li class="nav-item">
					<a class="nav-link <?= isActive('home', $currentRoute) ?>" href="<?= $baseUrl ?>">Home</a>
				</li>

				<?php 
				foreach (get_site_menu() as $category): 
					if ($category['show'] !== true) continue; // Respect the spreadsheet toggle
					
					$hasSub = !empty($category['items']);
					$slug = $category['slug'];
				?>
					<li class="nav-item <?= $hasSub ? 'dropdown' : '' ?>">
						<?php if ($hasSub): ?>
							<a class="nav-link dropdown-toggle <?= isActive($slug, $currentRoute) ?>" href="#" data-bs-toggle="dropdown">
								<?= htmlspecialchars($category['label']) ?>
							</a>
							<ul class="dropdown-menu">
								<li><a class="dropdown-item fw-bold" href="<?= $baseUrl . $category['url'] ?>">Overview</a></li>
								<li><hr class="dropdown-divider"></li>
								
								<?php foreach ($category['items'] as $item): ?>
									<?php if ($item['show'] !== true) continue; ?>
									
									<li><a class="dropdown-item" href="<?= $baseUrl . $item['url'] ?>"><?= htmlspecialchars($item['label']) ?></a></li>
									
									<!-- Deep Hierarchy (3rd Level) -->
									<?php if (!empty($item['items'])): ?>
										<?php foreach ($item['items'] as $subItem): ?>
											<?php if ($subItem['show'] !== true) continue; ?>
											<li><a class="dropdown-item ps-4 small text-muted" href="<?= $baseUrl . $subItem['url'] ?>">&raquo; <?= htmlspecialchars($subItem['label']) ?></a></li>
										<?php endforeach; ?>
									<?php endif; ?>

								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<a class="nav-link <?= isActive($slug, $currentRoute) ?>" href="<?= $baseUrl . $category['url'] ?>">
								<?= htmlspecialchars($category['label']) ?>
							</a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
    </div>
</nav>