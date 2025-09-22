<?php
// includes/class-kgsweb-google-helpers.php
if (!defined('ABSPATH')) exit;

use Google\Client;
use Google\Service\Drive;

class KGSweb_Google_Helpers {

    public static function init() { /* no-op */ }

    // -----------------------------
    // Folder / File Name Formatting
    // -----------------------------
    public static function format_folder_name(string $name): string {
        $name = preg_replace('/[-_]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return ucwords(trim($name));
    }

    public static function extract_date(string $name): ?string {
        if (preg_match('/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/', $name, $m)) {
            return $m[1] . $m[2] . $m[3];
        }
        return null;
    }

    public static function sanitize_file_name(string $filename): string {
        if (!$filename) return '';
        $sanitized = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);
        if (!$sanitized) {
            error_log("[KGSweb] WARNING: sanitize_file_name() returned empty for filename: $filename");
            $sanitized = 'file-' . time();
        }
        return $sanitized;
    }

    public static function sort_items(array &$items, string $sort_by): void {
        usort($items, function($a, $b) use ($sort_by) {
            $isFolderA = ($a['type'] ?? $a['mimeType'] ?? '') === 'folder';
            $isFolderB = ($b['type'] ?? $b['mimeType'] ?? '') === 'folder';

            if ($isFolderA && !$isFolderB) return -1;
            if (!$isFolderA && $isFolderB) return 1;

            $cmp = strcasecmp($a['name'], $b['name']);
            switch ($sort_by) {
                case 'alpha-desc':
                    return -$cmp;
                case 'date-asc':
                    return strcmp($a['modifiedTime'], $b['modifiedTime']);
                case 'date-desc':
                    return strcmp($b['modifiedTime'], $a['modifiedTime']);
                default:
                    $dateA = self::extract_date($a['name'] ?? '') ?? '99999999';
                    $dateB = self::extract_date($b['name'] ?? '') ?? '99999999';
                    if ($dateA !== $dateB) return strcmp($dateB, $dateA);
                    return $cmp;
            }
        });
    }

    // -----------------------------
    // Google Drive Files / Folders
    // -----------------------------
    public static function list_drive_files(Drive $service, string $parent_id): array {
        $files = [];
        $pageToken = null;
        do {
            $params = [
                'q' => sprintf("'%s' in parents and trashed = false", esc_sql($parent_id)),
                'fields' => 'nextPageToken, files(id, name, mimeType, modifiedTime, size, parents)',
                'pageSize' => 1000,
            ];
            if ($pageToken) $params['pageToken'] = $pageToken;

            $response = $service->files->listFiles($params);
            foreach ($response->getFiles() as $file) {
                $files[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mimeType' => $file->getMimeType(),
                    'modifiedTime' => $file->getModifiedTime(),
                    'size' => $file->getSize(),
                    'parents' => $file->getParents(),
                ];
            }
            $pageToken = $response->getNextPageToken();
        } while ($pageToken);

        return $files;
    }

