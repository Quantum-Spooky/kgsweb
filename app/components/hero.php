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

$image    = $image ?? '';
$title    = $title ?? '';
$subtitle = $subtitle ?? '';
$overlay  = $overlay ?? 0;
$text_position = $text_position ?? 'center';
$text_color = $text_color ?? 'light';

/*
|--------------------------------------------------------------------------
| BASE CLASSES
|--------------------------------------------------------------------------
*/

$textAlignClass = match ($text_position) {
    'left' => 'text-start',
    'right' => 'text-end',
    default => 'text-center',
};

$textColorClass = $text_color === 'dark' ? 'text-dark' : 'text-white';

?>

<div class="position-relative hero-component">

    <?php if (!empty($image)): ?>
        <img src="<?= htmlspecialchars($image) ?>"
             class="w-100"
             style="height: 480px; object-fit: cover;"
             alt="">
    <?php endif; ?>

    <?php if ($overlay > 0): ?>
        <div class="position-absolute top-0 start-0 w-100 h-100"
             style="background: rgba(0,0,0,<?= htmlspecialchars((string)$overlay) ?>);">
        </div>
    <?php endif; ?>

    <div class="position-absolute top-50 start-50 translate-middle w-100 px-4 <?= $textAlignClass ?> <?= $textColorClass ?>">

	<?php if (!empty($title)): ?>
		<h1 class="display-4 fw-bold hero-title">
			<?= htmlspecialchars($title) ?>
		</h1>
	<?php endif; ?>

	<?php if (!empty($subtitle)): ?>
		<p class="lead mt-3 hero-subtitle">
			<?= htmlspecialchars($subtitle) ?>
		</p>
	<?php endif; ?>

    </div>

</div>