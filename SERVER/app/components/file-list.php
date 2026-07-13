<?php 
/**
 * UI COMPONENT: File List
 * Features: Master Cache Seeker, Context-Aware Naming, UI State Controls.
 * Icons: FontAwesome 7 Solid (Animated Folders)
 */

// 1. Extract Config
$folderId     = $data['folder_id'] ?? null;
$title        = $data['title'] ?? 'Documents';
$showSearch   = $data['show_search'] ?? true;
$showToggle   = $data['show_toggle'] ?? true;
$initialDepth = $data['initial_depth'] ?? 0;
$initialState = $data['initial_state'] ?? 'collapsed';

$instanceHash = substr(md5($folderId ?? uniqid()), 0, 8);
$treeId       = 'kgs-tree-' . $instanceHash;
$searchId     = 'kgs-search-' . $instanceHash;

if (!$folderId || str_starts_with($folderId, '@')) {
    echo "<!-- File List Error: ID not resolved for token $folderId -->";
    return;
}

/*
|--------------------------------------------------------------------------
| SMART SEEKER CACHE LOGIC
|--------------------------------------------------------------------------
*/
$masterRootId = config('master_root_folder_id', '1o0X89kQL_cXEUpPOSZXcCUFz8bzCtmAp');
$masterTree = GoogleDriveCache::get('drive-trees', 'tree_' . $masterRootId, 0);
$tree = null;

if (!function_exists('kgs_find_folder_in_tree')) {
    function kgs_find_folder_in_tree($items, string $targetId) {
        if (!is_array($items)) return null;
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['id'])) continue;
            if ($item['id'] === $targetId) return $item['children'] ?? [];
            if (!empty($item['children'])) {
                $found = kgs_find_folder_in_tree($item['children'], $targetId);
                if ($found !== null) return $found;
            }
        }
        return null;
    }
}

if ($masterTree) {
    $cleanMasterTree = $masterTree['items'] ?? (is_array($masterTree) ? $masterTree : []);
    if ($folderId === $masterRootId) {
        $tree = $cleanMasterTree;
    } else {
        $tree = kgs_find_folder_in_tree($cleanMasterTree, $folderId);
    }
}

if ($tree === null) {
    $tree = GoogleDriveCache::get('drive-trees', 'tree_' . $folderId, 0);
}

if ($tree === null || !is_array($tree) || empty($tree)) {
    echo "<!-- File List Hidden: Folder $folderId is empty or not found in cache -->";
    return; 
}

/*
|--------------------------------------------------------------------------
| RENDERER & NAMING ENGINE FUNCTIONS
|--------------------------------------------------------------------------
*/

