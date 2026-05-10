<?php
/**
 * public/activities/index.php
 *
 * Activities landing page.
 */

$root = substr(__DIR__, 0, strpos(__DIR__, '/public'));
 
require_once $root . '/kgs-core/bootstrap.php';
 view('layout/header');
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Activities & Student Life</h1>

        <p class="lead mb-0">
            Kell Grade School offers a variety of activities, clubs, and athletics programs that help students explore interests, build leadership skills, and stay involved outside the classroom.
        </p>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h4">Clubs & Organizations</h2>

                    <p>
                        Students can participate in academic, creative, and enrichment clubs throughout the school year.
                    </p>

                    <ul class="mb-4">
                        <li>Brain Games</li>
                        <li>Book Club</li>
                        <li>Cooking Club</li>
                        <li>Scholar Bowl</li>
                        <li>Yearbook</li>
                    </ul>

                    <a href="<?= BASE_URL ?>activities/clubs/" class="btn btn-primary">
                        View Clubs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h4">Athletics</h2>

                    <p>
                        School athletics encourage teamwork, sportsmanship, and school pride while helping students stay active.
                    </p>

                    <ul class="mb-4">
                        <li>Baseball</li>
                        <li>Bowling</li>
                        <li>Basketball</li>
                        <li>Cheerleading</li>
                        <li>Cross Country</li>
                        <li>Volleyball</li>
                    </ul>

                    <a href="<?= BASE_URL ?>activities/athletics/" class="btn btn-success">
                        View Athletics
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            <h3 class="h5 mb-0">Participation Information</h3>
        </div>

        <div class="card-body">
            <div class="row g-4">

                <div class="col-md-4">
                    <h4 class="h6 text-uppercase">Eligibility</h4>
                    <p class="mb-0">
                        Students participating in extracurricular activities are expected to maintain appropriate academic and behavioral standards.
                    </p>
                </div>

                <div class="col-md-4">
                    <h4 class="h6 text-uppercase">Schedules</h4>
                    <p class="mb-0">
                        Practice times, competition dates, and club meetings are announced throughout the year.
                    </p>
                </div>

                <div class="col-md-4">
                    <h4 class="h6 text-uppercase">Family Support</h4>
                    <p class="mb-0">
                        Families are encouraged to attend events and support students through volunteer opportunities and attendance.
                    </p>
                </div>

            </div>
        </div>
    </div>

</div>

<?php view('layout/footer'); ?>