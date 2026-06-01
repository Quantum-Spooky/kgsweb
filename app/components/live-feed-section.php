<?php
/**
 * UI COMPONENT
 *
 * Responsibility:
 * - Render HTML for a single component type
 * - Use only provided $data array
 *
 * Rules:
 * - MUST NOT access CMS
 * - MUST NOT access cache
 * - MUST NOT perform routing or data fetching
 *
 * This file is PURE PRESENTATION LAYER.
 */
 
 
/**
 * app/components/live-feed-section.php
 * Live Feed Section Component
 */

$limit = isset($limit) ? (int)$limit : 5;
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-success text-white fw-bold">
        Latest Announcements
    </div>

    <div class="card-body">
        <?php
        /*
        |----------------------------------------------------------------------
        | SOURCE DATA
        |----------------------------------------------------------------------
        | Uses $live_posts from config.php (fallback data source)
        */

        if (isset($live_posts) && is_array($live_posts)) {

            $posts = array_slice($live_posts, 0, $limit);

            foreach ($posts as $post) {

                $date = htmlspecialchars($post['date'] ?? '');
                $text = htmlspecialchars($post['text'] ?? '');

                echo '<div class="mb-3">';
                echo '<div class="small text-muted">' . $date . '</div>';
                echo '<div>' . $text . '</div>';
                echo '</div>';
            }

        } else {
            echo '<p class="text-muted">No announcements available.</p>';
        }
        ?>
    </div>
</div>