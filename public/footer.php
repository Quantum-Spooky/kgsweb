<?php
// footer.php
?>
<footer class="bg-dark text-white py-5 mt-auto">
    <div class="container">
        <div class="row">
            <!-- Contact Info -->
            <div class="col-md-4 mb-4">
                <h5><?= SITE_NAME ?></h5>
                <p class="mb-1"><?= ADDRESS ?></p>
                <p class="mb-1">Phone: <?= PHONE ?></p>
                <p class="mb-1">Email: <a href="mailto:<?= EMAIL ?>" class="text-white"><?= EMAIL ?></a></p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-4 mb-4">
                <h5>Quick Links</h5>
                <a href="../administration/staff-directory.php" class="text-white d-block mb-1">Staff Directory</a>
                <a href="../cafeteria/" class="text-white d-block mb-1">Lunch Menu</a>
                <a href="../calendar/" class="text-white d-block mb-1">School Calendar</a>
                <a href="<?= REPORT_CARD_URL ?>" target="_blank" class="text-white d-block mb-1">Illinois Report Card</a>
            </div>

            <!-- Safety & Social -->
            <div class="col-md-4 mb-4">
                <h5>Safety & Resources</h5>
                <a href="https://www.safe2helpil.com/" target="_blank" class="text-white d-block mb-1">Safe2Help Illinois</a>
                <a href="tel:988" class="text-white d-block mb-1">Suicide Prevention: 988</a>
                <a href="<?= FACEBOOK_PAGE ?>" target="_blank" class="text-white d-block mt-3">
                    <i class="fab fa-facebook"></i> Follow us on Facebook
                </a>
            </div>
        </div>

        <hr class="my-4">
        <div class="text-center small">
            &copy; <?= date("Y") ?> <?= DISTRICT_NAME ?> • All Rights Reserved
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>