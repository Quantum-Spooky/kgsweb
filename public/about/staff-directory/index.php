<?php
/**
 * public\about\staff-directory
 *
 * Staff directory page.
 */

include '../header.php';
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Staff Directory</h1>

        <p class="lead mb-0">
            Meet the teachers, administrators, and support staff who help make Kell Grade School a welcoming and successful learning environment.
        </p>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Directory</h2>
        </div>

        <div class="card-body">

            <?php
            /**
             * Planned future integration:
             * Populate staff directory from Google Sheets.
             */
            ?>

            <div class="alert alert-secondary mb-0">
                Staff directory integration is currently being developed.
            </div>

        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h2 class="h5">Future Features</h2>

            <ul class="mb-0">
                <li>Searchable staff listings</li>
                <li>Teacher classroom pages</li>
                <li>Email directory</li>
                <li>Department organization</li>
            </ul>

        </div>
    </div>

</div>

<?php include '../footer.php'; ?>