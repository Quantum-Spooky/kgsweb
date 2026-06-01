<?php

/**
 * LAYOUT TEMPLATE
 *
 * Responsibility:
 * - Define page structure (header/body/footer slots)
 * - Render passed meta + components output
 *
 * Rules:
 * - MUST NOT load CMS data
 * - MUST NOT query cache
 *
 * Layout only formats already-prepared page data.
 */
/**
 * footer.php
 * Site Footer (layout component)
 */

$siteName = config('site_name', 'School Site');
$district = config('district_name', '');

$address  = config('address', '');
$phone    = config('phone', '');
$email    = config('email', '');

$baseUrl    = config('base_url', '/');
$reportCard = config('report_card_url', '#');

$safe2help = config('safe2help_url', 'https://safe2helpil.com');
$lifeline  = config('lifeline_url', 'https://988lifeline.org');
$able      = config('able_url', '#');
?>

</main>

<footer class="bg-dark text-white py-5 mt-auto">
    <div class="container">
        <div class="row">

            <div class="col-md-4 mb-4">
                <h5><?= htmlspecialchars($siteName) ?></h5>
                <p class="mb-1"><?= htmlspecialchars($address) ?></p>
                <p class="mb-1">Phone: <?= htmlspecialchars($phone) ?></p>
                <p class="mb-1">
                    Email:
                    <a href="mailto:<?= htmlspecialchars($email) ?>" class="text-white">
                        <?= htmlspecialchars($email) ?>
                    </a>
                </p>
            </div>

            <div class="col-md-4 mb-4">
                <h5>Quick Links</h5>

                <a href="<?= $baseUrl ?>about/staff-directory/" class="text-white d-block mb-1">Staff Directory</a>
                <a href="<?= $baseUrl ?>dining/" class="text-white d-block mb-1">Lunch Menu</a>
                <a href="<?= $baseUrl ?>calendar/" class="text-white d-block mb-1">Calendar</a>

                <a href="<?= $reportCard ?>" target="_blank" class="text-white d-block mb-1">
                    Illinois Report Card
                </a>
            </div>

            <div class="col-md-4 mb-4">
                <h5>Safety &amp; Resources</h5>

                <div class="mb-3">
                    <a href="<?= $safe2help ?>" target="_blank"
                       class="text-white fw-bold text-decoration-none">
                        Safe2Help Illinois
                    </a>
                    <small class="d-block text-light">
                        Confidential student safety reporting system.
                    </small>
                </div>

                <div class="mb-3">
                    <a href="<?= $lifeline ?>" target="_blank"
                       class="text-white fw-bold text-decoration-none">
                        988 Suicide &amp; Crisis Lifeline
                    </a>

                    <small class="d-block text-light">
                        Call or text <a href="tel:988" class="text-white">988</a> for support.
                    </small>

                    <small class="d-block">
                        <a href="https://988lifeline.org/chat/" target="_blank" class="text-white">
                            Live Chat
                        </a>
                    </small>
                </div>

                <div class="mb-3">
                    <a href="<?= $able ?>" target="_blank"
                       class="text-white fw-bold text-decoration-none">
                        ABLE
                    </a>
                    <small class="d-block text-light">
                        Disability and independence resources.
                    </small>
                </div>

                <a href="<?= $baseUrl ?>about/documents/" class="text-white d-block mt-3">
                    Compliance Documents
                </a>
            </div>

        </div>

        <hr class="my-4">

        <div class="text-center small">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($district) ?> • All Rights Reserved
        </div>
    </div>
</footer>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>