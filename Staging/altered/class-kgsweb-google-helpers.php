<?php 
// includes/class-kgsweb-google-helpers.php
if (!defined('ABSPATH')) exit;

class KGSweb_Google_Helpers {

    public static function init() { /* no-op */ }

    // -----------------------------
    // Folder / File Name Formatting
    // -----------------------------
    public static function format_folder_name($name) {
        $name = preg_replace('/[-_]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return ucwords(trim($name));
    }

    public static function extract_date($name) {
        if (preg_match('/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/', $name, $m)) {
            return $m[1] . $m[2] . $m[3];
        }
        return null;
    }

//  public static function sanitize_file_name($name) {
//        $base = preg_replace('/\.[^.]+$/', '', $name); // remove extension
//        $base = preg_replace('/^school[\s-_]*board[\s-_]*/i', '', $base);
//        $base = preg_replace('/[-_]+/', ' ', $base);
//        if (preg_match('/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/', $base, $m)) {
//           $base = str_replace($m[0], sprintf('%s/%s/%s', $m[2], $m[3], $m[1]), $base);
//        }
//        return ucwords(trim($base));
 // }

	public static function sanitize_file_name($filename) {
		if (!$filename) return '';

		// Safe regex: place dash at start or end to avoid "invalid range" warnings
		$sanitized = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);

		// Fallback in case preg_replace returns null
		if ($sanitized === null || $sanitized === '') {
			error_log("[KGSweb] WARNING: sanitize_file_name() returned empty for filename: $filename");
			$sanitized = 'file-' . time();
		}

		return $sanitized;
	}

    // -----------------------------
    // Item Sorting
    // -----------------------------
    public static function sort_items($items) {
        usort($items, function($a, $b) {
            $isFolderA = ($a['type'] ?? $a['mimeType'] ?? '') === 'folder';
            $isFolderB = ($b['type'] ?? $b['mimeType'] ?? '') === 'folder';
            if ($isFolderA && !$isFolderB) return -1;
            if (!$isFolderA && $isFolderB) return 1;

            $dateA = self::extract_date($a['name'] ?? '') ?? '99999999';
            $dateB = self::extract_date($b['name'] ?? '') ?? '99999999';
            if ($dateA !== $dateB) return strcmp($dateB, $dateA); // newest first

            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        });
        return $items;
    }

