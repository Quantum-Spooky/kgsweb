<?php
/**
 * public/activities/clubs/index.php
 *
 * Clubs landing page.
 */

include '../../header.php';
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Student Clubs</h1>

        <p class="lead mb-0">
            Clubs and enrichment activities provide students with opportunities to learn new skills, build friendships, and participate in school life outside the classroom.
        </p>
    </div>

    <div class="row g-4">

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Book Club</h2>

                    <p class="mb-0">
                        Encourages reading, discussion, and a love of literature through group activities and selected readings.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Scholar Bowl</h2>

                    <p class="mb-0">
                        Academic competition team focused on teamwork, quick thinking, and subject-area knowledge.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5">Yearbook</h2>

                    <p class="mb-0">
                        Students help document school events and create the annual school yearbook.
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include '../../footer.php'; ?>