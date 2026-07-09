<?php
/**
 * UI COMPONENT: Compliance Table
 * Logic: Pulls a group of links and displays them with their legal citations.
 */

$category = $data['category'] ?? 'Compliance Links';
$links = get_link_group($category);

if (empty($links)) {
    echo "<!-- Compliance Table Hidden: No data  set in config -->";
    return;
}

// Use instance title if provided, otherwise default
$displayTitle = !empty($title) ? $title : '';
?>
<div class="container py-4">
	<div class="kgs-compliance-section my-5">
		<h3 class="rich-text-title">
			<span><?= htmlspecialchars($displayTitle) ?></span>
		</h3>

		<div class="table-responsive shadow-sm rounded">
			<table class="table table-hover align-middle border mb-0">
				<thead class="bg-primary text-white">
					<tr>
						<th class="py-3 ps-4" style="width: 65%;">Required Item / Content</th>
						<th class="py-3">Policy</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($links as $link): ?>
						<tr>
							<td class="ps-4">
								<a href="<?= url($link['url']) ?>" 
								   class="fw-bold text-primary text-decoration-none" 
								   <?= $link['external'] ? 'target="_blank" rel="noopener"' : '' ?>>
									<i class="fa-solid fa-file-circle-check me-2"></i>
									<?= htmlspecialchars($link['label']) ?>
								</a>
							</td>
							<td>
								<code class="bg-light px-2 py-1 rounded text-muted small" style="font-size: 0.8rem;">
									<?= htmlspecialchars($link['tooltip'] ?? 'N/A') ?>
								</code>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>