#!/usr/bin/env php
<?php
/**
 * MASTER CACHE REFRESH WORKER (Ultimate Integrated Version)
 * 
 * Logic Sequence:
 * 1. SYNC Config/Layout (Tokens & Widths)
 * 2. INDEX Recursive Drive Tree (Prerequisite for link resolution)
 * 3. SYNC Link Groups (Smart @Token Resolution + File Filtering + Icon Style)
 * 4. SYNC Widget Registry (Parameter Column E)
 * 5. SYNC Link Tile Suite (Icons + Style + Colors)
 * 6. SYNC People Directory (Title/First/Last + Context sieve)
 * 7. SYNC URL Aliases (Redirects + Invalidation)
 * 8. SYNC Live Feed (6-column format)
 * 9. EXPORT Docs to HTML & DOWNLOAD Image Assets
 * 10. SYNC Navigation (Recursive Tree + Icon Style)
 * 11. SYNC Icon Map (Keyword-to-Icon Registry)
 */

define('ROOT_PATH', dirname(__DIR__, 2) . '/');
require_once ROOT_PATH . 'kgs-core/bootstrap.php';

// --- RESOLVE MASTER FOLDER ---
$folderId = $argv[1] ?? config('master_root_folder_id') ?? '1o0X89kQL_cXEUpPOSZXcCUFz8bzCtmAp';

// --- PATH CONFIGURATION ---
$baseDir    = ROOT_PATH . "kgs-cache/google/";
$treePath   = $baseDir . "drive-trees/";
$htmlPath   = $baseDir . "html-content/";
$sheetPath  = $baseDir . "sheets/";
$imgDirPath = ROOT_PATH . "public/assets/img/";
$lockFile   = ROOT_PATH . 'kgs-cache/locks/drive-refresh.lock';

// Master JSON Mapping Paths
$confPath   = $baseDir . "config_map.json";
$layoutPath = $baseDir . "layout_map.json";
$linksPath  = $baseDir . "links_map.json";
$aliasPath  = $baseDir . "aliases_map.json";
$wRegPath   = $baseDir . "widget_registry.json";
$wLkpPath   = $baseDir . "widget_lookup.json";
$tRegPath   = $baseDir . "tile_registry.json";
$tLkpPath   = $baseDir . "tile_lookup.json";

// Ensure physical directories exist
$dirs = [$treePath, $htmlPath, $sheetPath, $imgDirPath, dirname($lockFile)];
foreach ($dirs as $dir) { if (!is_dir($dir)) mkdir($dir, 0755, true); }

// --- ROBUST LOCK HANDLING ---
if (file_exists($lockFile)) {
    $lockAge = time() - filemtime($lockFile);
    if ($lockAge < 600) { exit("[" . date('c') . "] SKIP: Already running (Lock age: {$lockAge}s).\n"); }
    echo "[" . date('c') . "] WARN: Stale lock detected. Overwriting...\n";
}
touch($lockFile);

/**
 * Seeker Helper for Token Resolution
 */
if (!function_exists('kgs_find_folder_in_tree')) {
    function kgs_find_folder_in_tree($items, string $targetId) {
        if (!is_array($items)) return null;
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (($item['id'] ?? '') === $targetId) return $item['children'] ?? [];
            if (!empty($item['children'])) {
                $found = kgs_find_folder_in_tree($item['children'], $targetId);
                if ($found !== null) return $found;
            }
        }
        return null;
    }
}

echo "[" . date('c') . "] START: Master Refresh Sequence [{$folderId}]\n";
$startTime = microtime(true);
$success   = false;

