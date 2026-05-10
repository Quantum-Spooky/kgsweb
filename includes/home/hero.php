<?php
/**
 * includes/home/hero.php
 * 
 * Home Page Hero Section
 * 
 * Displays the large school building image with overlay headline and subheadline.
 */
?>
<div class="position-relative">
    <img src="<?= HERO_IMAGE ?>" 
         alt="Kell Grade School" 
         class="w-100"
         style="height: 480px; object-fit: cover;">
   
    <div class="position-absolute top-50 start-50 translate-middle text-center text-white w-100 px-4">
        <h1 class="display-3 fw-bold hero-text"><?= HERO_HEADLINE ?></h1>
        <p class="lead fs-3 hero-text"><?= HERO_SUBHEADLINE ?></p>
    </div>
</div>