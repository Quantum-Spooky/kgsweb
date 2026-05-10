<?php 
// Ensure we have access to the variables defined in contact-info.php
include_once(__DIR__ . '/../config/contact-info.php');
?>

		</div> <!-- end of site-main /-->

		<div class="footer">
			<div class="footer-row">
				<div class="footer-column">
					<h2 class="footer-h2">Contact Us</h2>
					<p><?php echo $school_name; ?></p>
					<p>
						<a href="<?php echo $maps_direct_url; ?>" target="_blank" style="color: white; text-decoration: none;">
						<?php echo $school_address; ?>
						</a>
					</p>
					<p>Phone: <?php echo $school_phone; ?></p>
					<p>Fax: <?php echo $school_fax; ?></p>
				</div>
				<div class="footer-column">
					<h2 class="footer-h2">Resources</h2>
					<ul>
						<li><a href="https://www.safe2helpil.com/" target="_blank">Safe 2 Help IL</a></li>
						<li><a href="https://google.com" target="_blank">FOIA Requests</a></li>
						<li><a href="https://google.com">E-Learning Plan</a></li>
						<li><a href="https://www.isbe.net" target="_blank">ISBE Website</a></li>
					</ul>
				</div>
				<div class="footer-column">
					<h2 class="footer-h2">Connections</h2>
					<p>Principal: Patrick Keeney</p>
					<p>Secretary: Kendra Koch</p>
					<p>Email: <a href="mailto:<?php echo $school_email; ?>" style="color: white;"><?php echo $school_email; ?></a></p>
					<p>
						<a href="https://www.facebook.com/KellCSD2" target="_blank" style="color: white; text-decoration: none;">
						<i class="fab fa-facebook"></i> Follow @KellCSD2 on Facebook
						</a>
					</p>
				</div>
			</div>
			<div class="footer-copyright" style="position: relative;">
				Copyright 
				<span id="cache-buster-link" style="cursor: pointer;">&copy;</span>
				<?php echo date("Y"); ?> <?php echo $school_name; ?>
				<span id="cache-buster-toast" class="cache-toast"></span>
			</div>

			<a href="#" id="backToTop"><i class="fa-solid fa-chevron-up"></i></a>        

		</div> <!-- end footer div /-->
			


		<script src="<?php echo $base; ?>/js/main.js"></script>
		<script src="<?php echo $base; ?>/js/weather.js"></script>
		<script src="<?php echo $base; ?>/js/showdoc.js"></script>
			
			
		<div id="menu-lightbox" class="lightbox-overlay" style="display: none;">
			<span class="lightbox-close">&times;</span>
			<img class="lightbox-content" id="lightbox-img" src="">
			<div id="lightbox-caption"></div>
			<div class="lightbox-controls">
				<button onclick="printLightboxImage()" class="btn-control"><i class="fa-solid fa-print"></i> Print</button>
				<a id="download-link" href="#" download class="btn-control"><i class="fa-solid fa-download"></i> Download</a>
			</div>
		</div><!-- end menu lightbox div /-->
		
	</div><!-- end site-container div /-->
	
</body>
</html>