<?php
// includes/class-kgsweb-google-menus.php
if (!defined("ABSPATH")) {
    exit();
}

/**
 * KGSweb_Google_Menus
 *
 * Handles fetching, caching, and rendering of breakfast/lunch menus from Google Drive.
 * Supports PDFs (converted to PNG) and image optimization.
 */
class KGSweb_Google_Menus
{
    /**
     * Initialize class (no-op, placeholder for future hooks)
     */
    public static function init()
    {
        /* no-op */
    }

    /*******************************
     * Shortcode renderer
     *******************************/
    /**
     * Render menu shortcode [kgsweb_menu type="breakfast|lunch" width="600px|80%"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
	public static function shortcode_render($atts = [])
	{
		// Merge shortcode attributes with defaults
		$atts = shortcode_atts(
			[
				"type"  => "lunch", // default type
				"width" => "",      // e.g., '80%' or '600px'
			],
			$atts,
			"kgsweb_menu"
		);

		// Sanitize type
		$type = strtolower(sanitize_text_field($atts["type"]));
		if (!in_array($type, ["breakfast", "lunch"], true)) {
			$type = "lunch";
		}

		// Fetch cached payload
		$payload = self::get_menu_payload($type);

		if (is_wp_error($payload) || empty($payload["image_url"])) {
			return sprintf(
				'<div class="kgsweb-menu-empty">%s menu not available.</div>',
				ucfirst($type)
			);
		}

		// Prepare values
		$img_url         = esc_url($payload["image_url"]);
		$original_width  = intval($payload["width"]);
		$original_height = intval($payload["height"]);
		$alt             = sprintf("%s Menu", ucfirst($type));

		// --- Build styles ---
		// Inner width (can be px or percentage); leave empty for auto
		$inner_width_style = "";
		$raw_w = trim($atts["width"] ?? "");
		if ($raw_w !== "") {
			$w = esc_attr($raw_w);
			if (is_numeric($w)) {
				$w .= "px";
			}
			// Apply to inner wrapper (inline-block) so percentage widths center correctly
			$inner_width_style = "width:{$w};";
		}

		// Outer container: centers the inline-block inner wrapper
		$outer_style = 'style="text-align:center;"';

		// Inner wrapper: shrink-wrap to width (px or %) and allow positioning of overlay
		$inner_style = 'style="display:inline-block;position:relative;max-width:100%;' . $inner_width_style . '"';

		// Image: fully fluid inside inner wrapper
		$img_style = 'style="display:block;width:100%;height:auto;"';

		// Zoom button: fixed square circular background so it won't stretch with the image height
		$zoom_button = '<button type="button" class="kgsweb-menu-zoom-btn" aria-hidden="true" 
			style="position:absolute;top:8px;right:8px;width:36px;height:36px;border-radius:50%;
				   background:rgba(0,0,0,0.45);border:0;color:#fff;display:flex;
				   align-items:center;justify-content:center;padding:0;cursor:pointer;">
			<i class="fa fa-search-plus" aria-hidden="true"></i>
		</button>';

		// Build HTML (avoids complicated sprintf placeholders)
		return
			'<div class="kgsweb-menu kgsweb-menu-' . esc_attr($type) . '" ' . $outer_style . '>' .
				'<div class="kgsweb-menu-inner" ' . $inner_style . '>' .
					'<img src="' . $img_url . '" alt="' . esc_attr($alt) . '" loading="lazy" ' . $img_style . '>' .
					$zoom_button .
				'</div>' .
			'</div>';
	}



    /*******************************
     * Cache helpers
     *******************************/
    /**
     * Retrieve cached menu payload, or fetch if missing
     *
     * @param string $type 'breakfast' or 'lunch'
     * @return array|WP_Error
     */
    public static function get_menu_payload($type)
    {
        $key = "kgsweb_cache_menu_" . $type;
        $data = get_transient($key);

        if ($data === false) {
            error_log("[KGSweb] Cache miss for menu type: $type");
            $data = self::build_latest_menu_image($type);
            set_transient($key, $data, HOUR_IN_SECONDS);
            error_log("[KGSweb] Menu cache set for $type");
        } else {
            error_log("[KGSweb] Cache hit for menu type: $type");
        }

        if (empty($data["image_url"])) {
            return new WP_Error(
                "no_menu",
                __("Menu not available.", "kgsweb"),
                ["status" => 404]
            );
        }

        return $data;
    }

