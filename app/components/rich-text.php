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
 * Rich Text Component
 */

$title   = $title ?? '';
$content = $content ?? '';
$align   = $align ?? 'left';
?>

<div class="container">

    <div class="rich-text text-<?= htmlspecialchars($align) ?>">

        <?php if (!empty($title)): ?>
            <h2 class="rich-text-title">
                <?= htmlspecialchars($title) ?>
            </h2>
        <?php endif; ?>

        <div class="rich-text-body">
            <?= $content ?>
        </div>

    </div>

</div>