<?php
/**
 * about/school-board/index.php
 * 
 * School Board Page
 * 
 * Displays all required Illinois compliance information for the School Board.
 */

include '../../header.php'; 
?>

<div class="container my-5">
    <h1 class="mb-4">School Board</h1>
    
    <div class="row">
        <div class="col-lg-8">
            
            <!-- Board Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Board Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Schedule of Regular Board Meetings</strong> [5 ILCS 120/2.02]</p>
                    <p><strong>Agendas</strong> [5 ILCS 120/2.02]</p>
                    <p><strong>Minutes (Past 60 Days)</strong> [5 ILCS 120/2.06]</p>
                </div>
            </div>

            <!-- Board Members -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Board Members & Leadership Training</h5>
                </div>
                <div class="card-body">
                    <p>List of All Board Members and Leadership Training Completion [105 ILCS 5/10-16a]</p>
                    <p>Board Member Contact Emails (School Accounts) [50 ILCS 205/20]</p>
                </div>
            </div>

        </div>

        <!-- Sidebar Links -->
        <div class="col-lg-4">
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-action">Meeting Schedule</a>
                <a href="#" class="list-group-item list-group-item-action">Current Agendas</a>
                <a href="#" class="list-group-item list-group-item-action">Past Meeting Minutes</a>
                <a href="#" class="list-group-item list-group-item-action">Board Documents</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../footer.php'; ?>