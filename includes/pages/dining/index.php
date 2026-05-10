<?php
/**
 * dining/index.php
 * 
 * Dining / Cafeteria Page
 * 
 * Displays breakfast and lunch menus with nutrition information.
 */

$root = substr(__DIR__, 0, strpos(__DIR__, '/public'));
 
require_once $root . '/kgs-core/bootstrap.php';
 view('layout/header'); 
?>

<div class="container my-5">
    <h1 class="mb-4">School Dining</h1>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Breakfast Menu</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($config['breakfast_menu_file_id'] ?? '')): ?>
                        <!-- Add image or PDF embed here later -->
                        <p>Breakfast Menu Image / PDF will display here.</p>
                    <?php else: ?>
                        <p class="text-muted">Breakfast menu coming soon.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Lunch Menu</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($config['lunch_menu_file_id'] ?? '')): ?>
                        <!-- Add image or PDF embed here later -->
                        <p>Lunch Menu Image / PDF will display here.</p>
                    <?php else: ?>
                        <p class="text-muted">Lunch menu coming soon.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Free Meals:</strong> All students receive free breakfast and lunch under the Illinois program.
    </div>
</div>

<?php view('layout/footer'); ?>