<?php
/**
 * includes/home/widgets-section.php
 * 
 * Home Page Widgets Section
 * 
 * Displays Facebook feed and Google Calendar embed side-by-side.
 */
?>
<div class="row g-4">
    <div class="col-md-6">
        <h5>Facebook Updates</h5>
        <iframe src="https://www.facebook.com/plugins/page.php?href=<?= urlencode(FACEBOOK_PAGE) ?>&tabs=timeline&width=500&height=600" 
                width="100%" height="600" 
                style="border:none;overflow:hidden" 
                scrolling="no" frameborder="0" allowfullscreen="true"></iframe>
    </div>
    
	<div class="col-md-6">
		<h5>Upcoming Events</h5>
		<iframe
			src="https://calendar.google.com/calendar/embed?src=<?= urlencode($config['google_calendar_id'] ?? '') ?>&mode=AGENDA"
			width="100%"
			height="600"
			frameborder="0"
			scrolling="no">
		</iframe>
	</div>
</div>