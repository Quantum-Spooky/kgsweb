<?php
// includes/live-feed.php

// Fallback message if no posts
if (empty($live_posts)) {
    echo '<p class="text-muted">No announcements at this time.</p>';
    return;
}
?>

<div class="announcements">
    <?php foreach ($live_posts as $post): ?>
        <div class="d-flex mb-3">
            <div class="me-3 text-muted small" style="min-width: 85px;">
                <?= htmlspecialchars($post['date'] ?? 'Recent') ?>
            </div>
            <div>
                <?= nl2br(htmlspecialchars($post['text'])) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Optional: "View All Announcements" link -->
<div class="mt-3">
    <a href="<?= BASE_URL ?>announcements/" class="text-success fw-bold text-decoration-none">
        → View All Announcements
    </a>
</div>