if (!function_exists('kgs_fl_filter')) {
    function kgs_fl_filter(array $items): array {
        $out = [];
        foreach ($items as $item) {
            if ($item['type'] === 'file') { $out[] = $item; continue; }
            $filtered = kgs_fl_filter($item['children'] ?? []);
            if (!empty($filtered)) { $item['children'] = $filtered; $out[] = $item; }
        }
        return $out;
    }

    function kgs_fl_format_folder(string $name): string {
        if (!str_contains($name, '-') && !str_contains($name, '_')) return $name;
        return ucwords(strtolower(trim(preg_replace('/[-_]+/', ' ', $name))));
    }

    function kgs_fl_sort(array $items): array {
        usort($items, function ($a, $b) {
            $af = ($a['type'] === 'folder'); $bf = ($b['type'] === 'folder');
            if ($af !== $bf) return $af ? -1 : 1;
            // Uses global date extractor from bootstrap.php
            $da = kgs_fl_extract_sort_date($a['name'] ?? ''); 
            $db = kgs_fl_extract_sort_date($b['name'] ?? '');
            if ($da && $db) return strcmp($da, $db);
            if ($da) return -1; if ($db) return 1;
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        });
        foreach ($items as &$item) { if ($item['type'] === 'folder' && !empty($item['children'])) $item['children'] = kgs_fl_sort($item['children']); }
        return $items;
    }

    function kgs_fl_display_name(string $filename, string $parentName = ''): string {
        $base = preg_replace('/(\.[a-zA-Z0-9]{1,5})+$/', '', $filename);
        $dateLabel = '';
        // Uses global date extractor from bootstrap.php
        $rawDate = kgs_fl_extract_sort_date($filename);
        if ($rawDate) {
            $dt = DateTime::createFromFormat('Ymd', $rawDate);
            $dateLabel = $dt ? $dt->format('F j, Y') : '';
            // If date is only YYYYMM00 (Month only)
            if (str_ends_with($rawDate, '00')) { $dateLabel = $dt ? $dt->format('F Y') : ''; }
        }

        $isSpecial = stripos($base, 'special') !== false;
        $isAgenda  = stripos($base, 'agenda') !== false;
        $isMinutes = stripos($base, 'minutes') !== false;
        $isMeeting = ($isAgenda || $isMinutes);

        $cleanRemaining = trim(preg_replace(['/school board/i', '/meeting/i', '/agenda/i', '/minutes/i', '/special/i', '/\b\d{4}\b/', '/\b\d{1,2}\b/'], '', $base));
        $cleanRemaining = trim(preg_replace('/[()_\-]+/', ' ', $cleanRemaining));
        $note = (!empty($cleanRemaining)) ? " (" . ucwords(strtolower($cleanRemaining)) . ")" : "";

        if ($isMeeting) {
            $type = $isAgenda ? 'Meeting Agenda' : 'Meeting Minutes';
            if ($isSpecial) $type = 'Special ' . $type;
            return $dateLabel ? "$dateLabel - $type$note" : "$type$note";
        }

        $title = ucwords(strtolower($cleanRemaining ?: $base));
        return $dateLabel ? "$title ($dateLabel)" : $title;
    }

    function kgs_fl_icon(string $mimeType, string $name): string {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $cls = "fa-file"; $color = "kgs-fl-icon--generic";
        if ($mimeType === 'application/pdf' || $ext === 'pdf') { $cls = "fa-file-pdf"; $color = "kgs-fl-icon--pdf"; }
        elseif (str_contains($mimeType, 'word') || in_array($ext, ['doc', 'docx'])){ $cls = "fa-file-word"; $color = "kgs-fl-icon--doc"; }
        elseif (str_contains($mimeType, 'sheet') || in_array($ext, ['xls', 'xlsx'])){ $cls = "fa-file-excel"; $color = "kgs-fl-icon--xls"; }
        return "<i class=\"fa-solid $cls kgs-fl-icon $color\" aria-hidden=\"true\"></i>";
    }

    function kgs_fl_render(array $items, int $depth = 0, string $parentName = '', int $maxInitDepth = 0, string $initState = 'collapsed'): void {
        foreach ($items as $item) {
            $nodeId = preg_replace('/[^a-zA-Z0-9]/', '-', $item['id']);
            $collapseId = 'kgsc-' . $nodeId;

            if ($item['type'] === 'folder') {
                $label = htmlspecialchars(kgs_fl_format_folder($item['name']));
                $isOpen = ($initState === 'expanded' && $depth < $maxInitDepth);
                $btnClass = $isOpen ? '' : 'collapsed';
                $bodyClass = $isOpen ? 'show' : '';

                echo '<div class="kgs-fl-folder" data-depth="'.$depth.'">
                <button type="button" class="kgs-fl-folder__toggle '.$btnClass.'" data-bs-toggle="collapse" data-bs-target="#'.$collapseId.'">
                    <i class="fa-solid fa-chevron-right kgs-fl-caret"></i>
                    <!-- Dual Icons for CSS animation -->
                    <i class="fa-solid fa-folder kgs-fl-folder-icon kgs-fl-folder-icon--closed"></i>
                    <i class="fa-solid fa-folder-open kgs-fl-folder-icon kgs-fl-folder-icon--open"></i>
                    '.$label.'
                </button>
                <div class="collapse kgs-fl-folder__body '.$bodyClass.'" id="'.$collapseId.'">
                <div class="kgs-fl-folder__children">';
                kgs_fl_render($item['children'] ?? [], $depth + 1, $item['name'], $maxInitDepth, $initState);
                echo '</div></div></div>';
            } else {
                $displayName = kgs_fl_display_name($item['name'], $parentName);
                $link = htmlspecialchars($item['webViewLink'] ?? '#');
                echo '<div class="kgs-fl-file" data-depth="'.$depth.'" data-search-raw="'.htmlspecialchars(strtolower($item['name'])).'" data-search-name="'.htmlspecialchars(strtolower($displayName)).'">
                <a href="'.$link.'" target="_blank" class="kgs-fl-file__link">'.kgs_fl_icon($item['mimeType'] ?? '', $item['name']).'
                <span class="kgs-fl-file__name">'.htmlspecialchars($displayName).'</span></a>
                </div>';
            }
        }
    }
}

