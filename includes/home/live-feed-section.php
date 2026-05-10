<?php
/**
 * includes/home/live-feed-section.php
 * 
 * Home Page Live Feed Section
 * 
 * Displays the secretary announcements card.
 */
?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-success text-white fw-bold">Latest Announcements</div>
    <div class="card-body">
        <?php 
        // Correct path using __DIR__
        $live_feed_path = __DIR__ . '/../live-feed.php';
        if (file_exists($live_feed_path)) {
            include $live_feed_path;
        } else {
            echo '<p class="text-muted">Live feed file not found.</p>';
        }
        ?>
    </div>
</div>