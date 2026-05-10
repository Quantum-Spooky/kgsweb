<?php 
include('includes/header.php'); 

/**
 * PAGE LOGIC
 * Import engines and fetch data here
 */
require_once __DIR__ . '/api/class-showdoc-engine.php';
require_once __DIR__ . '/api/class-kgs-helper.php'; 
?>


    <div id="section-one" class="section">
        <h2 class="section-title section-title-blue">Section One Heading</h2>
        <div class="section-content"></div>
    </div>

    <div class="hr-feathers"></div>

    <div id="section-two" class="section">
        <h2 class="section-title section-title-blue">Section Two Heading</h2>
        <div class="section-content"></div>
    </div>

<?php include('includes/footer.php'); ?>