try {
    $sheetId = config('config_sheet_id');
    $service = GoogleService::sheets();

	/*
	|--------------------------------------------------------------------------
	| TASK 1: SYNC CONFIG & GRID WIDTHS
	|--------------------------------------------------------------------------
	*/
		echo " - Task 1: Config & Layout... ";
		$configMap = []; $layoutMap = []; 
		$tabs = [
			['range' => 'Google IDs!A:B',    'type' => 'config'],
			['range' => 'Site Settings!A:B', 'type' => 'config'],
			['range' => 'Links!A:B',         'type' => 'config'],
			['range' => 'Widgets!A:F',       'type' => 'layout'] 
		];
		foreach ($tabs as $tab) {
			try {
				$response = $service->spreadsheets_values->get($sheetId, $tab['range'])->getValues();
				if ($response) foreach ($response as $row) {
					$k = trim($row[0]??''); $v = trim($row[1]??'');
					if (!$k || str_starts_with($k, '-') || $k === 'Key') continue;
					$configMap[$k] = $v;
					if ($tab['type'] === 'layout') {
						$layoutMap[$k] = [ 
							'label' => $v, 
							'title' => trim($row[2] ?? ''),
							'width' => (int)($row[5] ?? 0)
						];
					}
				}
			} catch (Exception $e) {}
		}
		file_put_contents($confPath, json_encode($configMap, JSON_PRETTY_PRINT));
		file_put_contents($layoutPath, json_encode($layoutMap, JSON_PRETTY_PRINT));
		echo "DONE\n";

	/*
	|--------------------------------------------------------------------------
	| TASK 2: INDEX MASTER DRIVE (Required for Link Resolution)
	|--------------------------------------------------------------------------
	*/
		echo " - Task 2: Indexing Drive... ";
		$masterTree = GoogleDriveManager::listFolder($folderId, true);
		if ($masterTree) {
			GoogleDriveCache::set('drive-trees', 'tree_' . $folderId, $masterTree, ['folder_id' => $folderId]);
			echo "DONE\n";
		}

	/*
	|--------------------------------------------------------------------------
	| TASK 3: SYNC LINKS & RESOLVE @TOKENS
	|--------------------------------------------------------------------------
	*/
		echo " - Task 3: Links & Tokens... ";
		// Increased range to A:I to capture File Filter
		$linkResponse = $service->spreadsheets_values->get($sheetId, 'Links!A:I')->getValues();
		$linksMap = [];
		if ($linkResponse) {
			array_shift($linkResponse);
			foreach ($linkResponse as $row) {
				$k=trim($row[0]??''); $u=trim($row[1]??''); $l=trim($row[2]??''); $c=trim($row[3]??''); 
				$t=trim($row[4]??''); $f=trim($row[6]??''); $icon=trim($row[7]??''); 

				if (!$k || $c === 'HEADER' || !$u || !$l) continue;

				if (str_starts_with($u, '@')) {
					$targetFolderId = $configMap[substr($u, 1)] ?? null;
					if ($targetFolderId) {
						$folderContents = kgs_find_folder_in_tree($masterTree, $targetFolderId);
						if (!empty($folderContents)) {
							if (!empty($f)) {
								$folderContents = array_filter($folderContents, function($file) use ($f) {
									return str_contains(strtolower($file['name']), strtolower($f));
								});
							}
							if (!empty($folderContents)) {
								usort($folderContents, function($a, $b) {
									return strcmp(kgs_fl_extract_sort_date($b['name']), kgs_fl_extract_sort_date($a['name']));
								});
								$u = $folderContents[0]['webViewLink'];
							}
						}
					}
				}
				$linksMap[$c][] = [
					'label'=>$l, 'url'=>$u, 'tooltip'=>$t, 'icon'=>$icon, 
					'external'=>(strtoupper(trim($row[5]??''))==='TRUE' || str_starts_with($u, 'http'))
				];
			}
			file_put_contents($linksPath, json_encode($linksMap, JSON_PRETTY_PRINT));
			echo "DONE\n";
		}

	/*
	|--------------------------------------------------------------------------
	| TASK 4: SYNC WIDGET REGISTRY
	|--------------------------------------------------------------------------
	*/
		echo " - Task 4: Widget Registry... ";
		try {
			$response = $service->spreadsheets_values->get($sheetId, 'Widget Registry!A:E')->getValues();
			$wReg = []; $wLkp = [];
			if ($response) {
				array_shift($response);
				foreach ($response as $r) {
					$k = trim($r[0]??''); $l = trim($r[1]??'');
					if ($k && $k !== 'none') {
						$wReg[$k] = ['label'=>$l, 'component'=>trim($r[2]??''), 'source'=>trim($r[3]??''), 'parameter'=>trim($r[4]??'id')];
						$wLkp[$l] = $k;
					}
				}
				file_put_contents($wRegPath, json_encode($wReg, JSON_PRETTY_PRINT));
				file_put_contents($wLkpPath, json_encode($wLkp, JSON_PRETTY_PRINT));
				echo "DONE\n";
			}
		} catch (Exception $e) { echo "FAILED "; }

	/*
	|--------------------------------------------------------------------------
	| TASK 5: SYNC LINK TILES
	|--------------------------------------------------------------------------
	*/
		echo " - Task 5: Link Tiles... ";
		try {
			$response = $service->spreadsheets_values->get($sheetId, 'Link Tile Registry!A:G')->getValues();
			$tReg = []; $tLkp = [];
			if ($response) {
				array_shift($response);
				foreach ($response as $r) {
					$k = trim($r[0]??''); $l = trim($r[2]??'');
					if ($k) {
						$tReg[$k] = [
							'url'   => trim($r[1]??''),
							'label' => $l,
							'icon'  => trim($r[3]??''),
							'color' => trim($r[5]??'btn-primary')
						];
						$tLkp[$l] = $k;
					}
				}
				file_put_contents($tRegPath, json_encode($tReg, JSON_PRETTY_PRINT));
				file_put_contents($tLkpPath, json_encode($tLkp, JSON_PRETTY_PRINT));
				echo "DONE\n";
			}
		} catch (Exception $e) { echo "FAILED "; }

	/*
	|--------------------------------------------------------------------------
	| TASK 6: SYNC PEOPLE & PERMISSIONS (A:K)
	|--------------------------------------------------------------------------
	| Resolves "lastname-firstname" slugs into actual Google File IDs
	| by searching the Staff Photos folder.
	*/
		echo " - Task 6: People Directory & Photo Resolver... ";
		$peopleId = config('people_list_sheet_id'); 
		$photoFolderId = config('people_staff_photos_folder_id');

		if ($peopleId) {
			try {
				$response = $service->spreadsheets_values->get($peopleId, 'A:K')->getValues();
				$staff = []; $board = []; $authorized = [];
				
				// Get the list of actual files in the photo folder from our Task 2 index
				$photoFiles = kgs_find_folder_in_tree($masterTree, $photoFolderId);

				if ($response) {
					array_shift($response);
					foreach ($response as $r) {
						$lastName = trim($r[2] ?? '');
						if (empty($lastName) || $lastName === 'Last Name') continue;

						$email = strtolower(trim($r[3]??'')); 
						if (strtoupper(trim($r[7]??'')) === 'TRUE' && $email) $authorized[] = $email;
						
						// --- PHOTO SLUG RESOLUTION ---
						$imageInput = trim($r[5] ?? ''); // e.g. "arnold-lori"
						$resolvedId = '';

						if (!empty($imageInput)) {
							if (strlen($imageInput) > 25) {
								// If it looks like a real Google ID already, use it directly
								$resolvedId = $imageInput;
							} elseif ($photoFiles) {
								// It's a slug. Search for match: .png > .jpg > .jpeg
								$exts = ['png', 'jpg', 'jpeg'];
								foreach ($exts as $ext) {
									$targetName = strtolower($imageInput . '.' . $ext);
									foreach ($photoFiles as $file) {
										if (strtolower($file['name']) === $targetName) {
											$resolvedId = $file['id'];
											break 2;
										}
									}
								}
							}
						}

						$p = [
							'name'     => trim(($r[1]??'') . ' ' . ($r[2]??'')), 
							'email'    => $email,
							'title'    => trim($r[4]??''), 
							'image_id' => $resolvedId, // Now contains the real Google ID
							'category' => trim($r[8]??''),
							'sort'     => (int)($r[10]??100)
						];

						if (strtolower(trim($r[9]??'')) === 'staff') $staff[] = $p;
						elseif (strtolower(trim($r[9]??'')) === 'board') $board[] = $p;
					}
					file_put_contents($baseDir . "people_staff.json", json_encode($staff, JSON_PRETTY_PRINT));
					file_put_contents($baseDir . "people_board.json", json_encode($board, JSON_PRETTY_PRINT));
					file_put_contents($baseDir . "authorized_users.json", json_encode($authorized, JSON_PRETTY_PRINT));
					echo "DONE\n";
				}
			} catch (Exception $e) { echo "FAILED (" . $e->getMessage() . ") "; }
		}

	/*
	|--------------------------------------------------------------------------
	| TASK 7: URL ALIASES & INVALIDATION
	|--------------------------------------------------------------------------
	*/
		echo " - Task 7: URL Aliases... ";
		try {
			$aliasResponse = $service->spreadsheets_values->get($sheetId, 'URL Aliases!A:B')->getValues();
			if ($aliasResponse) {
				$newMap = []; foreach ($aliasResponse as $r) { $from=trim($r[0]??''); $to=trim($r[1]??''); if($from && !str_starts_with($from,'-') && $from!=='Key') $newMap[$from]=$to; }
				$oldMap = file_exists($aliasPath) ? json_decode(file_get_contents($aliasPath), true) : [];
				$changed = []; 
				foreach($newMap as $f=>$t){ if(($oldMap[$f]??'')!==$t){$changed[]=$t; if(isset($oldMap[$f]))$changed[]=$oldMap[$f];} }
				foreach($oldMap as $f=>$t){ if(!isset($newMap[$f]))$changed[]=$t; }
				if(!empty($changed) && class_exists('CMSCache')) CMSCache::invalidateMany(array_unique($changed));
				file_put_contents($aliasPath, json_encode($newMap, JSON_PRETTY_PRINT));
				echo "DONE\n";
			}
		} catch (Exception $e) { echo "FAILED "; }

	/*
	|--------------------------------------------------------------------------
	| TASK 8: SYNC LIVE FEED
	|--------------------------------------------------------------------------
	*/
		if (!empty($configMap['live_feed_sheet_id'])) {
			echo " - Task 8: Live Feed... ";
			try {
				$response = $service->spreadsheets_values->get($configMap['live_feed_sheet_id'], 'Sheet1!A:F')->getValues();
				if ($response) {
					array_shift($response); $posts = [];
					foreach ($response as $row) { if(empty(trim($row[3]??''))) continue; $posts[] = ['timestamp'=>$row[0]??'', 'time'=>$row[1]??'', 'date'=>$row[2]??'', 'text'=>$row[3]??'', 'image_id'=>$row[4]??'', 'author'=>$row[5]??'Office']; }
					file_put_contents($baseDir."sheets/feed_".$configMap['live_feed_sheet_id'].".json", json_encode(array_reverse($posts), JSON_PRETTY_PRINT));
					echo "DONE\n";
				}
			} catch (Exception $e) { echo "FAILED "; }
		}

	/*
	|--------------------------------------------------------------------------
	| TASK 9: DOCS & ASSETS
	|--------------------------------------------------------------------------
	*/
		echo " - Task 9: Assets... ";
		$manifest = array_merge((array)$configMap, include ROOT_PATH . 'cfg/google.php');
		foreach ($manifest as $key => $id) {
			if(!$id || !is_string($id)) continue;
			if(str_ends_with($key, '_doc_id')){ try{$html=GoogleDriveManager::exportFile($id, 'text/html'); if($html)file_put_contents($baseDir."html-content/".$id.".html",$html);}catch(Exception $e){}}
			if(preg_match('/(img|image)/i',$key) && strlen($id)>15){ try{$savePath=ROOT_PATH."public/assets/img/".$key.".png"; if(!file_exists($savePath)){$binary=GoogleDriveManager::downloadFile($id); if($binary)file_put_contents($savePath,$binary);}}catch(Exception $e){}}
		}
		echo "DONE\n";

	/*
	|--------------------------------------------------------------------------
	| TASK 10: SYNC NAVIGATION (Recursive Tree + Metadata for Tiles)
	|--------------------------------------------------------------------------
	*/
		echo " - Task 10: Navigation... ";
		// Range A:G covers: Key(0), Label(1), Parent(2), Show(3), Icon(4), Sort(6)
		$navResponse = $service->spreadsheets_values->get($sheetId, 'Navigation!A:G')->getValues();
		
		if (is_array($navResponse)) {
			array_shift($navResponse);
			$flat = [];
			foreach ($navResponse as $row) {
				$label = trim($row[1] ?? ''); 
				if (empty($label)) continue;

				$flat[$label] = [
					'label'  => $label,
					'parent' => trim($row[2] ?? ''),
					'show'   => (strtoupper(trim($row[3] ?? '')) === 'TRUE'),
					'icon'   => trim($row[4] ?? ''),             // Column E (Manual Icon)
					'sort'   => (int)($row[6] ?? 100),
					'slug'   => strtolower(str_replace([' ', '_'], '-', $label)),
					'url'    => '', // Calculated below
					'items'  => []
				];
			}

			$tree = [];
			$getPath = function($lbl, $data) use (&$getPath) {
				$i = $data[$lbl]; 
				if (empty($i['parent']) || !isset($data[$i['parent']])) return $i['slug'] . "/";
				return $getPath($i['parent'], $data) . $i['slug'] . "/";
			};

			foreach ($flat as $lbl => &$item) {
				$item['url'] = $getPath($lbl, $flat);
				if (empty($item['parent'])) {
					$tree[] = &$item;
				} else if (isset($flat[$item['parent']])) {
					$flat[$item['parent']]['items'][] = &$item;
				}
			}

			$sortFn = function($a, $b) { return $a['sort'] <=> $b['sort']; };
			usort($tree, $sortFn);
			foreach ($flat as &$m) { if (!empty($m['items'])) usort($m['items'], $sortFn); }

			file_put_contents($baseDir . "site_menu.json", json_encode($tree, JSON_PRETTY_PRINT));
			echo "DONE\n";
		}

	/*
	|--------------------------------------------------------------------------
	| TASK 11: ICON MAP
	|--------------------------------------------------------------------------
	*/
		echo " - Task 11: Icon Map... ";
		$resp = $service->spreadsheets_values->get($sheetId, 'Icon Map!A:B')->getValues();
		if($resp){ array_shift($resp); $m=[]; foreach($resp as $r){ if(trim($r[0]??'')) $m[strtolower(trim($r[0]))]=trim($r[1]??''); }
		file_put_contents($baseDir."icon_map.json", json_encode($m, JSON_PRETTY_PRINT)); }
		echo "DONE\n";

		file_put_contents($baseDir . 'last_refresh.json', json_encode(['timestamp'=>time(),'date_human'=>date('F j, Y, g:i a')], JSON_PRETTY_PRINT));
		$success = true;

} catch (Exception $e) { fwrite(STDERR, " FATAL ERROR: " . $e->getMessage() . "\n"); }
finally { unlink($lockFile); if (function_exists('purge_server_cache')) purge_server_cache(); }
exit($success ? 0 : 1);