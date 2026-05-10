<?php
/**
 * index.php
 * 
 * Placeholder / Temporary 404 Page
 * 
 * Used as a temporary landing page for sections that
 * have not been fully built out yet.
 */

include '../header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Page Under Construction</h4>
                </div>

                <div class="card-body">
                    <h1 class="display-6 mb-3">404 - Page Not Found</h1>

                    <p class="lead">
                        The page or section you are looking for has not been created yet.
                    </p>

                    <p>
                        This area of the website is currently under development and will be available soon.
                    </p>

                    <hr>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>" class="btn btn-primary">
                            Return to Homepage
                        </a>

                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            Go Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- Optional Future Content -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Coming Soon</h5>
                </div>

                <div class="card-body">
                    <p class="mb-0">
                        Additional content and resources for this section will be added in a future update.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../footer.php'; ?>