<?php
// includes/class-kgsweb-google-shortcodes.php
if (!defined('ABSPATH')) { exit; }

class KGSweb_Google_Shortcodes {

    private static ?self $instance = null;

    /*******************************
     * Singleton init
     *******************************/
    public static function init(): self {
        if (self::$instance === null) {
            self::$instance = new self();
            add_action('init', [self::$instance, 'register_shortcodes']);
        }
        return self::$instance;
    }

    /*******************************
     * Register all shortcodes
     *******************************/
    public function register_shortcodes() {
        add_shortcode('kgsweb_documents', [$this, 'documents']);
        add_shortcode('kgsweb_menu', [KGSweb_Google_Menus::class, 'shortcode_render']);
        add_shortcode('kgsweb_slides', [KGSweb_Google_Slides::class, 'shortcode_render']);
        add_shortcode('kgsweb_events', [__CLASS__, 'kgsweb_events_shortcode']);
        add_shortcode('kgsweb_calendar', [__CLASS__, 'kgsweb_events_shortcode']);
    }

    /*******************************
     * Asset registration
     *******************************/
    public static function enqueue_if_needed($handles = []) {
        wp_enqueue_style('kgsweb-style');
        foreach ((array)$handles as $h) wp_enqueue_script($h);
    }

    public static function register_assets() {
        $ver = '0.1.0';
        $settings = KGSweb_Google_Integration::get_settings();

        wp_register_style(
            'kgsweb-style',
            plugins_url('/css/kgsweb-style.css', KGSWEB_PLUGIN_FILE),
            [],
            $ver
        );

        wp_register_script(
            'kgsweb-helpers',
            plugins_url('/js/kgsweb-helpers.js', KGSWEB_PLUGIN_FILE),
            [],
            $ver,
            true
        );

        wp_register_script(
            'kgsweb-folders',
            plugins_url('/js/kgsweb-folders.js', KGSWEB_PLUGIN_FILE),
            ['kgsweb-helpers'],
            $ver,
            true
        );

        wp_register_script(
            'kgsweb-ticker',
            plugins_url('/js/kgsweb-ticker.js', KGSWEB_PLUGIN_FILE),
            [],
            $ver,
            true
        );

        wp_localize_script('kgsweb-folders', 'KGSwebFolders', [
            'restUrl' => esc_url_raw(rest_url('kgsweb/v1/documents')),
            'rootId'  => $settings['public_docs_root_id'] ?? '',
        ]);
    }

    public static function enqueue_documents_assets() {
        wp_enqueue_style('kgsweb-style');
        wp_enqueue_script('kgsweb-folders');
    }

