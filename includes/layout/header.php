<?php
/**
 * header.php
 * 
 * Site Header - Loads config, Bootstrap, and critical styles.
 */


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - <?= DISTRICT_NAME ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: <?= COLOR_PRIMARY ?>;
            --secondary: <?= COLOR_SECONDARY ?>;
            --accent: <?= COLOR_ACCENT ?>;
        }

        .navbar {
            background-image: url('<?= NAV_BG ?>') !important;
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

        .navbar-brand {
            color: white !important;
            text-shadow: 2px 2px 6px rgba(0,0,0,0.8);
        }
        
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 600;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.9);
        }
        
        .navbar-nav .nav-link:hover {
            color: <?= COLOR_ACCENT ?> !important;
        }

        .hero-text {
            text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.85);
        }
    </style>
</head>
<body>

    <!-- Top Contact Bar -->
    <div class="bg-dark text-white py-2 small">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <?= ADDRESS ?> &nbsp;•&nbsp; <?= PHONE ?> &nbsp;•&nbsp; 
                    <a href="mailto:<?= EMAIL ?>" class="text-white"><?= EMAIL ?></a>
                </div>
                <div class="col-auto">
                    <a href="https://www.teacherease.com/common/login.aspx" target="_blank" 
                       class="btn btn-sm top-login-btn">
                        <strong>TeacherEase Login</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/navigation.php'; ?>