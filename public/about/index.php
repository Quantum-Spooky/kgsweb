<?php
/**
 * about/index.php
 * 
 * About / District Main Page
 * 
 * Serves as the central hub for district information, board, documents, policies, and staff.
 */

include '../header.php'; 
?>

<div class="container my-5">
    <h1 class="mb-4">About Our District</h1>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- About the District -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">About Kell Grade School</h5>
                </div>
                <div class="card-body">
                    <?php 
                    // You can pull this from a Google Doc or config later
                    echo "<p>Content for About the District goes here...</p>"; 
                    ?>
                </div>
            </div>

            <!-- Building Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Building Information</h5>
                </div>
                <div class="card-body">
                    <p>Building details, history, and contact information will go here.</p>
                </div>
            </div>
        </div>

        <!-- Quick Links Sidebar -->
        <div class="col-lg-4">
            <div class="list-group">
                <a href="<?= BASE_URL ?>about/school-board/" class="list-group-item list-group-item-action">
                    <strong>School Board</strong>
                </a>
                <a href="<?= BASE_URL ?>about/documents/" class="list-group-item list-group-item-action">
                    <strong>District Documents &amp; Compliance</strong>
                </a>
                <a href="<?= BASE_URL ?>about/policies/" class="list-group-item list-group-item-action">
                    <strong>District Policies</strong>
                </a>
                <a href="<?= BASE_URL ?>about/employment/" class="list-group-item list-group-item-action">
                    <strong>Employment Opportunities</strong>
                </a>
                <a href="<?= BASE_URL ?>about/staff-directory/" class="list-group-item list-group-item-action">
                    <strong>Staff Directory</strong>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>