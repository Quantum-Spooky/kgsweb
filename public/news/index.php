<?php
/**
 * public/news/index.php
 *
 * School news landing page.
 */

$root = substr(__DIR__, 0, strpos(__DIR__, '/public'));
 
require_once $root . '/kgs-core/bootstrap.php';
 view('layout/header');
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">News & Announcements</h1>

        <p class="lead mb-0">
            Stay up to date with announcements, student achievements, upcoming events, and important information from Kell Grade School.
        </p>
    </div>

    <div class="row g-4">

        <div class="col-lg-8">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white">
                    <h2 class="h5 mb-0">Latest Updates</h2>
                </div>

                <div class="card-body">

                    <?php
                    /**
                     * Planned future integration:
                     * Pull announcements from Google Sheets
                     * or Apptegy-style feed JSON.
                     */
                    ?>

                    <div class="alert alert-secondary mb-0">
                        News feed integration is currently being developed.
                    </div>

                </div>
            </div>

        </div>

        <div class="col-lg-4">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h3 class="h5 mb-0">Quick Links</h3>
                </div>

                <div class="list-group list-group-flush">

                    <a href="<?= BASE_URL ?>calendar/" class="list-group-item list-group-item-action">
                        School Calendar
                    </a>

                    <a href="<?= BASE_URL ?>activities/" class="list-group-item list-group-item-action">
                        Activities & Athletics
                    </a>

                    <a href="<?= BASE_URL ?>family/" class="list-group-item list-group-item-action">
                        Family Resources
                    </a>

                </div>
            </div>

        </div>

    </div>

</div>

<?php view('layout/footer'); ?>