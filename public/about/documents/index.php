<?php
/**
 * public/about/documents/index.php
 *
 * Public documents page.
 */

$root = substr(__DIR__, 0, strpos(__DIR__, '/public'));
 
require_once $root . '/kgs-core/bootstrap.php';
 view('layout/header');
?>

<div class="container my-5">

    <div class="bg-light rounded-4 p-4 p-lg-5 shadow-sm mb-4">
        <h1 class="display-5 fw-bold mb-3">Public Documents</h1>

        <p class="lead mb-0">
            Access important district and school documents, forms, reports, and informational resources.
        </p>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Document Library</h2>
        </div>

        <div class="card-body">

            <p>
                This section is designed to work with Google Drive-powered document listings so staff can manage files without editing website code.
            </p>

            <div class="alert alert-secondary mb-0">
                Public documents will be added soon.
            </div>

        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h2 class="h5">Planned Features</h2>

            <ul class="mb-0">
                <li>Automatic Google Drive file listings</li>
                <li>Searchable documents</li>
                <li>Download tracking</li>
                <li>Folder organization</li>
            </ul>

        </div>
    </div>

</div>

<?php view('layout/footer'); ?>