<?php
 // includes/class-kgsweb-google-helpers.php
 if (!defined("ABSPATH")) {
     exit();
 }

 class KGSweb_Google_Helpers
 {
     public static function init()
     {
         /* no-op */
     }

     // -----------------------------
     // Folder / File Name Formatting
     // -----------------------------
     public static function format_folder_name($name)
     {
         $name = preg_replace("/[-_]+/", " ", $name);
         $name = preg_replace("/\s+/", " ", $name);
         return ucwords(trim($name));
     }

     public static function extract_date($name)
     {
         if (preg_match("/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/", $name, $m)) {
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

     public static function sanitize_file_name($filename)
     {
         if (!$filename) {
             return "";
         }

         // Safe regex: place dash at start or end to avoid "invalid range" warnings
         $sanitized = preg_replace("/[^a-zA-Z0-9_.-]/", "", $filename);

         // Fallback in case preg_replace returns null
         if ($sanitized === null || $sanitized === "") {
             error_log(
                 "[KGSweb] WARNING: sanitize_file_name() returned empty for filename: $filename"
             );
             $sanitized = "file-" . time();
         }

         return $sanitized;
     }

     // -----------------------------
     // Item Sorting
     // -----------------------------
     public static function sort_items($items)
     {
         usort($items, function ($a, $b) {
             $isFolderA = ($a["type"] ?? ($a["mimeType"] ?? "")) === "folder";
             $isFolderB = ($b["type"] ?? ($b["mimeType"] ?? "")) === "folder";
             if ($isFolderA && !$isFolderB) {
                 return -1;
             }
             if (!$isFolderA && $isFolderB) {
                 return 1;
             }

             $dateA = self::extract_date($a["name"] ?? "") ?? "99999999";
             $dateB = self::extract_date($b["name"] ?? "") ?? "99999999";
             if ($dateA !== $dateB) {
                 return strcmp($dateB, $dateA);
             } // newest first

             return strcasecmp($a["name"] ?? "", $b["name"] ?? "");
         });
         return $items;
     }

     // -----------------------------
     // Icon Selection
     // -----------------------------
     public static function icon_for_mime_or_ext($mime, $ext)
     {
         $mime = strtolower($mime ?? "");
         $ext = strtolower($ext ?? "");
         if ($mime === "application/vnd.google-apps.folder") {
             return "fa-folder";
         }
         if ($ext === "pdf") {
             return "fa-file-pdf";
         }
         if (in_array($ext, ["doc", "docx"])) {
             return "fa-file-word";
         }
         if (in_array($ext, ["xls", "xlsx"])) {
             return "fa-file-excel";
         }
         if (in_array($ext, ["ppt", "pptx"])) {
             return "fa-file-powerpoint";
         }
         if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) {
             return "fa-file-image";
         }
         if (in_array($ext, ["wav", "mp4", "m4v", "mov", "avi"])) {
             return "fa-file-video";
         }
         if (in_array($ext, ["mp3", "wav"])) {
             return "fa-file-audio";
         }
         return "fa-file";
     }

     // -----------------------------
     // Fetch raw file contents (generic)
     // -----------------------------
     public static function fetch_file_contents_raw(string $file_id): ?string
     {
         try {
             // Get the Google Drive service directly
             $driveService = KGSweb_Google_Integration::get_drive_service(); // must return Google\Service\Drive
             if (!$driveService) {
                 error_log(
                     "[KGSweb] fetch_file_contents_raw: Drive service not initialized."
                 );
                 return null;
             }

             // Fetch the file contents
             $response = $driveService->files->get($file_id, [
                 "alt" => "media",
             ]);
             $body = $response->getBody();
             return $body ? (string) $body->getContents() : null;
         } catch (Exception $e) {
             error_log(
                 "[KGSweb] fetch_file_contents_raw ERROR for $file_id: " .
                     $e->getMessage()
             );
             return null;
         }
     }

     // -----------------------------
     // Helper to convert local path to URL
     // -----------------------------
     public static function get_cached_file_url($path)
     {
         $upload_dir = wp_upload_dir();
         $base_dir = $upload_dir["basedir"];
         $base_url = $upload_dir["baseurl"];
         if (strpos($path, $base_dir) === 0) {
             return str_replace($base_dir, $base_url, $path);
         }
         return $path;
     }

     // -----------------------------
     // Convert PDF to PNG (cached)
     // -----------------------------
     public static function convert_pdf_to_png_cached(
         $pdf_path,
         $filename = null
     ) {
         $upload_dir = wp_upload_dir();
         $cache_dir = $upload_dir["basedir"] . "/kgsweb-cache/";
         if (!file_exists($cache_dir)) {
             wp_mkdir_p($cache_dir);
         }

         $basename = $filename
             ? self::sanitize_file_name($filename)
             : basename($pdf_path);
         $png_path =
             $cache_dir . pathinfo($basename, PATHINFO_FILENAME) . ".png";

         $meta_path = $png_path . ".meta.json";

         // Return cached PNG if exists
         if (file_exists($png_path)) {
             error_log("[KGSweb] PDF already converted: $png_path");

             // Ensure meta file exists (self-heal if missing)
             if (!file_exists($meta_path)) {
                 [$width, $height] = getimagesize($png_path);
                 file_put_contents(
                     $meta_path,
                     json_encode(["width" => $width, "height" => $height])
                 );
                 error_log(
                     "[KGSweb] Created missing meta.json for cached PNG: {$width}x{$height}"
                 );
             }

             return $png_path;
         }

         // Verify PDF exists before converting
         if (!file_exists($pdf_path)) {
             error_log(
                 "[KGSweb] ERROR: PDF file not found for conversion: $pdf_path"
             );
             return false;
         }

         // Debug: report file size and header
         $filesize = filesize($pdf_path);
         $header = bin2hex(file_get_contents($pdf_path, false, null, 0, 16));
         error_log(
             "[KGSweb] Ready to convert PDF $pdf_path ($filesize bytes, header: $header)"
         );

         // Ensure Imagick is available
         if (!class_exists("Imagick")) {
             error_log("[KGSweb] Imagick extension is NOT installed.");
             return false;
         }

         // Convert PDF to PNG
         try {
             $img = new Imagick();
             $img->setResolution(150, 150); // optional: improve quality
             $img->readImage($pdf_path . "[0]"); // read first page only

             // Convert to PNG
             $img->setImageFormat("png");

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
             file_put_contents(
                 $meta_path,
                 json_encode(["width" => $width, "height" => $height])
             );
             error_log(
                 "[KGSweb] Converted PDF -> PNG: {$width}x{$height}, saved to $png_path"
             );

             return $png_path;
         } catch (Exception $e) {
             error_log(
                 "[KGSweb] Imagick ERROR converting PDF: " . $e->getMessage()
             );
             return false;
         }
     }

     // -----------------------------
     // Optimize Image (JPEG/PNG)
     // -----------------------------
     public static function optimize_image_cached(
         $file_id,
         $filename,
         $max_width = 1200
     ) {
         if (empty($file_id)) {
             return null;
         }

         $upload_dir = wp_upload_dir();
         $cache_dir = $upload_dir["basedir"] . "/kgsweb-cache";
         if (!file_exists($cache_dir)) {
             wp_mkdir_p($cache_dir);
         }

         $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
         $safe_name =
             self::sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)) .
             "." .
             $ext;
         $cached_file = $cache_dir . "/" . $safe_name;
         $meta_path = $cached_file . ".meta.json";

         // 1. If cached file already exists, ensure meta.json exists
         if (file_exists($cached_file)) {
             if (!file_exists($meta_path)) {
                 [$width, $height] = getimagesize($cached_file);
                 file_put_contents(
                     $meta_path,
                     json_encode(["width" => $width, "height" => $height])
                 );
                 error_log(
                     "[KGSweb] Created missing meta.json for cached image: {$width}x{$height}"
                 );
             }
             return $cached_file;
         }

         // 2. Download fresh file
         $content = KGSweb_Google_Integration::download_file($file_id);
         if (!$content) {
             return null;
         }

         file_put_contents($cached_file, $content);

         // 3. Optimize with Imagick if available
         if (class_exists("Imagick")) {
             try {
                 $img = new Imagick($cached_file);
                 $width = $img->getImageWidth();
                 $height = $img->getImageHeight();

                 if ($width > $max_width) {
                     $img->resizeImage(
                         $max_width,
                         0,
                         Imagick::FILTER_LANCZOS,
                         1
                     );
                     $width = $img->getImageWidth(); // update after resize
                     $height = $img->getImageHeight();
                 }

                 $img->setImageCompression(Imagick::COMPRESSION_JPEG);
                 $img->setImageCompressionQuality(85);
                 $img->stripImage();
                 $img->writeImage($cached_file);
                 $img->clear();
                 $img->destroy();

                 // Store dimensions after optimization
                 file_put_contents(
                     $meta_path,
                     json_encode(["width" => $width, "height" => $height])
                 );
                 error_log(
                     "[KGSweb] Optimized & cached image {$safe_name}: {$width}x{$height}"
                 );
             } catch (Exception $e) {
                 error_log(
                     "[KGSweb] Image optimization failed for $filename - " .
                         $e->getMessage()
                 );
             }
         } else {
             // Fallback: just store dimensions without optimization
             [$width, $height] = getimagesize($cached_file);
             file_put_contents(
                 $meta_path,
                 json_encode(["width" => $width, "height" => $height])
             );
             error_log(
                 "[KGSweb] Cached unoptimized image {$safe_name}: {$width}x{$height}"
             );
         }

         return $cached_file;
     }

     /**
      * Fetch children of a Drive folder and normalize array structure.
      * Generic version used by both Drive-Docs and Ticker.
      *
      * @param string $folderId
      * @return array Array of nodes with keys: id, name, mimeType, modifiedTime, parents[]
      */
	public static function fetch_drive_children($folderId)
	{
		$drive_docs = new KGSweb_Google_Drive_Docs(); // instantiate the class
		$children = $drive_docs->list_drive_children($folderId); // call non-static method

		// Filter out trashed items and normalize keys
		$normalized = [];
		foreach ($children as $item) {
			if (!empty($item["trashed"])) {
				continue;
			}

			$normalized[] = [
				"id" => $item["id"],
				"name" => $item["name"],
				"mimeType" => $item["mimeType"],
				"modifiedTime" => $item["modifiedTime"] ?? "",
				"parents" => $item["parents"] ?? [],
			];
		}

		return $normalized;
	}

     /**
      * Fetch file contents from Drive.
      * Generic helper for Drive-Docs and Ticker.
      *
      * @param string $fileId
      * @param string|null $mimeType Optional mimeType override
      * @return string File contents
      */
     public static function fetch_file_contents($fileId, $mimeType = null)
     {
         $integration = new KGSWeb_Google_Integration();
         return $integration->get_file_contents($fileId, $mimeType);
     }

     /**
      * Recursively sort a folder tree.
      *
      * @param array $tree
      * @param string $sortBy alpha-asc|alpha-desc|date-asc|date-desc
      * @return array Sorted tree
      */
     public static function sort_tree(array $tree, string $sortBy): array
     {
         usort($tree, function ($a, $b) use ($sortBy) {
             switch ($sortBy) {
                 case "alpha-desc":
                     return strcasecmp($b["name"], $a["name"]);
                 case "date-asc":
                     return strtotime($a["modifiedTime"]) -
                         strtotime($b["modifiedTime"]);
                 case "date-desc":
                     return strtotime($b["modifiedTime"]) -
                         strtotime($a["modifiedTime"]);
                 case "alpha-asc":
                 default:
                     return strcasecmp($a["name"], $b["name"]);
             }
         });

         foreach ($tree as &$node) {
             if (!empty($node["children"]) && is_array($node["children"])) {
                 $node["children"] = self::sort_tree(
                     $node["children"],
                     $sortBy
                 );
             }
         }
         return $tree;
     }

     /**
      * Recursively build HTML for folder tree.
      * @param array $tree
      * @param bool $collapsed Whether nodes start collapsed
      * @param bool $staticNoToggle Whether collapse toggles are disabled (false-static)
      * @return string HTML
      */
     public static function render_tree_html(
         array $tree,
         bool $collapsed = false,
         bool $staticNoToggle = false
     ): string {
         $html =
             '<ul class="kgsweb-documents-tree"' .
             ($staticNoToggle ? ' data-static="true"' : "") .
             ">";
         foreach ($tree as $node) {
             if ($node["mimeType"] === "application/vnd.google-apps.folder") {
                 $hasChildren = !empty($node["children"]);
                 $html .=
                     '<li class="folder' .
                     ($hasChildren ? " has-children" : "") .
                     '">';
                 if ($hasChildren && !$staticNoToggle) {
                     $html .=
                         '<span class="kgsweb-toggle">' .
                         ($collapsed ? "+" : "−") .
                         "</span>";
                 }
                 $html .=
                     '<span class="folder-name">' .
                     esc_html($node["name"]) .
                     "</span>";
                 if ($hasChildren) {
                     $html .=
                         '<ul class="children"' .
                         ($collapsed ? ' style="display:none;"' : "") .
                         ">";
                     $html .= self::render_tree_html(
                         $node["children"],
                         $collapsed,
                         $staticNoToggle
                     );
                     $html .= "</ul>";
                 }
                 $html .= "</li>";
             } else {
                 $html .= '<li class="document">';
                 $html .=
                     '<a href="' .
                     esc_url($node["url"] ?? "#") .
                     '" target="_blank">' .
                     esc_html($node["name"]) .
                     "</a>";
                 $html .= "</li>";
             }
         }
         $html .= "</ul>";
         return $html;
     }
 }