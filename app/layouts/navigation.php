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
 
$siteName = defined('SITE_NAME') ? SITE_NAME : 'School Site';
$baseUrl  = defined('BASE_URL') ? BASE_URL : '/';

// route comes from Router (fallback safe)
$route = $route ?? '';
$route = trim($route, '/');

// helpers for active states
$isHome       = ($route === '');
$isAbout      = str_starts_with($route, 'about');
$isAcademics  = str_starts_with($route, 'academics');
$isCalendar   = str_starts_with($route, 'calendar');
$isDining     = str_starts_with($route, 'dining');
$isNews       = str_starts_with($route, 'news');
$isActivities = str_starts_with($route, 'activities');
$isFamily     = str_starts_with($route, 'family');
?>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">

        <a class="navbar-brand fw-bold fs-4" href="<?= htmlspecialchars($baseUrl) ?>">
            <?= htmlspecialchars($siteName) ?>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a class="nav-link <?= $isHome ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>">
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $isAbout ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>about/">
                        About
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $isAcademics ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>academics/">
                        Academics
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $isCalendar ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>calendar/">
                        Calendar
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $isDining ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>dining/">
                        Dining
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $isNews ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>news/">
                        News
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $isActivities ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>activities/">
                        Activities
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $isFamily ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>family/">
                        Family
                    </a>
                </li>

            </ul>
        </div>

    </div>
</nav>