    /*******************************
     * Documents shortcode
     *******************************/
   public static function documents($atts = []) {
    $atts = shortcode_atts([
        'doc-folder'    => '',
        'folder'        => '',
        'folders'       => '',
        'class'         => '',
        'id'            => 'kgsweb-documents-tree',
        'sort_mode'     => 'alpha-asc',
        'folders_first' => 'true',
        'collapsed'     => 'true',
        'expanded'      => null,
        'search'        => 'false',
    ], $atts, 'kgsweb_documents');

    wp_enqueue_style('kgsweb-style');

    $settings = KGSweb_Google_Integration::get_settings();
    $root = $atts['doc-folder'] ?: $atts['folder'] ?: $atts['folders'] ?: ($settings['public_docs_root_id'] ?? '');
    if (!$root) {
        return '<p>No root folder configured for documents.</p>';
    }

    $id = preg_replace('/[^a-zA-Z0-9\-\_]/', '-', $atts['id']);
    $class = esc_attr($atts['class']);
    $folders_first = filter_var($atts['folders_first'], FILTER_VALIDATE_BOOLEAN);
    $collapsed_attr = filter_var($atts['collapsed'], FILTER_VALIDATE_BOOLEAN);
    $expanded_attr = $atts['expanded'] !== null ? filter_var($atts['expanded'], FILTER_VALIDATE_BOOLEAN) : null;
    $expanded = $expanded_attr !== null ? $expanded_attr : ! $collapsed_attr;
    $sort_mode = in_array(strtolower($atts['sort_mode']), ['alpha-asc','alpha-desc','date-asc','date-desc'])
        ? strtolower($atts['sort_mode'])
        : 'alpha-asc';
    $show_search = filter_var($atts['search'], FILTER_VALIDATE_BOOLEAN);

    // Get the documents tree payload **once, server-side**
    $payload = KGSweb_Google_Drive_Docs::get_documents_tree_payload($root);
    if (is_wp_error($payload)) {
        error_log('KGSWEB: get_documents_tree_payload error: ' . $payload->get_error_message());
        return '<p>Unable to load documents at this time.</p>';
    }

    $tree = $payload['tree'] ?? [];

    $render_node = function($node, $depth = 0) use (&$render_node, $folders_first, $sort_mode, $expanded) {
        $html = '';
        $type = $node['type'] ?? 'file';

        if ($type === 'folder') {
            $children = $node['children'] ?? [];

            if ($folders_first || str_starts_with($sort_mode, 'alpha') || str_starts_with($sort_mode, 'date')) {
                usort($children, function($a, $b) use ($sort_mode, $folders_first) {
                    if ($folders_first) {
                        if (($a['type'] ?? '') === 'folder' && ($b['type'] ?? '') !== 'folder') return -1;
                        if (($a['type'] ?? '') !== 'folder' && ($b['type'] ?? '') === 'folder') return 1;
                    }
                    $valA = $a['name'] ?? '';
                    $valB = $b['name'] ?? '';
                    if (str_starts_with($sort_mode, 'date')) {
                        $dateA = preg_replace('/[^0-9]/', '', $valA);
                        $dateB = preg_replace('/[^0-9]/', '', $valB);
                        $valA = $dateA ?: '99999999';
                        $valB = $dateB ?: '99999999';
                    } else {
                        $valA = strtolower($valA);
                        $valB = strtolower($valB);
                    }
                    $cmp = strcmp($valA, $valB);
                    if (str_ends_with($sort_mode, '-desc')) $cmp *= -1;
                    return $cmp;
                });
            }

            $folder_name = esc_html($node['name'] ?: 'Folder');
            $html .= '<li class="kgsweb-node type-folder depth-' . intval($depth) . '">';
            $html .= '<span class="kgsweb-toggle" role="button" tabindex="0" aria-expanded="' . ($expanded ? 'true' : 'false') . '">';
            $html .= '<span class="kgsweb-icon"><i class="fa fa-folder"></i><i class="fa fa-folder-open" style="display:none"></i></span>';
            $html .= '<span class="kgsweb-label">' . $folder_name . '</span>';
            $html .= '<i class="fa fa-chevron-right kgsweb-toggle-icon"></i>';
            $html .= '</span>';

            if (!empty($children)) {
                $html .= '<ul class="kgsweb-children"' . ($expanded ? '' : ' hidden')
                    . ' data-sort-mode="' . esc_attr($sort_mode) . '"'
                    . ' data-folders-first="' . ($folders_first ? 'true' : 'false') . '">';
                foreach ($children as $child) {
                    $html .= $render_node($child, $depth + 1);
                }
                $html .= '</ul>';
            }

            $html .= '</li>';
        } else {
            $file_name = $node['name'] ?? 'File';
            $file_id = $node['id'] ?? '';
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $icon_class = 'fa-file';
            switch($ext) {
                case 'pdf': $icon_class = 'fa-file-pdf'; break;
                case 'doc': case 'docx': $icon_class = 'fa-file-word'; break;
                case 'xls': case 'xlsx': $icon_class = 'fa-file-excel'; break;
                case 'ppt': case 'pptx': $icon_class = 'fa-file-powerpoint'; break;
                case 'jpg': case 'jpeg': case 'png': $icon_class = 'fa-file-image'; break;
            }
            $url = esc_url("https://drive.google.com/file/d/{$file_id}/view");
            $html .= '<li class="kgsweb-node type-file depth-' . intval($depth) . '">';
            $html .= '<a class="kgsweb-file" href="' . $url . '" target="_blank" rel="noopener">';
            $html .= '<span class="kgsweb-icon"><i class="fa ' . esc_attr($icon_class) . '"></i></span>';
            $html .= '<span class="kgsweb-label">' . esc_html($file_name) . '</span>';
            $html .= '</a>';
            $html .= '</li>';
        }

        return $html;
    };

    $tree_html = '<ul class="kgsweb-tree"'
        . ' data-sort-mode="' . esc_attr($sort_mode) . '"'
        . ' data-folders-first="' . ($folders_first ? 'true' : 'false') . '"'
        . ' data-expanded="' . ($expanded ? 'true' : 'false') . '">';
    foreach ($tree as $node) {
        $tree_html .= $render_node($node);
    }
    $tree_html .= '</ul>';

    ob_start(); ?>
    <div
        id="<?php echo esc_attr($id); ?>"
        class="kgsweb-documents <?php echo $class; ?>"
        data-root-id="<?php echo esc_attr($root); ?>"
        data-sort-mode="<?php echo esc_attr($sort_mode); ?>"
        data-folders-first="<?php echo ($folders_first ? 'true' : 'false'); ?>"
        data-expanded="<?php echo ($expanded ? 'true' : 'false'); ?>"
    >
        <?php if ($show_search) : ?>
            <input type="search" class="kgsweb-doc-search" placeholder="<?php echo esc_attr__('Search documents...', 'kgsweb'); ?>" />
        <?php endif; ?>

        <?php echo $tree_html; ?>

        <noscript>JavaScript is required for folder toggling.</noscript>
    </div>
    <?php
    return ob_get_clean();
}


