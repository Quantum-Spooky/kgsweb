<?php
/**
 * UI COMPONENT: Rich Text
 */
$content = $data['content'] ?? config('home_welcome_text', '');
$title   = $data['title']   ?? '';
$align   = $data['align']   ?? 'left';

if (empty(trim(strip_tags($content)))) return;

// Smart Auto-Paragraphing
if ($content === strip_tags($content, '<a><b><i><strong><em>')) {
    $content = '<p>' . str_replace("\n\n", '</p><p>', $content) . '</p>';
    $content = nl2br($content);
}

$textAlignClass = match ($align) {
    'center' => 'text-center',
    'right'  => 'text-end',
    default  => 'text-start',
};
?>
<div class="kgs-rich-text-block <?= $textAlignClass ?>">
    <?php if (!empty($title)): ?>
        <h2 class="rich-text-title"><?= htmlspecialchars($title) ?></h2>
    <?php endif; ?>

    <div class="rich-text-body">
        <?= $content ?>
    </div>
</div>