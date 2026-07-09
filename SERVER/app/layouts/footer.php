<?php
/**
 * app/layouts/footer.php
 * Site Footer (layout component)
 * 
 * Responsibility:
 * - Define the bottom structure of every page.
 * - Handle the hidden manual cache refresh trigger.
 * - Display the site heartbeat (Last Updated timestamp).
 * - Render the floating Back to Top utility.
 */

// Read the last refresh heartbeat
$heartbeatFile = ROOT_PATH . 'kgs-cache/google/last_refresh.json';
$lastUpdate = "Pending...";
if (file_exists($heartbeatFile)) {
    $hb = json_decode(file_get_contents($heartbeatFile), true);
    $lastUpdate = $hb['date_human'] ?? "Unknown";
}
?>

</main> <!-- Closes the tag opened in header.php -->

<footer class="bg-dark text-white py-5 mt-auto border-top border-secondary">
    <div class="container">
        <div class="row g-4">
            
            <!-- Column 1: Contact Info (Self-Hydrating) -->
            <div class="col-md-4">
                <?php render_component('contact-info'); ?>
            </div>

            <!-- Column 2: Quick Links (Managed via Sheet) -->
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <?php 
                render_component('link-list', [
                    'links' => get_link_group('Footer Links: Quick Links')
                ]); 
                ?>
            </div>

            <!-- Column 3: Safety & Resources (Managed via Sheet) -->
            <div class="col-md-4">
                <h5>Safety & Resources</h5>
                <?php 
                render_component('link-list', [
                    'links' => get_link_group('Footer Links: Safety and Resources')
                ]); 
                ?>
            </div>	

        </div>

        <hr class="my-4 opacity-25">

        <div class="text-center small opacity-50">
            <!-- TASK 11: The Copyright Trigger (3 clicks to sync) -->
            <span id="copyright-trigger" style="cursor: pointer; user-select: none;">&copy;</span> 
            <?= date('Y') ?> <?= htmlspecialchars(config('district_name')) ?> • All Rights Reserved
            <br>
            <!-- TASK 12: Last Updated Indicator -->
            <div class="mt-2" style="font-size: 0.7rem;">Site Last Updated: <?= $lastUpdate ?></div>
        </div>
    </div>
</footer>

<!-- UI ELEMENTS: TOAST & BACK TO TOP -->
<div id="kgs-toast" class="kgs-toast"></div>

<button id="back-to-top" class="btn btn-primary shadow" title="Back to top">
       <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- CORE SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function() {
    // --- 1. BACK TO TOP LOGIC ---
    const btt = document.getElementById("back-to-top");
    window.onscroll = function() {
        if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
            btt.style.display = "flex"; // Flex ensures icon centering
        } else {
            btt.style.display = "none";
        }
    };
    btt.onclick = function() { window.scrollTo({top: 0, behavior: 'smooth'}); };

    // --- 2. REFRESH TRIGGER LOGIC (Task 11) ---
    let clickCount = 0;
    let timer = null;
    let countdownInterval = null;
    const trigger = document.getElementById('copyright-trigger');
    const toast = document.getElementById('kgs-toast');

    function showKgsToast(message, type = 'success', persist = false) {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'kgs-toast show kgs-toast--' + type;
        if (!persist) { 
            setTimeout(() => { toast.classList.remove('show'); }, 4000); 
        }
    }

    if (trigger) {
        trigger.addEventListener('click', function() {
            clickCount++;
            clearTimeout(timer);
            if (clickCount === 3) {
                clickCount = 0;
                showKgsToast("Initiating Sync...", "success", true);

                // Fetch with cache-buster
                fetch('<?= config('base_url') ?>refresh-cache.php?t=' + Date.now(), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let secondsLeft = 30;
                        showKgsToast(`✓ Sync started. Refreshing in ${secondsLeft}s...`, "success", true);
                        
                        countdownInterval = setInterval(() => {
                            secondsLeft--;
                            if (secondsLeft > 0) {
                                toast.textContent = `✓ Syncing Google data... Refreshing in ${secondsLeft}s`;
                            } else {
                                clearInterval(countdownInterval);
                                toast.textContent = "Reloading...";
                                // Hard refresh to bust mobile Safari cache
                                window.location.href = window.location.pathname + '?refresh=' + Date.now();
                            }
                        }, 1000);
                    } else {
                        showKgsToast("✗ " + data.message, "error");
                    }
                })
                .catch(err => {
                    console.error("Refresh Trigger Error:", err);
                    showKgsToast("✗ Connection Error", "error");
                });
            }
            timer = setTimeout(() => { clickCount = 0; }, 1500); 
        });
    }
})();
</script>