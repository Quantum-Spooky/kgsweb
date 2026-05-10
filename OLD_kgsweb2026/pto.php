<?php 
include('includes/header.php'); 

/**
 * PAGE LOGIC: PTO
 * Fetches the intro text from a Google Doc and the latest flyer/image from a folder.
 */
require_once __DIR__ . '/api/class-showdoc-engine.php';
require_once __DIR__ . '/api/class-kgs-helper.php'; 

// --- 1. FETCH PTO INTRO TEXT ---
$pto_intro_cache_key = 'pto_intro_text';
$pto_intro_html = KGSHelper::get_cache($pto_intro_cache_key);

if (!$pto_intro_html) {
    try {
        $client = KGSHelper::getClient();
        $docsService = new \Google\Service\Docs($client);
        
        // Pulls the ID '1i8NOPak...AjKc' from your config.php
        $docId = $config['files']['pto_intro'];
        $doc = $docsService->documents->get($docId);
        
        $text = "";
        foreach ($doc->getBody()->getContent() as $element) {
            if ($element->getParagraph()) {
                foreach ($element->getParagraph()->getElements() as $pPart) {
                    $text .= $pPart->getTextRun() ? $pPart->getTextRun()->getContent() : "";
                }
            }
        }
        // Convert newlines to <br> tags for proper web display
        $pto_intro_html = nl2br(htmlspecialchars($text));
        KGSHelper::set_cache($pto_intro_cache_key, $pto_intro_html);
    } catch (Exception $e) {
        error_log("PTO Text Error: " . $e->getMessage());
        $pto_intro_html = "Welcome to the PTO section! Please check back soon for updates.";
    }
}

// --- 2. FETCH PTO FEATURE IMAGE ---
// ShowDoc looks into the folder '1M_gJ2tc...edAl1'
$pto_image_data = ShowDoc::get_latest_from_folder('pto_feature_image');
$pto_feature_image = "";

if ($pto_image_data) {
    // Note: Adjust the 'api/cache/' path if your PTO page is in a subfolder
    $imgUrl = 'api/cache/' . basename($pto_image_data['url']);
    $pto_feature_image = '<img src="' . $imgUrl . '" class="img-fluid rounded shadow zoomable-menu" alt="PTO Flyer">';
    $pto_image_caption = "Updated: " . $pto_image_data['updated'];
} else {
    $pto_feature_image = '<div class="text-center p-5 text-muted"><i class="fa-solid fa-image fa-3x mb-3"></i><br>New flyer coming soon!</div>';
    $pto_image_caption = "PTO Gallery";
}
?>

<div id="site-main">

<div class="section">
    <div class="section-content">
        <div class="row">
            <div class="col-lg-6 mb-5">
                <h2 id="pto" class="section-title section-title-blue text-start">Parent-Teacher Organization (PTO)</h2>
                
                <div class="pto-intro mb-3 p-4 bg-white border-start border-4 border-primary shadow-sm">
                    <?php echo $pto_intro_html; ?>
                </div>
				
				<div id="fb-root"></div>
				<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v24.0&appId=767023759976096"></script>
				
				<div class="fb-page" data-href="https://www.facebook.com/profile.php?id=61562425295733" data-tabs="timeline" data-width="500" data-height="" data-small-header="true" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/profile.php?id=61562425295733" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/profile.php?id=61562425295733" target="blank_">Kell Grade School PTO </a></blockquote></div>				
				
				<div class="fb-feed-item-placeholder">
					<i class="fab fa-facebook-square" style="color: #3b5998; font-size: 1.5rem; margin-right: 10px;"></i>
					<strong>Kell Grade School PTO</strong>
					<p></p>
					<p><a href="https://www.facebook.com/profile.php?id=61562425295733" target="_blank" style="color: #015BA7; font-weight: bold; text-decoration: none;">Visit Facebook Page →</a></p>
				</div>
		
            </div>
        
            <div class="col-lg-6">            
                <div class="pto-image mb-3 p-2 bg-white border shadow-sm text-center">
                    <?php echo $pto_feature_image; ?>
                </div>
                <p class="small text-muted text-center mt-2">
                   
                </p>
            </div>
        </div> 
    </div> 
</div> 

</div> 

<?php include('includes/footer.php'); ?>