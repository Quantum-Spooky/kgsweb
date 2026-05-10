<?php
/**
 * public/calendar/index.php
 *
 * School calendar page.
 */

$root = substr(__DIR__, 0, strpos(__DIR__, '/public'));
 
require_once $root . '/kgs-core/bootstrap.php';
 view('layout/header');
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">School Calendar</h1>

        <p class="lead mb-0">
            Stay informed about important school dates, events, holidays, early dismissals, and activities throughout the school year.
        </p>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Upcoming Events</h2>
        </div>

        <div class="card-body">

            <?php
            /**
             * Future improvement:
             * Replace this section with a Google Calendar
             * or Google Sheets powered event feed.
             */
            ?>

            <div class="alert alert-secondary mb-0">
                Calendar integration is currently being developed.
            </div>

        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="h5">Future Integration Plans</h2>

            <ul class="mb-0">
                <li>Google Calendar integration</li>
                <li>Athletics schedules</li>
                <li>Academic events</li>
                <li>Automatic event updates</li>
            </ul>
        </div>
    </div>

</div>

<?php view('layout/footer'); ?>