    /**
     * Force refresh menu cache
     *
     * @param string $type
     */
    public static function refresh_menu_cache($type)
    {
        error_log("[KGSweb] Refreshing menu cache for $type");
        $data = self::build_latest_menu_image($type);
        set_transient("kgsweb_cache_menu_" . $type, $data, HOUR_IN_SECONDS);
        update_option(
            "kgsweb_cache_last_refresh_menu_" . $type,
            current_time("timestamp")
        );
        error_log("[KGSweb] Menu cache refreshed for $type");
    }

    /*******************************
     * Google fetch + conversion
     *******************************/
    /**
     * Fetch a menu file (PDF or image) and prepare a local cached copy.
     * PDFs are converted to PNG; images are optimized.
     *
     * @param string $file_id Google Drive file ID
     * @param string $filename Original file name
     * @param string $mime_type MIME type of the file
     * @param string $type 'breakfast' or 'lunch'
     * @return string|false Local file path or false on failure
     */
    private static function fetch_file_for_menu(
        $file_id,
        $filename,
        $mime_type,
        $type = "lunch"
    ) {
        $upload_dir = wp_upload_dir();

        // Create type-specific cache folder (breakfast-menu / lunch-menu)
        $cache_base = trailingslashit($upload_dir["basedir"]) . "kgsweb-cache";
        $cache_dir = $cache_base . "/" . sanitize_key($type) . "-menu";

        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }

        $sanitized = sanitize_file_name($filename);
        $ext = strtolower(pathinfo($sanitized, PATHINFO_EXTENSION));
        $cached_path =
            $cache_dir .
            "/" .
            pathinfo($sanitized, PATHINFO_FILENAME) .
            "." .
            $ext;

        // If a converted PNG already exists, use it directly

        $png_cached_path =
            $cache_dir . "/" . pathinfo($sanitized, PATHINFO_FILENAME) . ".png";
        if (file_exists($png_cached_path)) {
            error_log("[KGSweb] Using cached PNG: $png_cached_path");

            // Always compute dimensions so first cache fill stores width/height

            $size = @getimagesize($png_cached_path);
            if ($size) {
                error_log(
                    "[KGSweb] Cached PNG dimensions: {$size[0]}x{$size[1]}"
                );
            } else {
                error_log(
                    "[KGSweb] WARNING: getimagesize() failed for cached PNG $png_cached_path"
                );
            }

            return $png_cached_path;
        }

        // Return cached file if it exists (non-PNG, e.g. a cached PDF or image)

        if (file_exists($png_cached_path)) {
            error_log("[KGSweb] Using cached file: $png_cached_path");
            return $png_cached_path;
        }

        // Handle PDFs

        if ($mime_type === "application/pdf") {
            error_log("[KGSweb] Fetching PDF: $filename (ID: $file_id)");
            try {
                $pdf_content = KGSweb_Google_Helpers::fetch_file_contents_raw(
                    $file_id
                );
                if (!$pdf_content) {
                    error_log(
                        "[KGSweb] ERROR: fetch_file_contents_raw() returned empty for $filename (ID: $file_id)"
                    );
                    return false;
                }

                // Save PDF to cache

                file_put_contents($cached_path, $pdf_content);
                error_log("[KGSweb] PDF saved to cache: $cached_path");

                // Convert the *local* PDF to PNG and return the PNG path

                return KGSweb_Google_Helpers::convert_pdf_to_png_cached(
                    $cached_path,
                    $filename
                );
            } catch (Exception $e) {
                error_log("[KGSweb] ERROR retrieving PDF: " . $e->getMessage());
                return false;
            }

						 
        }

