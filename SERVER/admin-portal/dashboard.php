<?php
require_once dirname(__DIR__, 2) . '/kgs-core/bootstrap.php';
session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; }

$sitemapPath = ROOT_PATH . 'kgs-cache/google/sitemap.json';
$pages = file_exists($sitemapPath) ? json_decode(file_get_contents($sitemapPath), true) : [];
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KGS Admin Portal</title>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Website Pages</h1>
        <a href="<?= config('config_sheet_url') ?>" target="_blank" class="btn btn-outline-primary">Edit in Google Sheets</a>
    </div>

    <div class="list-group shadow-sm">
        <?php foreach ($pages as $route => $data): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                <div>
                    <h5 class="mb-0"><?= htmlspecialchars($data['title']) ?></h5>
                    <small class="text-muted">/<?= htmlspecialchars($route) ?></small>
                </div>
                <span class="badge bg-secondary"><?= htmlspecialchars($data['template']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
	
	<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-primary text-white">
            <h5>Global Update</h5>
            <p class="small">Sync all Google Sheet changes to the site.</p>
            <button onclick="triggerSync()" class="btn btn-light btn-sm w-100">Run Master Sync</button>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-success text-white">
            <h5>New Post</h5>
            <p class="small">Add an announcement to the Facebook Feed.</p>
            <a href="live-feed-post.php" class="btn btn-light btn-sm w-100">Create Post</a>
        </div>
    </div>
</div>


</div>
</body>
</html>