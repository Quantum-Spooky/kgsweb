<?php
/**
 * UI COMPONENT: People List (Aligned Fluid Row)
 */
$type = $data['type'] ?? 'staff';
$showPhotos = (isset($data['show_photos']) && ($data['show_photos'] === true || $data['show_photos'] === 'true'));
$cachePath = ROOT_PATH . "kgs-cache/google/people_{$type}.json";
$people = file_exists($cachePath) ? json_decode(file_get_contents($cachePath), true) : [];

if (empty($people)) {
    echo "<!-- People List Hidden: No people list data set in google sheets -->";
    return;
}

usort($people, function($a, $b) { return ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100); });

$groups = [];
foreach ($people as $p) { $groups[$p['category'] ?: 'General'][] = $p; }
$baseUrl = config('base_url');
?>

<div class="container py-4">
	<div class="kgs-people-directory-fluid" style="margin-top: 0;">
		<?php foreach ($groups as $groupName => $members): ?>
			<h3 class="rich-text-title">
				<span><?= htmlspecialchars($groupName) ?></span>
			</h3>
			
			<div class="row g-2"> <!-- Tighter gap for a cleaner list look -->
				<?php foreach ($members as $p): ?>
					<?php if (strpos(trim($p['name']), ' ') === false) continue; ?>
					
					<div class="col-12 mb-2">
						<div class="card border-0 shadow-sm kgs-people-card">
							<div class="card-body p-2 d-flex align-items-center">
								
								<?php if ($showPhotos): ?>
									<div class="people-card-thumb me-2 text-center">
										<img src="<?= !empty($p['image_id']) ? get_drive_url($p['image_id'], 150) : $baseUrl.'assets/img/staff_photo_placeholder_img_id.png' ?>" 
											 class="rounded-circle shadow-sm" alt="">
									</div>
								<?php endif; ?>

								<!-- The Aligned Row -->
								<div class="kgs-person-fluid-row px-2">
									
									<div class="kgs-person-col kgs-person-col--name fw-bold text-primary">
										<?= htmlspecialchars($p['name']) ?>
									</div>
									
									<div class="kgs-person-col kgs-person-col--title text-muted">
										<i class="fa-solid fa-id-badge me-1 small opacity-50"></i>
										<?= htmlspecialchars($p['title']) ?>
									</div>
									
									<?php if(!empty($p['email'])): ?>
										<div class="kgs-person-col kgs-person-col--email">
											<a href="mailto:<?= $p['email'] ?>" class="text-decoration-none small">
												<i class="fa-solid fa-envelope me-1 opacity-50"></i>
												<?= htmlspecialchars($p['email']) ?>
											</a>
										</div>
									<?php endif; ?>

								</div>

							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>