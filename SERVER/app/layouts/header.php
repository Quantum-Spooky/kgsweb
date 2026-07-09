<?php

/**
 * LAYOUT TEMPLATE
 * app/layouts/header.php
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
 
// Use the unified config() helper for EVERYTHING. 
// This ensures that if you change a value in the Google Sheet, the header updates too.
$siteName     = config('site_name', 'School Site');
$districtName = config('district_name', '');

$colorPrimary = config('color_primary', '#003366');
$colorAccent  = config('color_accent', '#FFD700');
$navBg        = config('nav_bg_image', 'https://kellgradeschool.com/wp-content/uploads/2024/11/feathers_3.png');

$address      = config('address', '');
$phone        = config('phone', '');
$email        = config('email', '');

$baseUrl      = config('base_url', '/');

/**
 * CMS META SAFE INPUT
 */
$meta = is_array($meta ?? null) ? $meta : [];


?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($meta['title'] ?? ($siteName . ' - ' . $districtName)) ?>
    </title>

	<!-- Favicon cached from Google Drive -->
    <link rel="icon" type="image/png" href="<?= config('base_url') ?>assets/img/site_favicon_img_ID.png">

    <meta name="description" content="<?= htmlspecialchars($meta['description'] ?? '') ?>">

	<!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	
	<!-- FontAwesome 7.x Free -->
	<script src="https://kit.fontawesome.com/da8aa1d7c1.js" crossorigin="anonymous"></script>	

    <style>
		:root {
			/* Fetch colors from Google Sheet / Fallback to Kell Blue */
			--primary: <?= config('color_primary', '#015BA7') ?>;
			--secondary: <?= config('color_secondary', '#002366') ?>;
			--accent: <?= config('color_accent', '#87d3f8') ?>;

			/* Convert to RGB for transparency logic */
			--primary-rgb: <?= hexToRgbList(config('color_primary', '#015BA7')) ?>;
			--secondary-rgb: <?= hexToRgbList(config('color_secondary', '#002366')) ?>;
			--accent-rgb: <?= hexToRgbList(config('color_accent', '#87d3f8')) ?>;
		}
	</style>
    
    <!-- Custom Theme Pipeline -->
    <link href="<?= htmlspecialchars($baseUrl) ?>assets/css/base.css?v=<?= KGS_ASSET_VER ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseUrl) ?>assets/css/layout.css?v=<?= KGS_ASSET_VER ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseUrl) ?>assets/css/navigation.css?v=<?= KGS_ASSET_VER ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseUrl) ?>assets/css/hero.css?v=<?= KGS_ASSET_VER ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseUrl) ?>assets/css/footer.css?v=<?= KGS_ASSET_VER ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseUrl) ?>assets/css/style.css?v=<?= KGS_ASSET_VER ?>" rel="stylesheet">

</head>
<body>




<!-- Top Contact Bar -->
<div class="bg-dark text-white py-2 small">
    <div class="container">
        <div class="row align-items-center">
            
            <!-- Left Side -->
            <div class="col">
                <!-- MOBILE ONLY: Simple Contact Link -->
                <a href="<?= config('base_url') ?>contact/" class="text-accent text-decoration-none fw-bold d-md-none">
                    Contact
                </a>

                <!-- DESKTOP ONLY: Full Address & Phone -->
                <div class="d-none d-md-block header-contact-info">
                    <span><?= htmlspecialchars(config('address')) ?></span>
                    &nbsp;•&nbsp;
                    <span><?= htmlspecialchars(config('phone')) ?></span>
                    &nbsp;•&nbsp;
                    <a href="mailto:<?= htmlspecialchars(config('email')) ?>" class="text-white">
                        <?= htmlspecialchars(config('email')) ?>
                    </a>
                </div>
            </div>

            <!-- Right Side (Always Visible) -->
            <div class="col-auto">
                <a href="<?= config('teacherease_url', 'https://www.teacherease.com/common/login.aspx') ?>"
                   target="_blank"
                   class="btn btn-sm top-login-btn p-0">
                    <i class="fa-solid fa-user d-md-none"></i> <!-- Icon only for mobile if space is tight -->
                    <strong>TeacherEase</strong><span class="d-none d-md-inline"> Login</span>
                </a>
            </div>

        </div>
    </div>
</div>

<?php include ROOT_PATH . 'app/layouts/navigation.php'; ?>

<main class="page-wrapper">