<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array(  ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_separate', trailingslashit( get_stylesheet_directory_uri() ) . 'ctc-style.css', array( 'chld_thm_cfg_parent','lawson-stylesheet' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 25 );

// END ENQUEUE PARENT ACTION


//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////

// BEGIN CUSTOM CHILD FUNCTIONS

// Add columns to the Pages table
function kgs_add_template_parent_columns_to_pages($columns) {
    $columns['template'] = __('Template', 'kgs-lawson');
    $columns['parent'] = __('Parent', 'kgs-lawson');
    return $columns;
}
add_filter('manage_pages_columns', 'kgs_add_template_parent_columns_to_pages');

// Display content for the new columns in the Pages table
function kgs_display_template_parent_columns_pages($column, $post_id) {
    if ($column == 'template') {
        $template = get_page_template_slug($post_id);
        echo $template ? $template : 'pages';
    } elseif ($column == 'parent') {
        $parent_id = wp_get_post_parent_id($post_id);
        $parent_title = get_the_title($parent_id);
        echo $parent_title;
    }
}
add_action('manage_pages_custom_column', 'kgs_display_template_parent_columns_pages', 10, 2);

// Add columns to the Posts table
function kgs_add_template_parent_columns_to_posts($columns) {
    // Remove Template and Parent columns
    unset($columns['template']);
    unset($columns['parent']);
    return $columns;
}
add_filter('manage_posts_columns', 'kgs_add_template_parent_columns_to_posts');

// Make columns sortable for Pages
function kgs_make_pages_columns_sortable($columns) {
    $columns['author'] = 'author';
    $columns['template'] = 'template';
    return $columns;
}
add_filter('manage_edit-page_sortable_columns', 'kgs_make_pages_columns_sortable');

// Handle sorting for custom columns
function kgs_sort_columns($query) {
    if (!is_admin()) return;

    $orderby = $query->get('orderby');
    if ($orderby == 'template') {
        $meta_query = array(
            'relation' => 'OR',
            array(
                'key' => '_wp_page_template',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_wp_page_template',
                'compare' => 'NOT EXISTS'
            )
        );
        $query->set('meta_query', $meta_query);
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'kgs_sort_columns');



// --------------------
// Year-month extraction helper
// --------------------
function kgs_extract_year_month_from_filename($filename) {
    $name = strtolower($filename);
    $month_map = [
        "january" => "01", "jan" => "01",
        "february" => "02", "feb" => "02",
        "march" => "03", "mar" => "03",
        "april" => "04", "apr" => "04",
        "may" => "05",
        "june" => "06", "jun" => "06",
        "july" => "07", "jul" => "07",
        "august" => "08", "aug" => "08",
        "september" => "09", "sep" => "09", "sept" => "09",
        "october" => "10", "oct" => "10",
        "november" => "11", "nov" => "11",
        "december" => "12", "dec" => "12",
    ];

    // Try YYYY-MM or YYYY_MM or YYYY MM format
    if (preg_match('/(20\d{2})[-_ ]?(0?[1-9]|1[0-2])/', $name, $matches)) {
        $year = $matches[1];
        $month = str_pad($matches[2], 2, "0", STR_PAD_LEFT);
        return "$year-$month";
    }

    // Try MM-YYYY or MM_YYYY or MM YYYY format
    if (preg_match('/(0?[1-9]|1[0-2])[-_ ]?(20\d{2})/', $name, $matches)) {
        $month = str_pad($matches[1], 2, "0", STR_PAD_LEFT);
        $year = $matches[2];
        return "$year-$month";
    }

    // Try YYYYMM format
    if (preg_match('/(20\d{2})(0[1-9]|1[0-2])/', $name, $matches)) {
        return $matches[1] . '-' . $matches[2];
    }

    // Try matching month name and 4-digit year
    foreach ($month_map as $key => $mnum) {
        if (strpos($name, $key) !== false) {
            if (preg_match('/20\d{2}/', $name, $year_match)) {
                return $year_match[0] . '-' . $mnum;
            }
        }
    }

    // Try matching month name + 2-digit year (like Sep25)
    if (preg_match('/(jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*[-_ ]?\'?(\d{2})/', $name, $matches)) {
        $month = $month_map[$matches[1]];
        $year = '20' . $matches[2];
        return "$year-$month";
    }

    return false; // no valid date found
}

// --------------------
// Clear PDF metadata helper
// --------------------
function kgs_clear_pdf_metadata($imagick) {
    $props = ['pdf:Title', 'pdf:Author', 'pdf:Subject', 'pdf:Keywords', 'pdf:Creator', 'pdf:Producer'];
    foreach ($props as $prop) {
        $imagick->setImageProperty($prop, '');
    }
}

// --------------------
// Image to PDF conversion for calendars (academic + monthly)
// --------------------
function kgs_convert_calendar_images_to_pdf() {
    if (!class_exists('Imagick')) {
        error_log("Imagick class not available. Image to PDF conversion disabled.");
        return;
    }

    $upload_dir = wp_upload_dir();

    // Define calendar subfolders and their PDF prefixes
    $folders = [
        'academic-calendar' => 'academic-calendar-',
        'monthly-calendar'  => 'monthly-calendar-',
    ];

    foreach ($folders as $subfolder => $prefix) {
        $calendar_dir = trailingslashit($upload_dir['basedir']) . "documents/public/calendar/$subfolder/";

        if (!file_exists($calendar_dir)) {
            error_log("Calendar directory not found: $calendar_dir");
            continue;
        }

        $image_files = glob($calendar_dir . '*.{jpg,jpeg,png}', GLOB_BRACE);

        foreach ($image_files as $image_path) {
            $filename = basename($image_path);

            if ($subfolder === 'academic-calendar') {
                // Match year or year range like 2025 or 2025-2026
                if (preg_match('/\b(20\d{2})(-(20\d{2}))?\b/', $filename, $matches)) {
                    if (!empty($matches[3])) {
                        $pdf_filename = $prefix . $matches[1] . '-' . $matches[3] . '.pdf';
                    } else {
                        $pdf_filename = $prefix . $matches[1] . '.pdf';
                    }
                } else {
                    // fallback filename with timestamp
                    $pdf_filename = $prefix . time() . '.pdf';
                }
            } else {
                // monthly-calendar uses year-month extraction helper
                $year_month = kgs_extract_year_month_from_filename($filename);
                if ($year_month === false) {
                    error_log("Skipping file without date in name: $filename in folder $subfolder");
                    continue;
                }
                $pdf_filename = $prefix . $year_month . '.pdf';
            }

            $pdf_path = $calendar_dir . $pdf_filename;

            if (file_exists($pdf_path)) {
                unlink($image_path); // delete original if PDF exists
                continue;
            }

            try {
                $imagick = new Imagick($image_path);
                $imagick->setImageFormat('pdf');

                kgs_clear_pdf_metadata($imagick);

                $imagick->writeImage($pdf_path);
                $imagick->clear();
                $imagick->destroy();

                unlink($image_path);
                error_log("Converted and removed $filename to $pdf_filename");
            } catch (Exception $e) {
                error_log('Image-to-PDF conversion error: ' . $e->getMessage());
            }
        }
    }
}

// --------------------
// Shortcode: Latest Academic Calendar PDF (embedded object)
// --------------------
function kgs_latest_academic_calendar_shortcode() {
    $upload_dir = wp_upload_dir();
    $calendar_dir = trailingslashit($upload_dir['basedir']) . 'documents/public/calendar/academic-calendar/';
    $calendar_url = trailingslashit($upload_dir['baseurl']) . 'documents/public/calendar/academic-calendar/';

    $files = glob($calendar_dir . '*.pdf');
    if (!$files) {
        return '<p>No academic calendar PDF found.</p>';
    }

    $files = array_map('realpath', $files);
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latest = sanitize_file_name(basename($files[0]));
    $file_url = esc_url($calendar_url . $latest);

    ob_start();
    ?>
    <object class="pdf-object" data="<?php echo $file_url; ?>#zoom=page-fit" type="application/pdf" width="100%" height="600px">
        <p>Your browser does not support PDFs. <a href="<?php echo $file_url; ?>">Download the Academic Calendar</a></p>
    </object>
    <?php
    return ob_get_clean();
}
add_shortcode('latest_academic_calendar_pdf', 'kgs_latest_academic_calendar_shortcode');

// --------------------
// Shortcode: Latest Monthly Calendar PDF (embedded object)
// --------------------
function kgs_show_latest_monthly_calendar_pdf() {
    $upload_dir = wp_upload_dir();
    $calendar_dir = trailingslashit($upload_dir['basedir']) . 'documents/public/calendar/monthly-calendar/';
    $calendar_url = trailingslashit($upload_dir['baseurl']) . 'documents/public/calendar/monthly-calendar/';

    $files = glob($calendar_dir . '*.pdf');
    if (!$files) {
        return '<p>No monthly calendar PDF found.</p>';
    }

    $files = array_map('realpath', $files);
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latest_file = sanitize_file_name(basename($files[0]));
    $file_url = esc_url($calendar_url . $latest_file);

    ob_start();
    ?>
    <object class="pdf-object" data="<?php echo $file_url; ?>#view=FitV" type="application/pdf" width="100%" height="800px">
        <p>Your browser does not support PDFs. <a href="<?php echo $file_url; ?>">Download the Monthly Calendar</a></p>
    </object>
    <?php
    return ob_get_clean();
}
add_shortcode('latest_monthly_calendar', 'kgs_show_latest_monthly_calendar_pdf');

// --------------------
// Image to PDF conversion + get latest PDFs for cafeteria menus
// --------------------
function kgs_convert_cafeteria_images_to_pdf_and_get_latest() {
    $upload_dir = wp_upload_dir();

    $folders = [
        'lunch-menu' => 'lunch-menu-',
        'breakfast-menu' => 'breakfast-menu-',
    ];

    $base_dir = trailingslashit($upload_dir['basedir']) . 'documents/public/cafeteria/';

    $latest_pdfs = [];

    foreach ($folders as $folder => $prefix) {
        $folder_path = $base_dir . $folder . '/';

        if (!file_exists($folder_path)) {
            error_log("Cafeteria folder not found: $folder_path");
            continue;
        }

        // Step 1: Convert images to PDFs if missing
        $image_files = glob($folder_path . '*.{jpg,jpeg,png}', GLOB_BRACE);

        foreach ($image_files as $image_path) {
            $filename = basename($image_path);

            $year_month = kgs_extract_year_month_from_filename($filename);
            if ($year_month === false) {
                error_log("Skipping image without date in name: $filename");
                continue;
            }

            $pdf_filename = $prefix . $year_month . '.pdf';
            $pdf_path = $folder_path . $pdf_filename;

            if (file_exists($pdf_path)) {
                // PDF already exists; delete original image
                unlink($image_path);
                continue;
            }

            try {
                if (class_exists('Imagick')) {
                    $imagick = new Imagick($image_path);
                    $imagick->setImageFormat('pdf');

                    kgs_clear_pdf_metadata($imagick);

                    $imagick->writeImage($pdf_path);
                    $imagick->clear();
                    $imagick->destroy();

                    unlink($image_path);
                    error_log("Converted $filename to $pdf_filename");
                } else {
                    error_log("Imagick class not available. Cannot convert $filename.");
                }
            } catch (Exception $e) {
                error_log('Image-to-PDF conversion error: ' . $e->getMessage());
            }
        }

        // Step 2: Find latest PDF by date
        $pdf_files = glob($folder_path . $prefix . '*.pdf');
        $latest_date = '0000-00';
        $latest_file = '';

        foreach ($pdf_files as $pdf) {
            $pdf_name = basename($pdf);
            $year_month = kgs_extract_year_month_from_filename($pdf_name);
            if ($year_month !== false && $year_month > $latest_date) {
                $latest_date = $year_month;
                $latest_file = $pdf;
            }
        }

        if ($latest_file) {
            $latest_pdfs[$folder] = str_replace(
                $upload_dir['basedir'], 
                $upload_dir['baseurl'], 
                $latest_file
            );
        } else {
            $latest_pdfs[$folder] = false;
        }
    }

    return $latest_pdfs;
}

// --------------------
// Shortcode: Latest Cafeteria Calendar (Lunch and Breakfast PDFs embedded)
// --------------------
function kgs_latest_cafeteria_calendar_shortcode() {
    $latest_pdfs = kgs_convert_cafeteria_images_to_pdf_and_get_latest();

    ob_start();
    ?>
    <div class="cafeteria-calendars">
        <?php if (!empty($latest_pdfs['lunch-menu'])): ?>
            <section class="cafeteria-lunch-menu">
                <h3>Latest Lunch Menu</h3>
                <object class="pdf-object" data="<?php echo esc_url($latest_pdfs['lunch-menu']); ?>#view=FitV" type="application/pdf" width="100%" height="600px">
                    <p>Your browser does not support PDFs. <a href="<?php echo esc_url($latest_pdfs['lunch-menu']); ?>">Download the Lunch Menu PDF</a></p>
                </object>
            </section>
        <?php else: ?>
            <p>No lunch menu PDF found.</p>
        <?php endif; ?>

        <?php if (!empty($latest_pdfs['breakfast-menu'])): ?>
            <section class="cafeteria-breakfast-menu">
                <h3>Latest Breakfast Menu</h3>
                <object class="pdf-object" data="<?php echo esc_url($latest_pdfs['breakfast-menu']); ?>#view=FitV" type="application/pdf" width="100%" height="600px">
                    <p>Your browser does not support PDFs. <a href="<?php echo esc_url($latest_pdfs['breakfast-menu']); ?>">Download the Breakfast Menu PDF</a></p>
                </object>
            </section>
        <?php else: ?>
            <p>No breakfast menu PDF found.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('latest_cafeteria_calendar', 'kgs_latest_cafeteria_calendar_shortcode');



// Disable multiple image file creation by WP
	function kgsweb_disable_intermediate_image_sizes($sizes) {
		return [];
	}
	add_filter('intermediate_image_sizes_advanced', 'kgsweb_disable_intermediate_image_sizes');




// END CUSTOM CHILD FUNCTIONS