    /*******************************
     * Events shortcode
     *******************************/
    public static function kgsweb_events_shortcode($atts) {
        $atts = shortcode_atts([
            'calendar_id' => get_option('kgsweb_default_calendar_id', ''),
        ], $atts, 'kgsweb_events');

        $integration = KGSweb_Google_Integration::init();
        $events = $integration->get_cached_events($atts['calendar_id']);
        $calendar_url = get_option('kgsweb_calendar_url', '');
        if (empty($calendar_url) && !empty($atts['calendar_id'])) {
            $calendar_url = 'https://calendar.google.com/calendar/embed?src=' . urlencode($atts['calendar_id']);
        }

        wp_enqueue_script('kgsweb-calendar');
        wp_localize_script('kgsweb-calendar', 'kgswebCalendarData', [
            'events' => array_values($events),
        ]);

        ob_start(); ?>
        <div class="kgsweb-calendar" data-calendar-id="<?php echo esc_attr($atts['calendar_id']); ?>">
            <ul class="events-list"></ul>
            <div class="calendar-controls">
                <button class="prev">&laquo; Prev</button>
                <?php if (!empty($calendar_url)) : ?>
                    <div class="calendar-link">
                        <a href="<?php echo esc_url($calendar_url); ?>" target="_top" rel="noopener">
                            View Calendar
                        </a>
                    </div>
                <?php endif; ?>
                <button class="next">Next &raquo;</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /*******************************
     * Secure Upload Form
     *******************************/
    public static function secure_upload_form($atts) {
        $a = shortcode_atts([
            'upload-folder' => '',
            'folders'       => '',
            'folder'        => '',
        ], $atts, 'kgsweb_secure_upload_form');

        $settings = KGSweb_Google_Integration::get_settings();
        $root = $a['upload-folder'] ?? $a['folders'] ?? $a['folder'] ?? ($settings['upload_root_id'] ?? '');

        self::enqueue_if_needed(['kgsweb-helpers', 'kgsweb-upload']);

        ob_start(); ?>
        <div class="kgsweb-upload" data-upload-folder="<?php echo esc_attr($root); ?>">
            <div class="kgsweb-upload-gate"></div>
            <form class="kgsweb-upload-form" method="post" enctype="multipart/form-data" hidden>
                <label><?php esc_html_e('Destination Folder', 'kgsweb'); ?>
                    <select name="folder_id" class="kgsweb-upload-dest"></select>
                </label>
                <label><?php esc_html_e('File', 'kgsweb'); ?>
                    <input type="file" name="file" required />
                </label>
                <button type="submit"><?php esc_html_e('Upload', 'kgsweb'); ?></button>
            </form>
            <div class="kgsweb-upload-status" aria-live="polite"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /*******************************
     * Sheets shortcode
     *******************************/
    public static function sheets($atts) {
        $a = shortcode_atts([
            'sheet_id' => '',
            'range'    => '',
        ], $atts, 'kgsweb_sheets');

        $id = $a['sheet_id'] ?: (KGSweb_Google_Integration::get_settings()['sheets_file_id'] ?? '');
        $range = $a['range'] ?: (KGSweb_Google_Integration::get_settings()['sheets_default_range'] ?? 'A1:Z100');

        return '<div class="kgsweb-sheets" data-sheet-id="' . esc_attr($id) . '" data-range="' . esc_attr($range) . '"></div>';
    }

    /*******************************
     * Placeholder methods for other shortcodes
     *******************************/
    public static function events($atts) { /* ... */ }
    public static function menu($atts) { /* ... */ }
    public static function slides($atts) { /* ... */ }

}
