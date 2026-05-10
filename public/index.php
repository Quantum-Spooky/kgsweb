<?php 
include 'header.php'; 
?>

<!-- Hero Section -->
<div class="position-relative">
    <img src="<?= HERO_IMAGE ?>" 
         alt="Kell Grade School" 
         class="w-100" 
         style="height: 480px; object-fit: cover;">
    
    <div class="position-absolute top-50 start-50 translate-middle text-center text-white w-100 px-4">
        <h1 class="display-3 fw-bold hero-text"><?= HERO_HEADLINE ?></h1>
        <p class="lead fs-3 hero-text"><?= HERO_SUBHEADLINE ?></p>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Live Feed -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-success text-white fw-bold">Latest Announcements</div>
                <div class="card-body">
                    <?php include 'includes/live-feed.php'; ?>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <h5>Facebook Updates</h5>
                    <iframe src="https://www.facebook.com/plugins/page.php?href=<?= urlencode(FACEBOOK_PAGE) ?>&tabs=timeline&width=500&height=600" 
                            width="100%" height="600" style="border:none;overflow:hidden" scrolling="no" frameborder="0"></iframe>
                </div>
                <div class="col-md-6">
                    <h5>Upcoming Events</h5>
                    <iframe src="https://calendar.google.com/calendar/embed?src=<?= urlencode($config['google_calendar_id'] ?? '') ?>" 
                            width="100%" height="600" frameborder="0" scrolling="no"></iframe>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Current Weather - Kell, IL</div>
                <div class="card-body text-center">
                    <h2>58°F</h2>
                    <p>Partly Cloudy</p>
                </div>
            </div>

            <a href="<?= REPORT_CARD_URL ?>" target="_blank" class="btn btn-outline-primary btn-lg w-100 mb-3">
                Illinois Report Card
            </a>
			<a href="<?= BASE_URL ?>cafeteria/" class="btn btn-outline-success btn-lg w-100 mb-3">
                View Lunch Menu
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>