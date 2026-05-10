<?php
/**
 * public/board/index.php
 *
 * School board page.
 */

include '../header.php';
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Board of Education</h1>

        <p class="lead mb-0">
            The Board of Education helps guide district policies, finances, and long-term planning in support of student success.
        </p>
    </div>

    <div class="row g-4">

        <div class="col-lg-8">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white">
                    <h2 class="h5 mb-0">Board Information</h2>
                </div>

                <div class="card-body">

                    <p>
                        Board meeting dates, agendas, minutes, and district information will be available here.
                    </p>

                    <div class="alert alert-secondary mb-0">
                        Board document integration is currently being developed.
                    </div>

                </div>
            </div>

        </div>

        <div class="col-lg-4">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h3 class="h5 mb-0">Related Resources</h3>
                </div>

                <div class="list-group list-group-flush">

                    <a href="<?= BASE_URL ?>about/policies/" class="list-group-item list-group-item-action">
                        Policies & Handbooks
                    </a>

                    <a href="<?= BASE_URL ?>about/documents/" class="list-group-item list-group-item-action">
                        Public Documents
                    </a>

                    <a href="<?= BASE_URL ?>calendar/" class="list-group-item list-group-item-action">
                        School Calendar
                    </a>

                </div>
            </div>

        </div>

    </div>

</div>

<?php include '../footer.php'; ?>