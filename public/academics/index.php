<?php
/**
 * academics/index.php
 * 
 * Academics Main Page
 * 
 * Central hub for all grade levels, subjects, and useful learning resources.
 */

include '../header.php'; 
?>

<div class="container my-5">
    <h1 class="mb-4">Academics</h1>

    <h3 class="mt-4">Grade School</h3>
    <div class="list-group mb-5">
        <?php if (!empty($config['prek_site_url'])): ?>
            <a href="<?= $config['prek_site_url'] ?>" class="list-group-item list-group-item-action">Preschool / Pre-K</a>
        <?php endif; ?>
        
        <?php if (!empty($config['ks_site_url'])): ?>
            <a href="<?= $config['ks_site_url'] ?>" class="list-group-item list-group-item-action">Kindergarten</a>
        <?php endif; ?>
        
        <?php if (!empty($config['gr1_site_url'])): ?>
            <a href="<?= $config['gr1_site_url'] ?>" class="list-group-item list-group-item-action">1st Grade</a>
        <?php endif; ?>
        
        <?php if (!empty($config['gr2_site_url'])): ?>
            <a href="<?= $config['gr2_site_url'] ?>" class="list-group-item list-group-item-action">2nd Grade</a>
        <?php endif; ?>
        
        <?php if (!empty($config['gr3_site_url'])): ?>
            <a href="<?= $config['gr3_site_url'] ?>" class="list-group-item list-group-item-action">3rd Grade</a>
        <?php endif; ?>
        
        <?php if (!empty($config['gr4_site_url'])): ?>
            <a href="<?= $config['gr4_site_url'] ?>" class="list-group-item list-group-item-action">4th Grade</a>
        <?php endif; ?>
        
        <?php if (!empty($config['gr5_site_url'])): ?>
            <a href="<?= $config['gr5_site_url'] ?>" class="list-group-item list-group-item-action">5th Grade</a>
        <?php endif; ?>
    </div>

    <h3 class="mt-4">Junior High</h3>
    <div class="list-group mb-5">
        <?php if (!empty($config['jh_ela_site_url'])): ?>
            <a href="<?= $config['jh_ela_site_url'] ?>" class="list-group-item list-group-item-action">English Language Arts</a>
        <?php endif; ?>
        <?php if (!empty($config['jh_math_site_url'])): ?>
            <a href="<?= $config['jh_math_site_url'] ?>" class="list-group-item list-group-item-action">Math</a>
        <?php endif; ?>
        <?php if (!empty($config['jh_science_site_url'])): ?>
            <a href="<?= $config['jh_science_site_url'] ?>" class="list-group-item list-group-item-action">Science</a>
        <?php endif; ?>
        <?php if (!empty($config['jh_ss_site_url'])): ?>
            <a href="<?= $config['jh_ss_site_url'] ?>" class="list-group-item list-group-item-action">Social Studies</a>
        <?php endif; ?>
    </div>

    <h3 class="mt-4">Schoolwide Programs</h3>
    <div class="list-group mb-5">
        <?php if (!empty($config['sped_site_url'])): ?>
            <a href="<?= $config['sped_site_url'] ?>" class="list-group-item list-group-item-action">Special Education</a>
        <?php endif; ?>
        <?php if (!empty($config['title1_site_url'])): ?>
            <a href="<?= $config['title1_site_url'] ?>" class="list-group-item list-group-item-action">Title I</a>
        <?php endif; ?>
    </div>

    <h3>Useful Learning Links</h3>
    <p class="text-muted">Organized resources for students and parents.</p>
    <!-- You can expand this into categorized cards later -->

</div>

<?php include '../footer.php'; ?>