<?php
/**
 * public/contact/index.php
 *
 * Contact information page.
 */

$root = substr(__DIR__, 0, strpos(__DIR__, '/public'));
 
require_once $root . '/kgs-core/bootstrap.php';
 view('layout/header');
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Contact Us</h1>

        <p class="lead mb-0">
            Contact Kell Grade School for questions regarding enrollment, transportation, activities, school events, or general information.
        </p>
    </div>

    <div class="row g-4">

        <div class="col-lg-7">

            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <h2 class="h4 mb-4">School Information</h2>

                    <div class="mb-3">
                        <strong>Address</strong><br>
                        17990 Route 37<br>
                        Kell, Illinois 62853
                    </div>

                    <div class="mb-3">
                        <strong>Phone</strong><br>
                        <a href="tel:+16188223435">(618) 822-3435</a>
                    </div>

                    <div class="mb-0">
                        <strong>Fax</strong><br>
                        (618) 822-6426
                    </div>

                </div>
            </div>

        </div>

        <div class="col-lg-5">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h3 class="h5 mb-0">Office Hours</h3>
                </div>

                <div class="card-body">

                    <p class="mb-2">
                        Monday - Friday
                    </p>

                    <p class="mb-0">
                        7:30 AM - 3:30 PM
                    </p>

                </div>
            </div>

        </div>

    </div>

</div>

<?php view('layout/footer'); ?>