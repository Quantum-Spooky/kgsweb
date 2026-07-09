<?php

require_once dirname(__DIR__, 2) . '/cfg/config.php';
require_once ROOT_PATH . 'kgs-core/cms/DriveCMS.php';

$base = ROOT_PATH . 'kgs-cache/drive/';
$pages = array_filter(glob($base . '*'), 'is_dir');

?>
<!DOCTYPE html>
<html>
<head>
    <title>CMS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <h1 class="mb-4">CMS Pages</h1>

    <div class="list-group">

        <?php foreach ($pages as $pagePath): ?>

            <?php $route = basename($pagePath); ?>

            <a class="list-group-item list-group-item-action"
               href="/tools/admin/page-editor.php?route=<?= urlencode($route) ?>">

                <?= htmlspecialchars($route) ?>

            </a>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>