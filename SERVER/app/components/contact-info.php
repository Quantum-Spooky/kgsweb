<?php
/**
 * UI COMPONENT: Contact Info
 */

// 1. Explicitly initialize variables with defaults to prevent "Undefined variable" errors.
// We pull from $data first (passed by renderer), then config() as a fallback.
$schoolname = $data['school_name'] ?? config('site_name', 'Kell Grade School');
$address    = $data['address'] ?? config('address', '');
$phone      = $data['phone']   ?? config('phone', '');
$fax        = $data['fax']     ?? config('fax', '');
$email      = $data['email']   ?? config('email', '');

// 2. EARLY EXIT: If we don't have the basics, hide the component silently.
if (empty($address) && empty($phone)) {
    echo "<!-- Contact Info Hidden: No address / phone data set in config -->";
    return;
}


?>

<div class="kgs-contact-widget">
    <h5 class="text-accent fw-bold"><?= htmlspecialchars($schoolname) ?></h5>
    
    <?php if (!empty($address)): ?>
        <p class="small mb-1"><?= htmlspecialchars($address) ?></p>
    <?php endif; ?>

    <?php if (!empty($phone)): ?>
        <p class="small mb-1"><strong>Phone:</strong> <?= htmlspecialchars($phone) ?></p>
    <?php endif; ?>

    <?php if (!empty($fax)): ?>
        <p class="small mb-1"><strong>Fax:</strong> <?= htmlspecialchars($fax) ?></p>
    <?php endif; ?>

    <?php if (!empty($email)): ?>
        <p class="small">
            <strong>Email:</strong> <a href="mailto:<?= $email ?>" class="text-white text-decoration-underline"><?= htmlspecialchars($email) ?></a>
        </p>
    <?php endif; ?>
</div>