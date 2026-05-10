<?php
/**
 * public/activities/athletics/index.php
 *
 * Athletics landing page.
 */

include '../../header.php';
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Athletics</h1>

        <p class="lead mb-0">
            Athletics at Kell Grade School promote teamwork, discipline, sportsmanship, and school pride through competitive and developmental programs.
        </p>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Basketball</h2>

                    <p class="mb-0">
                        Competitive team opportunities focused on skill development, teamwork, and sportsmanship.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Cross Country</h2>

                    <p class="mb-0">
                        Endurance-based athletics program encouraging goal setting and personal growth.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Cheerleading</h2>

                    <p class="mb-0">
                        Supports school spirit and athletic events while developing teamwork and performance skills.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white">
            <h3 class="h5 mb-0">Future Improvements</h3>
        </div>

        <div class="card-body">

            <ul class="mb-0">
                <li>Game schedules from Google Sheets</li>
                <li>Score updates</li>
                <li>Team rosters</li>
                <li>Photo galleries</li>
            </ul>

        </div>
    </div>

</div>

<?php include '../../footer.php'; ?>