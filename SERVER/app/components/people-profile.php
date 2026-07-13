<?php
/**
 * UI COMPONENT: People Profile
 * Parameter: 'role_id' (e.g., 'coach_basketball')
 */

$roleId = $data['role_id'] ?? null;
$person = get_featured_profile($roleId);

if (!$person) {
    echo "<!-- Profile Hidden: Role '$roleId' not resolved -->";
    return;
}
?>

<div class="container py-4">
	<div class="kgs-people-profile-widget mb-4">
		<div class="card border-0 shadow-sm overflow-hidden" style="max-width: 400px; margin: auto;">
			<div class="card-body p-3">
				<div class="d-flex align-items-center">
					
					<div class="profile-image me-3">
						<?php if (!empty($person['image_id'])): ?>
							<img src="<?= get_drive_url($person['image_id'], 200) ?>" 
								 alt="<?= htmlspecialchars($person['name']) ?>" 
								 class="rounded-circle shadow-sm"
								 style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #fff;">
						<?php else: ?>
							<img src="<?= $baseUrl ?>assets/img/staff_photo_placeholder_img_id.png" 
								 class="rounded-circle opacity-25" 
								 style="width: 80px; height: 80px; object-fit: cover;">
						<?php endif; ?>
					</div>

					<div class="profile-info text-start">
						<h6 class="fw-bold mb-0 text-primary"><?= htmlspecialchars($person['name']) ?></h6>
						<p class="small text-muted mb-2"><?= htmlspecialchars($person['title']) ?></p>
						
						<a href="mailto:<?= htmlspecialchars($person['email']) ?>" 
						   class="btn btn-sm btn-outline-primary py-0" 
						   style="font-size: 0.7rem;">
							<i class="fa-solid fa-envelope me-1"></i> Email
						</a>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>