    // -----------------------------
    // Icon Selection
    // -----------------------------
    public static function icon_for_mime_or_ext($mime, $ext) {
        $mime = strtolower($mime ?? '');
        $ext = strtolower($ext ?? '');
        if ($mime === 'application/vnd.google-apps.folder') return 'fa-folder';
        if ($ext === 'pdf') return 'fa-file-pdf';
        if (in_array($ext, ['doc','docx'])) return 'fa-file-word';
        if (in_array($ext, ['xls','xlsx'])) return 'fa-file-excel';
        if (in_array($ext, ['ppt','pptx'])) return 'fa-file-powerpoint';
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) return 'fa-file-image';
        if (in_array($ext, ['wav','mp4','m4v','mov','avi'])) return 'fa-file-video';
        if (in_array($ext, ['mp3','wav'])) return 'fa-file-audio';
        return 'fa-file';
    }

    // -----------------------------
    // Fetch raw file contents (generic)
    // -----------------------------
    public static function fetch_file_contents_raw(string $file_id): ?string {
        try {
            // Get the Google Drive service directly
            $driveService = KGSweb_Google_Integration::get_drive_service(); // must return Google\Service\Drive
            if (!$driveService) {
                error_log("[KGSweb] fetch_file_contents_raw: Drive service not initialized.");
                return null;
            }

            // Fetch the file contents
            $response = $driveService->files->get($file_id, ['alt' => 'media']);
            $body = $response->getBody();
            return $body ? (string)$body->getContents() : null;

        } catch (Exception $e) {
            error_log("[KGSweb] fetch_file_contents_raw ERROR for $file_id: " . $e->getMessage());
            return null;
        }
    }

    // -----------------------------
    // Helper to convert local path to URL
    // -----------------------------
    public static function get_cached_file_url($path) {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $base_url = $upload_dir['baseurl'];
        if (strpos($path, $base_dir) === 0) {
            return str_replace($base_dir, $base_url, $path);
        }
        return $path;
    }

	 // -----------------------------
	// Convert PDF to PNG (cached)
	// -----------------------------
	public static function convert_pdf_to_png_cached($pdf_path, $filename = null) {
		$upload_dir = wp_upload_dir();
		$cache_dir  = $upload_dir['basedir'] . '/kgsweb-cache/';
		if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);
	  
		$basename = $filename ? self::sanitize_file_name($filename) : basename($pdf_path);
		$png_path = $cache_dir . pathinfo($basename, PATHINFO_FILENAME) . '.png';
			 
		$meta_path = $png_path . '.meta.json';

		// Return cached PNG if exists
		if (file_exists($png_path)) {
			error_log("[KGSweb] PDF already converted: $png_path");

			// Ensure meta file exists (self-heal if missing)										
			if (!file_exists($meta_path)) {
				[$width, $height] = getimagesize($png_path);
				file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
				error_log("[KGSweb] Created missing meta.json for cached PNG: {$width}x{$height}");
			}

			return $png_path;
		}

		// Verify PDF exists before converting	
		if (!file_exists($pdf_path)) {
			error_log("[KGSweb] ERROR: PDF file not found for conversion: $pdf_path");
			return false;
		}

		// Debug: report file size and header
		$filesize = filesize($pdf_path);
		$header = bin2hex(file_get_contents($pdf_path, false, null, 0, 16));
		error_log("[KGSweb] Ready to convert PDF $pdf_path ($filesize bytes, header: $header)");

		// Ensure Imagick is available									 
		if (!class_exists('Imagick')) {
			error_log("[KGSweb] Imagick extension is NOT installed.");
			return false;
		}

		// Convert PDF to PNG	    
		try {
					   
			$img = new Imagick();
			$img->setResolution(150, 150); // optional: improve quality
			$img->readImage($pdf_path . '[0]'); // read first page only

			// Convert to PNG
			$img->setImageFormat('png');

			// Resize if wider than 1000px				  
			if ($img->getImageWidth() > 1000) {
				$img->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1);
			}

			$img->setImageCompression(Imagick::COMPRESSION_JPEG);
			$img->setImageCompressionQuality(85);
			$img->stripImage();
			$img->writeImage($png_path);
			$img->clear();
			$img->destroy();

			if (!file_exists($png_path)) {
				error_log("[KGSweb] Conversion failed: PNG file not created");
				return false;
			}

			// Immediately store dimensions in meta.json												
			[$width, $height] = getimagesize($png_path);
			file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
			error_log("[KGSweb] Converted PDF -> PNG: {$width}x{$height}, saved to $png_path");

			return $png_path;

		} catch (Exception $e) {
			error_log("[KGSweb] Imagick ERROR converting PDF: " . $e->getMessage());
			return false;
		}

							
	}

	// -----------------------------
	// Optimize Image (JPEG/PNG)
	// -----------------------------
	public static function optimize_image_cached($file_id, $filename, $max_width = 1200) {
		if (empty($file_id)) return null;

		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/kgsweb-cache';
		if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);

		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$safe_name = self::sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)) . '.' . $ext;
		$cached_file = $cache_dir . '/' . $safe_name;
		$meta_path   = $cached_file . '.meta.json';

		// 1. If cached file already exists, ensure meta.json exists
		if (file_exists($cached_file)) {
			if (!file_exists($meta_path)) {
				[$width, $height] = getimagesize($cached_file);
				file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
				error_log("[KGSweb] Created missing meta.json for cached image: {$width}x{$height}");
			}
			return $cached_file;
		}

		// 2. Download fresh file
		$content = KGSweb_Google_Helpers::download_file($file_id);
		if (!$content) return null;

		file_put_contents($cached_file, $content);

		// 3. Optimize with Imagick if available
		if (class_exists('Imagick')) {
			try {
				$img = new Imagick($cached_file);
				$width  = $img->getImageWidth();
				$height = $img->getImageHeight();

				if ($width > $max_width) {
					$img->resizeImage($max_width, 0, Imagick::FILTER_LANCZOS, 1);
					$width  = $img->getImageWidth();  // update after resize
					$height = $img->getImageHeight();
				}

				$img->setImageCompression(Imagick::COMPRESSION_JPEG);
				$img->setImageCompressionQuality(85);
				$img->stripImage();
				$img->writeImage($cached_file);
				$img->clear();
				$img->destroy();

		// Store dimensions after optimization												  
				file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
				error_log("[KGSweb] Optimized & cached image {$safe_name}: {$width}x{$height}");

			} catch (Exception $e) {
				error_log("[KGSweb] Image optimization failed for $filename - " . $e->getMessage());
			}
		} else {
			// Fallback: just store dimensions without optimization															   
			[$width, $height] = getimagesize($cached_file);
			file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
			error_log("[KGSweb] Cached unoptimized image {$safe_name}: {$width}x{$height}");
		}

		return $cached_file;
	}


	// -----------------------------
	// Ticker helper files
	// -----------------------------

		/**
		 * Extract plain text from a Google Docs API document object.
		 *
		 * @param Docs\Document $doc
		 * @return string
		 */
		public static function extract_text_from_doc($doc): string {
			if (!$doc || !$doc->getBody() || !$doc->getBody()->getContent()) {
				error_log("KGSWEB: extract_text_from_doc - received empty document object");
				return '';
			}

			$output = '';
			foreach ($doc->getBody()->getContent() as $structuralElement) {
				if (!isset($structuralElement['paragraph'])) {
					continue;
				}
				$paragraph = $structuralElement['paragraph'];
				if (!isset($paragraph['elements'])) {
					continue;
				}
				foreach ($paragraph['elements'] as $element) {
					if (isset($element['textRun']['content'])) {
						$output .= $element['textRun']['content'];
					}
				}
				$output .= "\n";
			}

			error_log("KGSWEB: extract_text_from_doc - extracted " . strlen($output) . " chars");
			return trim($output);
		}

		/**
		 * Fallback export of Google Doc as plain text.
		 *
		 * @param string $file_id
		 * @param Google\Service\Drive $service
		 * @return string
		 */
		public static function export_google_doc_as_text(string $file_id, $service): string {
			try {
				$response = $service->files->export($file_id, 'text/plain', ['alt' => 'media']);
				if (!$response) {
					error_log("KGSWEB ERROR: export_google_doc_as_text - NULL response for {$file_id}");
					return '';
				}

				$body = method_exists($response, 'getBody') ? $response->getBody() : null;
				if (!$body) {
					error_log("KGSWEB ERROR: export_google_doc_as_text - Missing body for {$file_id}");
					return '';
				}

				$content = (string) $body->getContents();
				error_log("KGSWEB: export_google_doc_as_text returned " . strlen($content) . " chars for {$file_id}");
				return $content;
			} catch (Exception $e) {
				error_log("KGSWEB ERROR: export_google_doc_as_text failed for {$file_id}: " . $e->getMessage());
				return '';
			}
		}

		/**
		 * Extracts paragraph text from structural elements.
		 *
		 * @param array|object $elements
		 * @return string
		 */
		public static function extract_paragraph_text_from_structural_elements($elements): string {
			$text = '';
			if (empty($elements)) return $text;

			foreach ($elements as $element) {
				if (is_object($element) && method_exists($element, 'getParagraph') && $element->getParagraph()) {
					$paragraph = $element->getParagraph();
					$pelems = $paragraph->getElements() ?? [];
					foreach ($pelems as $pe) {
						if (is_object($pe) && method_exists($pe, 'getTextRun') && $pe->getTextRun()) {
							$run = $pe->getTextRun();
							$content = method_exists($run, 'getContent') ? $run->getContent() : ($run->content ?? '');
							$text .= $content;
						} elseif (is_array($pe) && isset($pe['textRun']['content'])) {
							$text .= $pe['textRun']['content'];
						}
					}
					$text .= "\n";
				} elseif (is_array($element) && isset($element['paragraph'])) {
					$pelems = $element['paragraph']['elements'] ?? [];
					foreach ($pelems as $pe) {
						if (isset($pe['textRun']['content'])) $text .= $pe['textRun']['content'];
					}
					$text .= "\n";
				}
			}
			return $text;
		}
	



    /*******************************
     * Standardized Google Drive Helpers
     *******************************/

    /**
     * List files in a folder
     * @param string $folder_id
     * @param array $options Optional keys: 'mimeType', 'orderBy', 'pageSize'
     * @return array List of files with keys: id, name, mimeType, modifiedTime
     */
    public static function list_files_in_folder(
        string $folder_id,
        array $options = []
    ): array {
        $drive = self::get_drive();
        if (!$drive) {
            return [];
        }

        // drive->list_files_in_folder currently accepts only folder_id; pass options if implemented later
        return $drive->list_files_in_folder($folder_id);
    }

    /**
     * Get contents of a Google file (Docs or plain text)
     * @param string $file_id
     * @param string|null $mimeType Optional, if known
     * @return string|null File contents or null on error
     */
    public static function get_file_contents(
        string $file_id,
        ?string $mimeType = null
    ): ?string {
        $drive = self::get_drive();
        if (!$drive) {
            return null;
        }

        return $drive->get_file_contents($file_id, $mimeType);
    }

    /**
     * Get the latest file in a folder.
     *
     * Strategy:
     * 1) Try to use cached documents tree (transient 'kgsweb_docs_tree_' . md5($folder_id))
     *    - If present, traverse it to find the newest file by modifiedTime.
     * 2) If cache missing or empty, call Drive API directly with orderBy modifiedTime desc, pageSize=1.
     *
     * Returns array with keys: id, name, mimeType, modifiedTime OR null if none found.
     */
    public static function get_latest_file_from_folder(
        string $folder_id
    ): ?array {
        if (empty($folder_id)) {
            return null;
        }

        $drive = self::get_drive();
        if (!$drive) {
            return null;
        }

        // 1) Try cached tree
        $cache_key = "kgsweb_docs_tree_" . md5($folder_id);
        $tree = self::get_transient($cache_key);

        $latest = null;

        if ($tree !== false && !empty($tree) && is_array($tree)) {
            // tree is an array of nodes; traverse recursively
            $walker = function ($nodes) use (&$walker, &$latest) {
                foreach ((array) $nodes as $n) {
                    if (!is_array($n)) {
                        continue;
                    }
                    if (($n["type"] ?? "") === "file") {
                        $mt = $n["modifiedTime"] ?? "";
                        // store minimal file info
                        $file = [
                            "id" => $n["id"] ?? "",
                            "name" => $n["name"] ?? "",
                            "mimeType" => $n["mime"] ?? ($n["mimeType"] ?? ""),
                            "modifiedTime" => $mt,
                        ];
                        if (
                            empty($latest) ||
                            strcmp(
                                $file["modifiedTime"],
                                $latest["modifiedTime"]
                            ) > 0
                        ) {
                            $latest = $file;
                        }
                    }
                    if (!empty($n["children"]) && is_array($n["children"])) {
                        $walker($n["children"]);
                    }
                }
            };
            $walker($tree);
            if ($latest) {
                return $latest;
            }
        }

        // 2) Fallback: call Drive API directly to get newest file
        $client = self::get_google_client();
        if (!$client instanceof Client) {
            error_log(
                "KGSWEB: get_latest_file_from_folder - google client not available"
            );
            return null;
        }

        try {
            $service = new Drive($client);

            // Query for docs or plain text files; include other types if needed
            $q = sprintf("'%s' in parents and trashed = false", $folder_id);

            // Try to prioritize docs & text. The Drive API won't accept OR with complex grouping easily,
            // so we will not restrict MIME types here to allow any file; but we will order by modifiedTime.
            $params = [
                "q" => $q,
                "orderBy" => "modifiedTime desc",
                "pageSize" => 50,
                "fields" => "files(id,name,mimeType,modifiedTime)",
            ];

            $response = $service->files->listFiles($params);
            $files = $response->getFiles() ?: [];

            // Prefer google-docs or text/plain first, but still return first modified file if none match.
            $preferred = null;
            foreach ($files as $f) {
                $meta = [
                    "id" => $f->getId(),
                    "name" => $f->getName(),
                    "mimeType" => $f->getMimeType(),
                    "modifiedTime" => $f->getModifiedTime() ?? "",
                ];
                if (
                    in_array(
                        $meta["mimeType"],
                        ["application/vnd.google-apps.document", "text/plain", "application/pdf"],
                        true
                    )
                ) {
                    $preferred = $meta;
                    break;
                }
                // keep first as fallback
                if ($preferred === null) {
                    $preferred = $meta;
                }
            }

            if ($preferred) {
                return $preferred;
            }

            return null;
        } catch (Exception $e) {
            error_log(
                "KGSWEB: get_latest_file_from_folder - Drive API error: " .
                    $e->getMessage()
            );
            return null;
        }
    }
	
	



}
