<?php
/**
 * index.php
 * 
 * Main Home Page
 * 
 * Loads all modular components for the homepage.
 */

include 'header.php'; 
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

<?php include 'footer.php'; ?>