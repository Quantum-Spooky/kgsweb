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
 
$title = $title ?? '';
$subtitle = $subtitle ?? '';
?>

<div class="container py-4">

    <?php if ($title): ?>
        <h1 class="page-title">
            <?= htmlspecialchars($title) ?>
        </h1>
    <?php endif; ?>

    <?php if ($subtitle): ?>
        <p class="page-subtitle">
            <?= htmlspecialchars($subtitle) ?>
        </p>
    <?php endif; ?>

</div>