<?php 
/* cafeteria.php */

include('includes/header.php'); 

// 1. Engines & Helpers
require_once __DIR__ . '/api/class-showdoc-engine.php';
require_once __DIR__ . '/api/class-kgs-helper.php'; 

// 2. Fetch Data
$breakfast = ShowDoc::get_latest_from_folder('breakfast_menu');
$lunch     = ShowDoc::get_latest_from_folder('lunch_menu');
?>

	<div class="cafeteria-menu-container display-container">

		<div id="lunch-menu-container" class="cafeteria-menu-container-section section">
			<h2 class="section-title section-title-blue">Breakfast Menu</h2>
			<div class="section-content">
				<div id="breakfast-menu" class="document-viewer">
					<div class="viewer-header">
						<h3>Breakfast Menu</h3>
						<?php if($breakfast): ?>
							<span class="badge">Updated: <?php echo $breakfast['updated']; ?></span>
						<?php endif; ?>
					</div>
					<div class="img-wrapper">
						<?php if($breakfast): ?>
							<img src="<?php echo $breakfast['url']; ?>" class="zoomable-menu" alt="Breakfast Menu" title="Click to zoom">
						<?php else: ?>
							<div class="placeholder-box">Breakfast menu is currently being updated.</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="hr-feathers"></div>
				
		<div id="breakfast-menu-container" class="cafeteria-menu-container-section section">
			<h2 class="section-title section-title-blue">Lunch Menu</h2>
			<div class="section-content">
				<div id="lunch-menu" class="document-viewer">
					<div class="viewer-header">
						<h3>Lunch Menu</h3>
						<?php if($lunch): ?>
							<span class="badge">Updated: <?php echo $lunch['updated']; ?></span>
						<?php endif; ?>
					</div>
					<div class="img-wrapper">
						<?php if($lunch): ?>
							<img src="<?php echo $lunch['url']; ?>" class="zoomable-menu" alt="Lunch Menu" title="Click to zoom">
						<?php else: ?>
							<div class="placeholder-box">Lunch menu is currently being updated.</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

	</div>

<?php include('includes/footer.php'); ?>