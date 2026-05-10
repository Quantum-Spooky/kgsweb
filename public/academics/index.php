<?php include '../header.php'; ?>

<div class="container my-5">
    <h1>Academics</h1>

    <h3>Grade School</h3>
    <ul class="list-group">
        <?php if (!empty($config['ks_site_url'])): ?>
            <li class="list-group-item"><a href="<?= $config['ks_site_url'] ?>">Kindergarten</a></li>
        <?php endif; ?>
        <!-- Repeat for all grades with config check -->
    </ul>

    <h3 class="mt-4">Useful Links</h3>
    <div class="row">
        <div class="col-md-4">
            <h5>Math</h5>
            <ul><li><a href="https://www.khanacademy.org">Khan Academy</a></li><!-- etc --></ul>
        </div>
        <!-- More columns -->
    </div>
</div>

<?php include '../footer.php'; ?>