<?php 
include('includes/header.php'); 
require_once __DIR__ . '/api/class-showdoc-engine.php';
require_once __DIR__ . '/api/class-kgs-helper.php'; 
?>

<div id="site-main">
    <div class="staff-directory-header text-center py-5 bg-light border-bottom mb-5">
        <h2 class="section-title section-title-blue">Faculty & Staff</h2>
    </div>
    <div class="container mb-5">
        <div id="staff-directory-grid" class="row g-4">
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>