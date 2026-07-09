<?php
/**
 * UI COMPONENT: Quick Links
 *
 * Responsibility:
 * - Accept an array of links or pull from a global config key
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

$links = $data['links'] ?? [];

// If no direct links provided, try to fetch by category (used by Dynamic Grid)
if (empty($links) && !empty($category)) {
    $links = get_link_group($category);
}

if (empty($links)) {
    echo "<!-- Quick Links Hidden: $links is empty -->";
    return;
}

$title = $data['title'] ?? '';
?>

<div class="kgs-quick-links-container">
    <?php if (!empty($title)): ?>
        <h5 class="mb-3"><?= htmlspecialchars($title) ?></h5>
    <?php endif; ?>

    <nav class="nav flex-column small">
        <?php foreach ($links as $link): ?>
            <?php 
                $url = $link['url'];
                if (!str_starts_with($url, 'http')) {
                    $url = config('base_url') . ltrim($url, '/');
                }
            ?>
            <a href="<?= htmlspecialchars($url) ?>" 
               class="text-white nav-link p-0 mb-2 d-flex justify-content-between align-items-center" 
               title="<?= htmlspecialchars($link['tooltip'] ?? '') ?>"
               <?= (!empty($link['external'])) ? 'target="_blank" rel="noopener"' : '' ?>>
                <span><?= htmlspecialchars($link['label']) ?></span>
                <i class="fa-solid fa-chevron-right opacity-50"></i>
            </a>
        <?php endforeach; ?>
    </nav>
</div>