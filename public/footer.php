<?php
/**
 * footer.php
 * 
 * Site Footer
 */
?>
<footer class="bg-dark text-white py-5 mt-auto">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5><?= SITE_NAME ?></h5>
                <p class="mb-1"><?= ADDRESS ?></p>
                <p class="mb-1">Phone: <?= PHONE ?></p>
                <p class="mb-1">Email: <a href="mailto:<?= EMAIL ?>" class="text-white"><?= EMAIL ?></a></p>
            </div>
            
            <div class="col-md-4 mb-4">
                <h5>Quick Links</h5>
                <a href="<?= BASE_URL ?>about/staff-directory/" class="text-white d-block mb-1">Staff Directory</a>
                <a href="<?= BASE_URL ?>dining/" class="text-white d-block mb-1">Lunch Menu</a>
                <a href="<?= BASE_URL ?>calendar/" class="text-white d-block mb-1">Calendar</a>
                <a href="<?= REPORT_CARD_URL ?>" target="_blank" class="text-white d-block mb-1">Illinois Report Card</a>
            </div>
            
			<div class="col-md-4 mb-4">
				<h5>Safety &amp; Resources</h5>

				<!-- Safe2Help -->
				<div class="mb-3">
					<a href="<?= $config['safe2help_url'] ?>"
					   target="_blank"
					   class="text-white fw-bold text-decoration-none">
						Safe2Help Illinois
					</a>

					<small class="d-block text-light">
						Illinois school safety and confidential reporting system.
					</small>
				</div>

				<!-- 988 -->
				<div class="mb-3">
					<a href="<?= $config['lifeline_url'] ?>"
					   target="_blank"
					   class="text-white fw-bold text-decoration-none">
						988 Suicide &amp; Crisis Lifeline
					</a>

					<small class="d-block text-light">
						Call or text <a href="tel:988" class="text-white">988</a>
						for immediate mental health crisis support.
					</small>

					<small class="d-block">
						<a href="https://988lifeline.org/chat/"
						   target="_blank"
						   class="text-white">
							Start Live Chat
						</a>
					</small>
				</div>

				<!-- ABLE -->
				<div class="mb-3">
					<a href="<?= $config['able_url'] ?>"
					   target="_blank"
					   class="text-white fw-bold text-decoration-none">
						ABLE
					</a>

					<small class="d-block text-light">
						Achieving a Better Life Experience resources.
					</small>
				</div>

				<!-- Compliance -->
				<a href="<?= BASE_URL ?>about/documents/"
				   class="text-white d-block mt-3">
					Compliance Documents
				</a>
			</div>
        </div>
        
        <hr class="my-4">
        <div class="text-center small">
            &copy; <?= date("Y") ?> <?= DISTRICT_NAME ?> • All Rights Reserved
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>