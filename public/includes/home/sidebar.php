<?php
/**
 * includes/home/sidebar.php
 * 
 * Home Page Sidebar Component
 * 
 * Displays weather widget and quick action links (Report Card + Lunch Menu).
 */
?>
<div class="col-lg-4">
    
    <!-- Weather -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Current Weather - Kell, IL</div>
        <div class="card-body text-center">
            <h2>58°F</h2>
            <p>Partly Cloudy</p>
        </div>
    </div>

    <!-- Quick Links -->
    <a href="<?= REPORT_CARD_URL ?>" target="_blank" class="btn btn-outline-primary btn-lg w-100 mb-3">
        Illinois Report Card
    </a>
    
    <a href="<?= BASE_URL ?>dining/" class="btn btn-outline-success btn-lg w-100 mb-3">
        View Lunch Menu
    </a>
</div>