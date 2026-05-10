<?php 
/* calendar.php */

include('includes/header.php'); 

/**
 * PAGE LOGIC
 */
 
// Engines & Helpers
require_once __DIR__ . '/api/class-showdoc-engine.php';
require_once __DIR__ . '/api/class-kgs-helper.php'; 
	
	
// Fetch Data (Matching your config.php keys)
$monthly_cal  = ShowDoc::get_latest_from_folder('monthly_cal');
$academic_cal = ShowDoc::get_latest_from_folder('academic_cal');
	
// Load the config to get the Calendar ID
$calendarId = $config['calendars']['main'] ?? '';
$encodedId = urlencode($calendarId);
?>


    <div id="interactive-calendar" class="section">
        <h2 class="section-title section-title-blue">School Events Calendar</h2>
        <div class="section-content">
			
			<div class="google-cal-responsive-container">
				<iframe 
					src="https://calendar.google.com/calendar/embed?src=<?php echo $encodedId; ?>&ctz=America%2FChicago&mode=MONTH&showPrint=0&showTabs=1&showCalendars=0&showTz=0"
					class="desktop-cal" 
					style="border:0" 
					width="100%" 
					height="600" 
					frameborder="0" 
					scrolling="no">
				</iframe>

				<iframe 
					src="https://calendar.google.com/calendar/embed?src=<?php echo $encodedId; ?>&ctz=America%2FChicago&mode=AGENDA&showPrint=0&showTabs=0&showCalendars=0&showTz=0" 
					class="mobile-cal" 
					style="border:0" 
					width="100%" 
					height="1500" 
					frameborder="0" 
					scrolling="no">
				</iframe>
			</div>
			
			<div class="calendar-tools">
				<?php 
				// Prepare  IDs for the JS to grab
				$calId = $config['calendars']['main'];
				$encodedId = urlencode($calId); 
				?>
				<a href="https://calendar.google.com/calendar/embed?src=<?php echo $encodedId; ?>&ctz=America%2FChicago" 
				   id="view-cal-on-google" 
				   class="calendar-more-btn more-btn" 
				   target="_blank">
				   View on Google
				</a>
				<a href="#" 
				   id="sync-cal-btn" 
				   class="calendar-more-btn more-btn"
				   data-calid="<?php echo $encodedId; ?>"
				   data-ical="<?php echo $calId; ?>">
				   Sync with My Calendar
				</a>
            </div>
			
        </div>
    </div>
	
	<div class="hr-feathers"></div>
	
	<div class="calendar-display-container display-container">

		<div id="monthly-calendar-container" class="section">
			<h2 class="section-title section-title-blue">Monthly Calendar</h2>
			<div class="section-content">
				<div id="monthly-calendar" class="document-viewer">
					<div class="viewer-header">
						<h3>Current Monthly Events</h3>
						<?php if($monthly_cal): ?>
							<span class="badge">Updated: <?php echo $monthly_cal['updated']; ?></span>
						<?php endif; ?>
					</div>
					<div class="img-wrapper">
						<?php if($monthly_cal): ?>
							<img src="<?php echo $monthly_cal['url']; ?>" class="zoomable-menu" alt="Monthly Calendar" title="Click to zoom">
						<?php else: ?>
							<div class="placeholder-box">Monthly calendar is currently being updated.</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="hr-feathers"></div>
				
		<div id="academic-calendar-container" class="section">
			<h2 class="section-title section-title-blue">Academic Calendar</h2>
			<div class="section-content">
				<div id="academic-calendar" class="document-viewer">
					<div class="viewer-header">
						<h3>Yearly Academic Schedule</h3>
						<?php if($academic_cal): ?>
							<span class="badge">Updated: <?php echo $academic_cal['updated']; ?></span>
						<?php endif; ?>
					</div>
					<div class="img-wrapper">
						<?php if($academic_cal): ?>
							<img src="<?php echo $academic_cal['url']; ?>" class="zoomable-menu" alt="Academic Calendar" title="Click to zoom">
						<?php else: ?>
							<div class="placeholder-box">Academic calendar is currently being updated.</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	
	</div>

<?php include('includes/footer.php'); ?>