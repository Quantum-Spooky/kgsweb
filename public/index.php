<?php
/**
 * public/index.php
 * 
 * Main Home Page
 * 
 * Loads all modular components for the homepage.
 */

require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';
view('layout/header'); 
?>

<!-- Hero -->
<?php include 'includes/home/hero.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <?php include 'includes/home/live-feed-section.php'; ?>
            <?php include 'includes/home/widgets-section.php'; ?>
        </div>

        <?php include 'includes/home/sidebar.php'; ?>
    </div>
</div>

<?php view('layout/footer'); ?>