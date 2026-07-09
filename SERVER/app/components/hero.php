<?php
/**
 * app/components/hero.php
 * UI COMPONENT
 *
 * Responsibility:
 * - Renders a full-width image banner with optional title, subtitle, and overlay.
 * - Prioritizes locally cached images from Google Drive to reduce API overhead.
 *
 * Rules:
 * - MUST NOT access CMS
 * - MUST NOT access cache
 * - MUST NOT perform routing or data fetching
 *
 * This file is PURE PRESENTATION LAYER.
 */

if (strtoupper(config('show_hero_section')) !== 'TRUE') return;

$showHeroTitle    = (strtoupper(config('show_hero_title')) === 'TRUE');
$showHeroSubtitle = (strtoupper(config('show_hero_subtitle')) === 'TRUE');

$titleText    = $hero_title ?? ''; 
$subtitleText = $hero_subtitle ?? '';
$overlay      = (float)($overlay ?? 0);
$rawImage     = $image ?? '';

// Control how the image behaves on mobile ('crop' or 'full')
$mobileView   = $mobile_view ?? 'crop'; 

// Image Resolution
$localHeroFile = "assets/img/hero_image.png";
$localHeroPath = ROOT_PATH . "public/" . $localHeroFile;
$finalImage    = '';

if (!empty($rawImage) && filter_var($rawImage, FILTER_VALIDATE_URL)) {
    $finalImage = $rawImage;
} elseif (file_exists($localHeroPath)) {
    $finalImage = config('base_url') . $localHeroFile;
} else {
    $finalImage = $rawImage;
}

if (empty($finalImage)) return;

$textAlignClass = match ($text_position ?? 'center') {
    'left'  => 'text-start',
    'right' => 'text-end',
    default => 'text-center',
};
$textColorClass = ($text_color ?? 'light') === 'dark' ? 'text-dark' : 'text-white';
?>

<div class="position-relative hero-component shadow-sm hero-mobile-<?= $mobileView ?>">
    
    <?php if (!empty($finalImage)): ?>
        <img src="<?= htmlspecialchars($finalImage) ?>" class="w-100" alt="">
    <?php endif; ?>

    <?php if ($overlay > 0): ?>
        <div class="position-absolute top-0 start-0 w-100 h-100 d-none d-md-block" style="background: rgba(0,0,0,<?= $overlay ?>);"></div>
    <?php endif; ?>

    <div class="position-absolute top-50 start-50 translate-middle w-100 px-4 <?= $textAlignClass ?> <?= $textColorClass ?>">
        <?php if ($showHeroTitle && !empty($titleText)): ?>
            <h1 class="hero-title text-shadow"><?= htmlspecialchars($titleText) ?></h1>
        <?php endif; ?>

        <?php if ($showHeroSubtitle && !empty($subtitleText)): ?>
            <p class="hero-subtitle text-shadow"><?= htmlspecialchars($subtitleText) ?></p>
        <?php endif; ?>
    </div>
</div>