        // Handle images

        if (in_array($mime_type, ["image/png", "image/jpeg", "image/jpg"])) {
            try {
                $img_content = KGSweb_Google_Helpers::get_file_contents(
                    $file_id
                );
                if (!$img_content) {
                    error_log(
                        "[KGSweb] ERROR: get_file_contents() returned empty for image $filename (ID: $file_id)"
                    );
                    return false;
                }

                file_put_contents($cached_path, $img_content);
                error_log("[KGSweb] Image saved to cache: $cached_path");

                // Resize to 1000px wide, preserve aspect ratio

                return KGSweb_Google_Helpers::optimize_image_cached(
                    $file_id,
                    $filename,
                    1000
                );
            } catch (Exception $e) {
                error_log(
                    "[KGSweb] ERROR retrieving image: " . $e->getMessage()
                );
                return false;
            }
        }

        // Unsupported type

        error_log(
            "[KGSweb] fetch_file_for_menu - unsupported MIME $mime_type for $filename"
        );
        return false;
    }

	// -----------------------------
	// CONVERT PDF MENU TO IMAGE PNG
	// -----------------------------
	private static function convert_pdf_for_menu($pdf_path, $filename, $type)
	{
		$upload_dir = wp_upload_dir();
		$cache_base = trailingslashit($upload_dir["basedir"]) . "kgsweb-cache";
		$cache_dir = $cache_base . "/" . sanitize_key($type) . "-menu";
		if (!file_exists($cache_dir)) {
			wp_mkdir_p($cache_dir);
		}

		$basename = self::sanitize_file_name($filename);
		$menu_png_path =
			$cache_dir . "/" . pathinfo($basename, PATHINFO_FILENAME) . ".png";
		$meta_path = $menu_png_path . '.meta.json';

		// If menu-specific PNG already exists, return it
		if (file_exists($menu_png_path)) {
			return $menu_png_path;
		}

		try {
			// Call the helper to convert PDF to PNG
			$generic_png = KGSweb_Google_Helpers::convert_pdf_to_png_cached(
				$pdf_path,
				$filename
			);

			// Copy to menu-specific folder if needed
			if ($generic_png && $generic_png !== $menu_png_path) {
				copy($generic_png, $menu_png_path);

				// Ensure menu-specific PNG is resized to max 1000px width
				$img = new Imagick($menu_png_path);
				if ($img->getImageWidth() > 1000) {
					$img->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1);
					$img->writeImage($menu_png_path);
				}
				$img->clear();
				$img->destroy();

				// Update meta.json
				[$width, $height] = getimagesize($menu_png_path);
				file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
				error_log("[KGSweb] Menu PNG resized & cached: {$width}x{$height}, path: $menu_png_path");
			}

			return $menu_png_path;
		} catch (Exception $e) {
			error_log(
				"[KGSweb] ERROR converting PDF to PNG: " . $e->getMessage()
			);
			return false;
		}
	}


    /*******************************
     * Build latest menu image
     *******************************/

    /**
     * Build latest menu image (PDF converted to PNG or optimized image)
     *
     * @param string $type 'breakfast' or 'lunch'
     * @return array payload with image_url, width, height
     */
    private static function build_latest_menu_image($type)
    {
        $settings = KGSweb_Google_Integration::get_settings();
        $folder_map = [
            "breakfast" => $settings["menu_breakfast_folder_id"] ?? "",
            "lunch" => $settings["menu_lunch_folder_id"] ?? "",
        ];
        $folder_id = $folder_map[$type] ?? "";

        // Helper for empty payload

        $empty_payload = function () use ($type) {
            return [
                "type" => $type,
                "image_url" => "",
                "width" => 0,
                "height" => 0,
                "updated_at" => current_time("timestamp"),
            ];
        };

        if (!$folder_id) {
            error_log("[KGSweb] No folder ID set for menu type: $type");
            return $empty_payload();
        }

        $files = KGSweb_Google_Helpers::list_files_in_folder($folder_id);
        if (empty($files)) {
            error_log("[KGSweb] No files found in folder ID: $folder_id");
            return $empty_payload();
        }

        // Keep only PDF or image files
        $files = array_filter($files, function ($f) {
            $ext = strtolower(pathinfo($f["name"] ?? "", PATHINFO_EXTENSION));
            $mime = strtolower($f["mimeType"] ?? "");
            return in_array($ext, [
                "pdf",
                "png",
                "jpg",
                "jpeg",
                "gif",
                "webp",
            ]) ||
                in_array($mime, [
                    "application/pdf",
                    "image/png",
                    "image/jpeg",
                    "image/jpg",
                    "image/gif",
                    "image/webp",
                ]);
        });

        if (empty($files)) {
            return $empty_payload();
        }

        // Sort newest first

        usort(
            $files,
            fn($a, $b) => strtotime($b["modifiedTime"]) <=>
                strtotime($a["modifiedTime"])
        );

        $latest = $files[0];
        $file_id = $latest["id"];
        $filename = $latest["name"];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $local_path =
            $ext === "pdf"
                ? self::fetch_file_for_menu(
                    $file_id,
                    $filename,
                    "application/pdf"
                )
                : self::fetch_file_for_menu(
                    $file_id,
                    $filename,
                    $latest["mimeType"]
                );

        if (!$local_path || !file_exists($local_path)) {
            error_log(
                "[KGSweb] Failed to fetch or create local file for $filename"
            );
            return $empty_payload();
        }

        // Look for cached metadata

        $meta_path = $local_path . ".meta.json";
        $width = $height = 0;

        if (file_exists($meta_path)) {
            $meta = json_decode(file_get_contents($meta_path), true);
            if (is_array($meta) && isset($meta["width"], $meta["height"])) {
                $width = intval($meta["width"]);
                $height = intval($meta["height"]);
                error_log(
                    "[KGSweb] Using cached dimensions from $meta_path: {$width}x{$height}"
                );
            }
        }

        // Fallback if no valid meta
        if (!$width || !$height) {
            $size = @getimagesize($local_path);
            if ($size) {
                [$width, $height] = $size;
                error_log(
                    "[KGSweb] Calculated dimensions via getimagesize(): {$width}x{$height}"
                );

										  
                file_put_contents(
                    $meta_path,
                    json_encode(["width" => $width, "height" => $height])
                );
            } else {
                error_log(
                    "[KGSweb] WARNING: getimagesize() failed for $local_path"
                );
                $width = $height = 0;
            }
        }

        $url = KGSweb_Google_Helpers::get_cached_file_url($local_path);

        error_log(
            "[KGSweb] Menu built: $filename (Width: $width, Height: $height, URL: $url)"
        );

        return [
            "type" => $type,
            "image_url" => esc_url($url),
            "width" => $width,
            "height" => $height,
            "updated_at" => current_time("timestamp"),
        ];
    }

    /*******************************
     * Full diagnostic / debug
     *******************************/
    /**
     * Full Diagnostic: PDF/Image conversion + cached URL + dimensions
     *
     * @param string $type 'breakfast' or 'lunch'
     */
    public static function debug_menu_full($type, $shortcode_width = "")
    {
        $settings = KGSweb_Google_Integration::get_settings();
        $folder_map = [
            "breakfast" => $settings["menu_breakfast_folder_id"] ?? "",
            "lunch" => $settings["menu_lunch_folder_id"] ?? "",
        ];
        $folder_id = $folder_map[$type] ?? "";
        if (!$folder_id) {
            echo "Folder ID for $type not set.\n";
            return;
        }

        echo "Debug for $type menu\n";
        echo "Folder ID: $folder_id\n\n";

        $files = KGSweb_Google_Helpers::list_files_in_folder($folder_id);
        if (empty($files)) {
            echo "No files found in folder.\n";
            return;
        }

        echo "Files found in folder:\n";
        foreach ($files as $f) {
            echo "- {$f["name"]} ({$f["mimeType"]}) – modified: {$f["modifiedTime"]}\n";
        }

        $latest = $files[0];
        $file_id = $latest["id"];
        $filename = $latest["name"];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        echo "\nProcessing latest file: $filename\n";

        // Fetch and convert PDF or optimize image

        if ($ext === "pdf") {
            try {
                $local_path = self::fetch_file_for_menu(
                    $file_id,
                    $filename,
                    "application/pdf"
                );
                echo $local_path && file_exists($local_path)
                    ? "PDF conversion SUCCESS: Local path: $local_path\n"
                    : "PDF conversion FAILED: File not created.\n";
            } catch (Exception $e) {
                echo "PDF conversion ERROR: " . $e->getMessage() . "\n";
            }
        } else {
            try {
                $local_path = self::fetch_file_for_menu(
                    $file_id,
                    $filename,
                    $latest["mimeType"]
                );
                echo $local_path && file_exists($local_path)
                    ? "Image optimization SUCCESS: Local path: $local_path\n"
                    : "Image optimization FAILED: File not created.\n";
            } catch (Exception $e) {
                echo "Image optimization ERROR: " . $e->getMessage() . "\n";
            }
        }

        $width = $height = 0;
        if ($local_path && file_exists($local_path)) {
            // Look for cached metadata

            $meta_path = $local_path . ".meta.json";
            if (file_exists($meta_path)) {
                $meta = json_decode(file_get_contents($meta_path), true);
                if (is_array($meta) && isset($meta["width"], $meta["height"])) {
                    $width = intval($meta["width"]);
                    $height = intval($meta["height"]);
                    echo "[KGSweb] Using cached dimensions from $meta_path: {$width}x{$height}\n";
                }
            }

            // Fallback if no meta file or invalid data
									  
															  

            if (!$width || !$height) {
                $size = @getimagesize($local_path);
                if ($size) {
                    [$width, $height] = $size;
                    echo "[KGSweb] Calculated dimensions via getimagesize(): {$width}x{$height}\n";

                    // Save metadata for next time

                    file_put_contents(
                        $meta_path,
                        json_encode(["width" => $width, "height" => $height])
                    );
                }
            }

            $url = KGSweb_Google_Helpers::get_cached_file_url($local_path);
            echo "\nFinal cached image details:\n";
            echo "- URL: $url\n";
            echo "- Width: $width\n";
            echo "- Height: $height\n";
            echo "- Exists on disk: " .
                (file_exists($local_path) ? "Yes" : "No") .
                "\n";
        } else {
            echo "No local image generated.\n";
            $url = "";
        }

        // Build inline style for responsive width

        $inline_style = "";
        if (!empty($shortcode_width)) {
            $w = esc_attr($shortcode_width);
            if (is_numeric($w)) {
                $w .= "px";
            }
            $inline_style = "style=\"width:{$w};height:auto;\"";
        } else {
            $inline_style = 'style="max-width:100%;height:auto;"';
        }

        // Also show what the shortcode would render

        $shortcode_output = sprintf(
            '<div class="kgsweb-menu kgsweb-menu-%s">
                <img src="%s" %s alt="%s Menu" loading="lazy">
                <i class="fa fa-search-plus kgsweb-menu-zoom" aria-hidden="true"></i>
            </div>',
            esc_attr($type),
            esc_url($url),
            $inline_style,
            esc_attr(ucfirst($type))
        );

        echo "\nShortcode would render with width '{$shortcode_width}':\n$shortcode_output\n";
    }
}
