<?php
/**
 * LAYOUT TEMPLATE
 * home-start.php
 *
 * Responsibility:
 * - Define page structure (header/body/footer slots)
 * - Render passed meta + components output
 *
 * Rules:
 * - MUST NOT load CMS data
 * - MUST NOT query cache
 *
 * Layout only formats already-prepared page data.
 */
?>

<div class="home-layout-wrapper">
    <!-- 
       Note: We do NOT open a .container here. 
       This allows the Hero component to bleed to the edges.
    -->