    public static function drive_list_children_raw(string $folder_id): array {
        try {
            $client = KGSweb_Google_Integration::get_google_client();
            if (!$client) throw new Exception('Google Client not available.');

            $service = new Drive($client);
            $files = [];
            $pageToken = null;

            do {
                $params = [
                    'q' => sprintf("'%s' in parents and trashed = false", $folder_id),
                    'fields' => 'nextPageToken, files(id, name, mimeType, modifiedTime, size, parents)',
                    'pageSize' => 1000,
                ];
                if ($pageToken) $params['pageToken'] = $pageToken;

                $response = $service->files->listFiles($params);
                foreach ($response->getFiles() as $file) {
                    $files[] = [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'mimeType' => $file->getMimeType(),
                        'modifiedTime' => $file->getModifiedTime(),
                        'size' => $file->getSize(),
                        'parents' => $file->getParents(),
                    ];
                }
                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

            return $files;

        } catch (Exception $e) {
            error_log("KGSWEB ERROR: Failed to list children for folder {$folder_id} - " . $e->getMessage());
            return [];
        }
    }

    public static function filter_empty_folders(array &$tree): void {
        $tree = array_filter($tree, function($item) {
            if (($item['mimeType'] ?? '') === 'application/vnd.google-apps.folder') {
                if (!empty($item['children'])) {
                    self::filter_empty_folders($item['children']);
                    return !empty($item['children']);
                }
                return false;
            }
            return true;
        });
    }

    public static function build_tree_recursive(Drive $service, string $parent_id, string $sort_by = ''): array {
        $items = self::list_drive_files($service, $parent_id);
        $tree = [];
        foreach ($items as $file) {
            $node = [
                'id' => $file['id'],
                'name' => $file['name'],
                'mimeType' => $file['mimeType'],
                'modifiedTime' => $file['modifiedTime'],
                'children' => [],
            ];
            if ($node['mimeType'] === 'application/vnd.google-apps.folder') {
                $node['children'] = self::build_tree_recursive($service, $node['id'], $sort_by);
            }
            $tree[] = $node;
        }
        self::sort_items($tree, $sort_by);
        return $tree;
    }

    public static function render_tree_html(array $tree, string $collapsed = 'false'): string {
        $html = '<ul class="kgsweb-documents">';
        foreach ($tree as $item) {
            $is_folder = ($item['mimeType'] ?? '') === 'application/vnd.google-apps.folder';
            $toggle_class = ($collapsed === 'false-static') ? 'no-toggle' : 'toggle';
            $html .= '<li data-id="' . esc_attr($item['id']) . '">';
            if ($is_folder) {
                $html .= '<span class="folder ' . $toggle_class . '">' . esc_html($item['name']) . '</span>';
                if (!empty($item['children'])) {
                    $html .= self::render_tree_html($item['children'], $collapsed);
                }
            } else {
                $html .= '<a class="file" href="https://drive.google.com/file/d/' . esc_attr($item['id']) . '/view" target="_blank">' . esc_html($item['name']) . '</a>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    // -----------------------------
    // Icon Selection
    // -----------------------------
    public static function icon_for_mime_or_ext(?string $mime, ?string $ext): string {
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
    // Fetch raw file contents
    // -----------------------------
    public static function fetch_file_contents_raw(string $file_id): ?string {
        try {
            $driveService = KGSweb_Google_Integration::get_drive_service();
            if (!$driveService) {
                error_log("[KGSweb] fetch_file_contents_raw: Drive service not initialized.");
                return null;
            }
            $response = $driveService->files->get($file_id, ['alt' => 'media']);
            $body = $response->getBody();
            return $body ? (string)$body->getContents() : null;
        } catch (Exception $e) {
            error_log("[KGSweb] fetch_file_contents_raw ERROR for $file_id: " . $e->getMessage());
            return null;
        }
    }

    // -----------------------------
    // Cached file helpers
    // -----------------------------
    public static function get_cached_file_url(string $path): string {
        $upload_dir = wp_upload_dir();
        return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $path);
    }

    public static function convert_pdf_to_png_cached($pdf_path, $filename = null) {
        $upload_dir = wp_upload_dir();
        $cache_dir  = $upload_dir['basedir'] . '/kgsweb-cache/';
        if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);

        $basename = $filename ? self::sanitize_file_name($filename) : basename($pdf_path);
        $png_path = $cache_dir . pathinfo($basename, PATHINFO_FILENAME) . '.png';
        $meta_path = $png_path . '.meta.json';

        if (file_exists($png_path)) {
            if (!file_exists($meta_path)) {
                [$width, $height] = getimagesize($png_path);
                file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
            }
            return $png_path;
        }

        if (!file_exists($pdf_path)) return false;
        if (!class_exists('Imagick')) return false;

        try {
            $img = new Imagick();
            $img->setResolution(150, 150);
            $img->readImage($pdf_path . '[0]');
            $img->setImageFormat('png');
            if ($img->getImageWidth() > 1000) {
                $img->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1);
            }
            $img->setImageCompression(Imagick::COMPRESSION_JPEG);
            $img->setImageCompressionQuality(85);
            $img->stripImage();
            $img->writeImage($png_path);
            [$width, $height] = getimagesize($png_path);
            file_put_contents($meta_path, json_encode(['width' => $width, 'height' => $height]));
            $img->clear();
            $img->destroy();
            return $png_path;
        } catch (Exception $e) {
            error_log("[KGSweb] PDF to PNG conversion failed for $pdf_path: " . $e->getMessage());
            return false;
        }
    }

    // -----------------------------
    // Deprecated / legacy functions
    // -----------------------------
    /*
    public static function old_drive_fetch_method() {
        // ORIGINAL: used to fetch files with a simpler API call, replaced by drive_list_children_raw()
    }

    public static function legacy_sort_folders() {
        // ORIGINAL: handled folder sorting differently, now replaced by sort_items()
    }

    public static function old_pdf_helper() {
        // ORIGINAL: older Imagick PDF conversion, replaced by convert_pdf_to_png_cached()
    }
    */
}
