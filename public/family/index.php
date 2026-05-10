<?php
/**
 * public/family/index.php
 *
 * Family resources landing page.
 */

include '../header.php';
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Families</h1>

        <p class="lead mb-0">
            Family involvement is an important part of student success at Kell Grade School. This section provides quick access to resources, organizations, and important information for parents and guardians.
        </p>
    </div>

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h4">Parent Resources</h2>

                    <ul class="mb-4">
                        <li>School announcements</li>
                        <li>Menus and meal information</li>
                        <li>School calendar</li>
                        <li>Student handbook</li>
                        <li>Transportation information</li>
                    </ul>

                    <a href="<?= BASE_URL ?>calendar/" class="btn btn-primary">
                        View Calendar
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h4">Parent Teacher Organization</h2>

                    <p>
                        The PTO supports students, staff, and school events throughout the year through volunteer work and fundraising activities.
                    </p>

                    <a href="<?= BASE_URL ?>family/pto/" class="btn btn-success">
                        PTO Information
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include '../footer.php'; ?>