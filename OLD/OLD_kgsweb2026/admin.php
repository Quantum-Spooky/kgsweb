<?php 
include('includes/header.php'); 

/**
 * PAGE LOGIC
 * Import engines and fetch data here
 */
require_once __DIR__ . '/api/class-showdoc-engine.php';
require_once __DIR__ . '/api/class-kgs-helper.php'; 
?>


    <div id="admin-section" class="section">
        <h2 class="section-title section-title-blue">KGS Admin Control</h2>
        <div class="section-content">
        
			<div class="card">
				<h1>KGS Web Control Room</h1>
				<p>Current Refresh: <?php echo date('m/d/Y H:i A'); ?></p>
				<hr>
				<button onclick="alert('Cache Cleared!')">Clear Website Cache</button>
				<button onclick="location.href='index.html'">View Live Site</button>
			</div>
		
		</div>
    </div>

    <div class="hr-feathers"></div>

    <div id="section-two" class="section">
        <h2 class="section-title section-title-blue">Section Two Heading</h2>
        <div class="section-content">
       </div>
    </div>

<?php include('includes/footer.php'); ?>