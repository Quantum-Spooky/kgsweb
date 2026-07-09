<?php
/**
 * UI COMPONENT: Smart Grid (Weighted Single Row)
 * 
 * Features:
 * - Proportional Fill Math: Automatically calculates widths for unassigned slots.
 * - Dynamic Icon Styles: Supports Solid and Simulated Regular (Outline) via get_icon().
 * - Generic Mapping: Uses Registry "Parameter" column to pass IDs to components.
 * - Subdirectory Aware: Uses url() helper for all internal Link Tiles.
 * - Auto-Hide: Vanishes silently if no data is found, allowing grid to re-layout.
 */

// 1. SETUP CONTEXT
$p = $data['prefix'] ?? 'home';
$r = $data['row']    ?? 1;

$baseDir = ROOT_PATH . "kgs-cache/google/";
$wReg = json_decode(@file_get_contents($baseDir . 'widget_registry.json'), true) ?: [];
$wLkp = json_decode(@file_get_contents($baseDir . 'widget_lookup.json'),   true) ?: [];
$tReg = json_decode(@file_get_contents($baseDir . 'tile_registry.json'),   true) ?: [];
$tLkp = json_decode(@file_get_contents($baseDir . 'tile_lookup.json'),     true) ?: [];
$layoutMap = json_decode(@file_get_contents($baseDir . 'layout_map.json'), true) ?: [];

$activeSlots = [];
$totalManualWidth = 0;
$unassignedActiveCount = 0;

// 2. GATHER DATA & ANALYZE ROW
for ($s = 1; $s <= 4; $s++) {
    $configKey  = "{$p}_row_{$r}_slot_{$s}";
    $layout     = $layoutMap[$configKey] ?? null;
    $userChoice = trim($layout['label'] ?? 'None');

    // Skip if empty or explicitly set to 'None'
    if (empty($userChoice) || strtolower($userChoice) === 'none') continue;

    $output = '';
    
    // Resolve Display Title: Widgets Tab Title > Registry Friendly Name
    $displayTitle = (!empty($layout['title'])) ? $layout['title'] : $userChoice;
    $manualWidth  = (int)($layout['width'] ?? 0);

 // --- CHOICE A: IT IS A LINK TILE ---
    if (isset($tLkp[$userChoice])) {
        $key = $tLkp[$userChoice]; 
        $tile = $tReg[$key];
        
        $finalUrl = url($tile['url']);
        // Simplified: ignore style preference, just get solid icon
        $fullIconClass = get_icon($tile['label']);
        
        $output = "
            <a href='".htmlspecialchars($finalUrl)."' class='btn {$tile['color']} w-100 d-flex flex-column align-items-center justify-content-center py-4 shadow-sm h-100'>
                <i class='{$fullIconClass} display-6 mb-2'></i>
                <span class='fw-bold small text-uppercase text-center'>".htmlspecialchars($tile['label'])."</span>
            </a>";
        
    // --- CHOICE B: IT IS A REGISTRY WIDGET ---
    } elseif (isset($wLkp[$userChoice])) {
        $key = $wLkp[$userChoice];
        $w   = $wReg[$key];
        
        // Resolve the Data Source (e.g. convert '@token' to actual ID)
        $source  = $w['source'] ?? '';
        $finalId = str_starts_with($source, '@') ? config(substr($source, 1)) : $source;
        
        // Identify the parameter name (folder_id, doc_id, etc) from Column E of Registry
        $paramName = !empty($w['parameter']) ? $w['parameter'] : 'id';
        
        // Prepare the payload for the component
        $componentData = [
            'title'    => $displayTitle,
            $paramName => $finalId
        ];

        // Check for specific attribute toggles in the config (e.g. widget_staff_show_photos)
        $photoKey = $key . "_show_photos";
        if (config($photoKey)) {
            $componentData['show_photos'] = config($photoKey);
        }

        // Test render to allow component to hide itself silently if data is empty
        ob_start();
        render_component($w['component'], $componentData);
        $renderResult = ob_get_clean();
        
        if (!empty(trim($renderResult))) {
            $output = $renderResult;
        }
    }

    // If the slot successfully produced HTML, add it to our active list
    if (!empty(trim($output))) {
        $activeSlots[$s] = [
            'html'  => $output, 
            'width' => $manualWidth
        ];
        
        // Track widths for Proportional Fill math
        if ($manualWidth > 0) {
            $totalManualWidth += $manualWidth;
        } else {
            $unassignedActiveCount++;
        }
    }
}

// EARLY EXIT: Hide entire row if no content is available
if (empty($activeSlots)) return;

// 3. CALCULATE PROPORTIONS (The Proportional Fill Math)
$remainingSpace = max(0, 12 - $totalManualWidth);
$autoFillWidth  = ($unassignedActiveCount > 0) ? floor($remainingSpace / $unassignedActiveCount) : 0;

// Safety check: ensure auto-fill columns don't become invisibly narrow
if ($autoFillWidth < 3 && $unassignedActiveCount > 0) {
    $autoFillWidth = 6; 
}

// 4. RENDER
?>
<div class="container">
    <div class="kgs-smart-grid-row">
        <div class="row g-4">
            <?php foreach ($activeSlots as $slotNum => $data): 
                // Use manual width if provided (>0), otherwise use the calculated fill
                $w = ($data['width'] > 0) ? $data['width'] : $autoFillWidth;
                
                // Mobile layout: items 25% or smaller stack 2x2 (col-6), larger stack 1x1 (col-12)
                $mobileClass = ($w <= 3) ? 'col-6' : 'col-12';
            ?>
                <div class="<?= $mobileClass ?> col-md-<?= $w ?>">
                    <?= $data['html'] ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>