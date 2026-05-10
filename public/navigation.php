<?php
// navigation.php
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="<?= BASE_URL ?>index.php">
            <?= SITE_NAME ?>
        </a>
       
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
       
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>administration/">About / District</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>academics/">Academics</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>calendar/">Calendar</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>cafeteria/">Dining</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>news/">News</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>activities/">Activities</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>family/">Family</a></li>
            </ul>
        </div>
    </div>
</nav>