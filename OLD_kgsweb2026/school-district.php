<?php 
include('includes/header.php'); 

/**
 * PAGE LOGIC
 */
require_once __DIR__ . '/api/class-kgs-helper.php';
require_once __DIR__ . '/api/class-schooldistrict-engine.php';
require_once __DIR__ . '/api/class-showdoc-engine.php'; // Included for template consistency

$config = require __DIR__ . '/config/config.php';
$contactinfo = require __DIR__ . '/config/contact-info.php';

// Fetch the dynamic content from Google Docs
$about_html = KGSSchoolDistrict::get_doc_content($config['files']['about_kgs']);
$meeting_intro_html = KGSSchoolDistrict::get_doc_content($config['files']['board_meeting_intro']);
$district_folder_id = $config['folders']['district_docs_root']; 
?>

    <div class="section">
        <h2 id="school-district" class="section-title section-title-blue">Kell Consolidated School District No. 2</h2>
        
        <div class="section-content">
            
			<div id="school-district-jump-to-anchor" class="text-center jump-to-anchor-section mb-4">
                <span class="small fw-bold text-uppercase d-block mb-2">Jump to:</span>
				<a href="#contact-card" class="jump-to-anchor-btn py-1 px-3">Contact Us</a> 
                <a href="#school-board" class="jump-to-anchor-btn py-1 px-3">School Board</a> 
                <a href="#school-district-documents" class="jump-to-anchor-btn py-1 px-3">District Documents</a>
            </div>




            <section id="about-kell" class="section-content mb-5">
                <h3 class="border-bottom pb-2 mb-3">About Kell Grade School</h3>
                <div class="google-doc-import content-card-simple">
                    <?php echo $about_html; ?>
                </div>
            </section>
		
		<div class="hr-feathers"></div>

            <section id="contact-card" class="section-content mb-5">
                <h3 class="border-bottom pb-2 mb-3">Contact Us</h3>
                <div class="google-doc-import content-card-simple">
                     <div class="contact-flex-container">
						<div class="contact-info-column">
							<div class="detail-section">
								<h3><i class="fa-solid fa-map-marker-alt"></i> Location</h3>
								<address>
									<strong><?php echo $school_name; ?></strong><br>
									<?php echo $school_address; ?>
								</address>
							</div> <!-- end detail-section /-->

							<div class="detail-section">
								<h3><i class="fa-solid fa-phone"></i> Contact Details</h3>
								<p><strong>Phone:</strong> <a href="tel:<?php echo $school_phone; ?>"><?php echo $school_phone; ?></a></p>
								<p><strong>Fax:</strong> <?php echo $school_fax; ?></p>
								<p><strong>Email:</strong> <a href="mailto:<?php echo $school_email; ?>"><?php echo $school_email; ?></a></p>
							</div> <!-- end detail-section /-->

							<div class="detail-section">
								<h3><i class="fa-solid fa-clock"></i> Office Hours</h3>
								<p><?php echo $office_hours; ?></p>
							</div>
						</div> <!-- end detail-section /-->
						
						<div class="contact-map-column">
							<iframe 
								src="<?php echo $maps_embed_url; ?>" 
								width="100%" 
								height="100%" 
								style="border:0; min-height: 400px;" 
								allowfullscreen="" 
								loading="lazy">
							</iframe>
						</div><!-- end contact-map-column /-->
					</div> <!-- contact-flex-container /-->
                </div>
            </section>		

        </div>
    </div>
	
	<div class="hr-feathers"></div>	
	
    <div class="section">
        <div class="section-content">
            <div class="row">
                <div class="col-lg-6 mb-5">
                    <h2 id="school-board" class="section-title section-title-blue text-start">School Board</h2>
                    
                    <h3>Meeting Schedule</h3>
                    <div class="meeting-intro mb-3 p-3 bg-light border-start border-4 border-primary">
                        <?php echo $meeting_intro_html; ?>
                    </div>

					<table id="board-meetings-table" class="table table-striped table-hover border">
						<thead class="table-light">
							<tr><th>Upcoming Meeting Dates</th></tr>
						</thead>
						<tbody id="board-meetings-body"> <tr><td><i class="fa-solid fa-spinner fa-spin"></i> Loading dates...</td></tr>
						</tbody>
					</table>

                    <h3 class="mt-5">Board Members</h3>
						<table id="board-members-table" class="table table-bordered border-primary shadow-sm">
							<thead class="table-primary">
								<tr>
									<th>Member Name</th>
									<th>Position</th>
								</tr>
							</thead>
							<tbody id="board-members-body">
								<tr>
									<td colspan="2" class="text-center py-3">
										<i class="fa-solid fa-spinner fa-spin"></i> Loading Board Members...
									</td>
								</tr>
							</tbody>
						</table>
                </div>
				
				
			
                <div class="col-lg-6">
                    <h2 id="school-district-documents" class="section-title section-title-blue text-start">District Documents</h2>
                    <p class="small text-muted mb-3">Browse our public folders for policies, handbooks, and financial reports.</p>
                    
                    <div id="kgsweb-documents-tree" class="p-3 bg-light border rounded shadow-sm">
                        <ul class="kgsweb-tree" id="drive-root">
                            <li><i class="fa-solid fa-sync fa-spin"></i> Connecting to Drive...</li>
                        </ul>
                    </div>
                </div>
            </div> 
		</div> 
	</div> 
			
		

<?php include 'includes/footer.php'; ?>