// 2. Perform Processing
$tree = kgs_fl_filter($tree);
$tree = kgs_fl_sort($tree);
?>

<div class="container py-4">
	<section class="kgs-file-list" id="section-<?= $treeId ?>">
		<!-- Standardized Heading (matching base.css) -->
		<?php if ($title): ?>
			<h3 class="rich-text-title"><span><?= htmlspecialchars($title) ?></span></h3>
		<?php endif; ?>

		<div class="container">
			<?php if ($showToggle || $showSearch): ?>
				<div class="kgs-file-list__toolbar mb-3">
					<?php if ($showToggle): ?>
						<button class="btn btn-outline-primary btn-sm kgs-toggle-all">Expand All</button>
					<?php endif; ?>
					
					<?php if ($showSearch): ?>
						<input class="form-control form-control-sm kgs-search-box <?php if ($showToggle) echo 'ms-auto'; ?>" 
							   id="<?= $searchId ?>" placeholder="Search documents..." type="search">
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="kgs-file-list__tree" id="<?= $treeId ?>">
				<?php kgs_fl_render($tree, 0, '', $initialDepth, $initialState); ?>
			</div>
		</div>
	</section>
</div>

<script>
(function () {
    const treeId = <?= json_encode($treeId) ?>;
    const searchId = <?= json_encode($searchId) ?>;
    const section = document.getElementById('section-' + treeId);
    const tree = document.getElementById(treeId);
    const toggleBtn = section.querySelector('.kgs-toggle-all');
    const searchInput = document.getElementById(searchId);

    const getBsCollapse = (el) => window.bootstrap?.Collapse?.getOrCreateInstance(el, { toggle: false });

    const updateButtonText = () => {
        if (!toggleBtn) return;
        const isAnyCollapsed = Array.from(tree.querySelectorAll('.kgs-fl-folder__toggle'))
                                    .some(btn => btn.classList.contains('collapsed'));
        toggleBtn.innerText = isAnyCollapsed ? 'Expand All' : 'Collapse All';
    };

    if (toggleBtn) {
        updateButtonText();
        toggleBtn.onclick = function() {
            const isAnyCollapsed = Array.from(tree.querySelectorAll('.kgs-fl-folder__toggle'))
                                        .some(btn => btn.classList.contains('collapsed'));
            tree.querySelectorAll('.kgs-fl-folder__body').forEach(el => {
                isAnyCollapsed ? getBsCollapse(el).show() : getBsCollapse(el).hide();
            });
        };
        tree.addEventListener('shown.bs.collapse', updateButtonText);
        tree.addEventListener('hidden.bs.collapse', updateButtonText);
    }

    if (searchInput) {
        searchInput.oninput = function() {
            const term = this.value.toLowerCase().trim();
            tree.querySelectorAll('.kgs-fl-file').forEach(file => {
                const match = file.dataset.searchName.includes(term) || file.dataset.searchRaw.includes(term);
                file.classList.toggle('kgs-fl-hidden', term && !match);
            });
            const folders = Array.from(tree.querySelectorAll('.kgs-fl-folder')).sort((a,b) => b.dataset.depth - a.dataset.depth);
            folders.forEach(f => {
                const hasVisible = !!f.querySelector('.kgs-fl-file:not(.kgs-fl-hidden)');
                f.classList.toggle('kgs-fl-hidden', term && !hasVisible);
                if (term && hasVisible) getBsCollapse(f.querySelector('.kgs-fl-folder__body'))?.show();
            });
        };
    }
})();
</script>