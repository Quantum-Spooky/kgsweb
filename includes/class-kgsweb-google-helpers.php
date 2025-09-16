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

    public static function sanitize_file_name($name) {
        $base = preg_replace('/\.[^.]+$/', '', $name); // remove extension
        $base = preg_replace('/^school[\s-_]*board[\s-_]*/i', '', $base);
        $base = preg_replace('/[-_]+/', ' ', $base);

        if (preg_match('/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/', $base, $m)) {
            $base = str_replace($m[0], sprintf('%s/%s/%s', $m[2], $m[3], $m[1]), $base);
        }

        return ucwords(trim($base));
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
}
