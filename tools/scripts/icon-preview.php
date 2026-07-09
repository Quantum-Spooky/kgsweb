<?php
/**
 * LOGIC: Icon Library Preview (v3.0 - Solid Only)
 * Path: tools/scripts/icon-preview.php
 * 
 * Features:
 * - Displays the primary Solid/Brand icon for every keyword in your registry.
 * - Automatic Brand detection and labeling.
 * - PHP Code implementation hints for components.
 * - Optimized for FontAwesome 7.
 */

// Fetch the cached map created by your worker
$path = ROOT_PATH . 'kgs-cache/google/icon_map.json';
$icons = file_exists($path) ? json_decode(file_get_contents($path), true) : [];

// Sort alphabetically by keyword
ksort($icons);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KGS Icon Library - Registry View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 7.x Free -->
    <script src="https://kit.fontawesome.com/da8aa1d7c1.js" crossorigin="anonymous"></script>
    <style>
        body { background: #f1f3f5; padding: 40px 0; font-family: system-ui, -apple-system, sans-serif; }
        
        .icon-card { 
            background: #fff; border-radius: 12px; border: 1px solid #dee2e6;
            transition: all 0.2s ease-in-out; height: 100%; overflow: hidden;
            display: flex; flex-direction: column;
        }
        .icon-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-color: #015BA7; }
        
        .icon-display-container { 
            background: #fafbfc; 
            border-bottom: 1px solid #f1f3f5; 
            padding: 35px 0; 
            text-align: center;
        }
        
        .icon-display-container i { 
            font-size: 3.5rem; 
            color: #015BA7; 
            line-height: 1;
        }

        .keyword-label { font-size: 1.15rem; font-weight: 800; color: #212529; }
        .brand-badge { background: #3b5998; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; vertical-align: middle; margin-left: 8px; }
        
        code { font-size: 0.75rem; color: #d63384; background: #fff0f6; padding: 2px 4px; border-radius: 4px; word-break: break-all; }
        .implementation-box { background: #f8f9fa; border-radius: 6px; padding: 12px; margin-top: auto; border: 1px solid #eee; }
    </style>
</head>
<body>
<div class="container">
    <div class="row mb-5 align-items-end">
        <div class="col-md-8">
            <h1 class="fw-bold mb-0">Icon Registry <small class="text-muted fs-5 fw-normal">Solid Library</small></h1>
            <p class="text-muted mb-0">Official icon language for Kell Grade School (FontAwesome 7).</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge bg-primary px-3 py-2"><?= count($icons) ?> Keywords Registered</span>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($icons as $keyword => $baseName): ?>
            <?php 
                $fullClass = get_icon($keyword); 
                $isBrand = str_contains($fullClass, 'fa-brands');
            ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="icon-card shadow-sm">
                    <div class="icon-display-container">
                        <i class="<?= $fullClass ?>"></i>
                    </div>
                    
                    <div class="p-4 d-flex flex-column flex-grow-1">
                        <div class="keyword-label mb-1">
                            <?= htmlspecialchars($keyword) ?>
                            <?php if($isBrand): ?><span class="brand-badge">BRAND</span><?php endif; ?>
                        </div>
                        <div class="text-muted small mb-4">Base Name: <code><?= htmlspecialchars($baseName) ?></code></div>
                        
                        <div class="implementation-box">
                            <small class="text-muted d-block mb-1 fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">PHP Component Usage:</small>
                            <code>get_icon('<?= htmlspecialchars($keyword) ?>')</code>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($icons)): ?>
        <div class="card p-5 text-center shadow-sm border-0">
            <i class="fa-solid fa-cloud-exclamation display-1 text-muted mb-3"></i>
            <h3>Icon map is currently empty</h3>
            <p class="text-muted">Populate the <strong>Icon Map</strong> tab in your Spreadsheet and run the worker.</p>
        </div>
    <?php endif; ?>
</div>

<footer class="container mt-5 text-center text-muted small border-top pt-4">
    <p>KGS Platform Tool &bull; v3.0 (Solid Only)</p>
</footer>
</body>
</html>