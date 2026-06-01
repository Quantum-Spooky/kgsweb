<?php

/**
 * LAYOUT TEMPLATE
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
 
/**
 * header.php
 * Site Header (layout component)
 */

// Safe config fallbacks (prevents hard crashes during migration)
$siteName     = defined('SITE_NAME') ? SITE_NAME : 'School Site';
$districtName = defined('DISTRICT_NAME') ? DISTRICT_NAME : '';

$colorPrimary = defined('COLOR_PRIMARY') ? COLOR_PRIMARY : '#003366';
$colorAccent  = defined('COLOR_ACCENT') ? COLOR_ACCENT : '#FFD700';
$navBg        = defined('NAV_BG') ? NAV_BG : 'https://kellgradeschool.com/wp-content/uploads/2024/11/feathers_3.png';

$address      = defined('ADDRESS') ? ADDRESS : '';
$phone        = defined('PHONE') ? PHONE : '';
$email        = defined('EMAIL') ? EMAIL : '';

$baseUrl      = defined('BASE_URL') ? BASE_URL : '/';

/**
 * CMS META SAFE INPUT
 * (must always be injected by controller, but safe fallback here)
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

    <meta name="description" content="<?= htmlspecialchars($meta['description'] ?? '') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseUrl) ?>assets/css/style.css" rel="stylesheet">

    <style>
        :root {
            --primary: <?= htmlspecialchars($colorPrimary) ?>;
            --accent: <?= htmlspecialchars($colorAccent) ?>;
        }

        .navbar {
            background-image: url('<?= htmlspecialchars($navBg) ?>');
            background-position: left top;
            background-repeat: repeat;
            position: relative;
        }

        .navbar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 35, 102, 0.25);
            z-index: 1;
        }

        .navbar > * {
            position: relative;
            z-index: 2;
        }

        .navbar-brand,
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 600;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.9);
        }

        .navbar-nav .nav-link:hover {
            color: <?= htmlspecialchars($colorAccent) ?> !important;
        }

    </style>
</head>
<body>

<!-- Top Contact Bar -->
<div class="bg-dark text-white py-2 small">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <?= htmlspecialchars($address) ?>
                &nbsp;•&nbsp;
                <?= htmlspecialchars($phone) ?>
                &nbsp;•&nbsp;
                <a href="mailto:<?= htmlspecialchars($email) ?>" class="text-white">
                    <?= htmlspecialchars($email) ?>
                </a>
            </div>

            <div class="col-auto">
                <a href="https://www.teacherease.com/common/login.aspx"
                   target="_blank"
                   class="btn btn-sm top-login-btn">
                    <strong>TeacherEase Login</strong>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . 'app/layouts/navigation.php'; ?>

<main class="page-wrapper">