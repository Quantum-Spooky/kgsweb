<?php
/**
 * UI COMPONENT
 *
 * Responsibility:
 * - Render HTML for a single component type
 * - Use only provided $data array
 *
 * Rules:
 * - MUST NOT access CMS
 * - MUST NOT access cache
 * - MUST NOT perform routing or data fetching
 *
 * This file is PURE PRESENTATION LAYER.
 */
 
 
/**
 * app/components/sidebar.php
 *
 * Home Page Sidebar Component
 * Displays weather widget and quick action links (Report Card + Lunch Menu).
 */

$weatherLocation = WEATHER_LOCATION ?? 'Kell, IL';
?>

<div class="col-lg-4">

    <!-- Weather -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Current Weather - <?= htmlspecialchars($weatherLocation) ?>
        </div>
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