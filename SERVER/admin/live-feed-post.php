#!/usr/bin/env php
<?php
/* admin/live-feed-post.php*/
require_once dirname(__DIR__) . '/kgs-core/bootstrap.php';
session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>KGS Live Feed Admin</title>
    <style>
        body { background: #f4f7f6; padding: 20px; }
        .post-card { max-width: 500px; margin: auto; background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="post-card p-4">
        <h4 class="mb-4">Post to Live Feed</h4>
        <p class="small text-muted">Posting as: <strong><?= $_SESSION['user_name'] ?></strong></p>
        
        <form action="upload-handler.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold">Update Text</label>
                <textarea name="content" class="form-control" rows="4" placeholder="What's happening at school?" required></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Attach Photo (Optional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Publish Update</button>
        </form>
    </div>
</body>
</html>