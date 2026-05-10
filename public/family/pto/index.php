<?php
/**
 * public/family/pto/index.php
 *
 * PTO information page.
 */

$root = substr(__DIR__, 0, strpos(__DIR__, '/public'));
 
require_once $root . '/kgs-core/bootstrap.php';
 view('layout/header');
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Parent Teacher Organization</h1>

        <p class="lead mb-0">
            The Kell Grade School PTO works to strengthen the connection between school and home while supporting students and staff through events, volunteer efforts, and fundraising.
        </p>
    </div>

    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <h2 class="h4">Get Involved</h2>

                    <p>
                        Families are encouraged to participate in PTO meetings, volunteer opportunities, and school events throughout the year.
                    </p>

                    <p class="mb-0">
                        Additional PTO information, meeting dates, and announcements will be added to this page.
                    </p>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h3 class="h5 mb-0">Quick Links</h3>
                </div>

                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>calendar/" class="list-group-item list-group-item-action">
                        School Calendar
                    </a>

                    <a href="<?= BASE_URL ?>news/" class="list-group-item list-group-item-action">
                        School News
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