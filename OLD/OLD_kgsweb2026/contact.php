<?php 
include('includes/header.php'); 
include('config/contact-info.php'); 
?>

<main class="contact-page">
    <div class="content-card">
        <header class="contact-header">
            <h1>Contact Us</h1>
        </header>
        
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
    </div> <!-- end content-card div /-->
</main> <!-- end contact-page main /-->

<?php include('includes/footer.php'); ?>