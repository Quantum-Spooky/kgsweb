<?php
$config = require __DIR__ . '/../config/config.php';
// Use the exact base URL from config
$base = rtrim($config['base_url'], '/'); 
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($config['debug_mode']) && $config['debug_mode']) {
    header("X-LiteSpeed-Purge: *");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-LiteSpeed-Cache-Control: no-cache");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	
    <?php $fav = $base . '/favicon/'; ?>
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo $fav; ?>apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo $fav; ?>apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $fav; ?>apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo $fav; ?>apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $fav; ?>apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo $fav; ?>apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo $fav; ?>apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $fav; ?>apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $fav; ?>apple-icon-180x180.png">
    
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $fav; ?>android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $fav; ?>favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo $fav; ?>favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $fav; ?>favicon-16x16.png"> 
    <link rel="manifest" href="<?php echo $fav; ?>manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo $fav; ?>ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
	
    <title>Kell Grade School</title>
	
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Condiment&family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Playwrite+DE+Grund:wght@100..400&family=Teachers:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

	<?php
			// 1. SERVER PATH: For PHP to look at the folders (Physical location)
			// This moves up from /includes to root, then into /css
			$css_server_path = dirname(__DIR__) . '/css'; 
			
			// 2. WEB URI: For the Browser to find the files (URL location)
			// This uses your https://kellgradeschool.com/public/kgsweb2026/css
			$css_web_uri = $base . '/css';

			// Versioning logic
			$v = (isset($config['debug_mode']) && $config['debug_mode']) ? time() : ($config['version'] ?? '1.0');

			if (is_dir($css_server_path)) {
				$files = scandir($css_server_path);
				foreach ($files as $file) {
					if (pathinfo($file, PATHINFO_EXTENSION) === 'css' && $file !== '.' && $file !== '..') {
						// We use the WEB URI here so the browser gets the full correct path
						echo '<link rel="stylesheet" href="' . $css_web_uri . '/' . $file . '?v=' . $v . '">' . PHP_EOL;
					}
				}
			}
		?>
	
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div id="site-container">
        <div id="header">
            <div id="site-masthead" class="site-masthead-inline">
                <div id="site-logo">
                    <a href="<?php echo $base; ?>/index.php">
                        <img src="https://kellgradeschool.com/wp-content/uploads/2025/12/cropped-Kell-Indian-Head-2.png" alt="Kell Logo">
                    </a>
                </div>
                <div id="site-title-full" class="site-title"><a href="<?php echo $base; ?>/index.php">Kell Grade School</a></div>
                <div id="site-title-short" class="site-title"><a href="<?php echo $base; ?>/index.php">Kell Grade School</a></div>
            </div>
			
            <input type="checkbox" id="menu-checkbox">
            <label for="menu-checkbox" class="menu-toggle"><i class="fa-solid fa-bars"></i> Menu</label>	

            <div id="top-menu">
                <ul class="top-menu-ul">
                    <li class="top-menu-li <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/index.php">Home</a>
                    </li>

                    <li class="top-menu-li <?php echo ($current_page == 'school-district.php' || $current_page == 'staff-directory.php') ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/school-district.php">Office <i class="fa-solid fa-caret-down"></i></a>
                        <div class="dropdown-content">
                            <a href="<?php echo $base; ?>/school-district.php#about-kgs">About Kell School</a>
							<a href="<?php echo $base; ?>/school-district.php#contact.php">Contact Us</a>
                            <a href="<?php echo $base; ?>/school-district.php#school-board">School Board</a>
                            <a href="<?php echo $base; ?>/school-district.php#school-district-documents">Documents</a>
                            <a href="<?php echo $base; ?>/staff-directory.php">Staff Directory</a>
                        </div>
                    </li>

                    <li class="top-menu-li <?php echo ($current_page == 'calendar.php') ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/calendar.php">Calendar</a>
                    </li>

                    <li class="top-menu-li align-right <?php echo ($current_page == 'cafeteria.php') ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/cafeteria.php">Dining</a>
                    </li>

                    <li class="hidden top-menu-li align-right <?php echo (strpos($_SERVER['PHP_SELF'], '/academics/') !== false) ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/academics/index.php">Academics <i class="fa-solid fa-caret-down"></i></a>
                        <div class="dropdown-content">
                            <a href="<?php echo $base; ?>/academics/pre-k.php">Pre-K</a>
                            <a href="<?php echo $base; ?>/academics/kindergarten.php">Kindergarten</a>
                            <a href="<?php echo $base; ?>/academics/grade-1.php">1st Grade</a>
                            <a href="<?php echo $base; ?>/academics/grade-2-3.php">2nd & 3rd Grade</a>
                            <a href="<?php echo $base; ?>/academics/grade-4-5.php">4th & 5th Grade</a>
                            <a href="<?php echo $base; ?>/academics/junior-high.php">Junior High</a>
                            <a href="<?php echo $base; ?>/academics/special-education.php">Mrs. Burk</a>
                            <a href="<?php echo $base; ?>/academics/title-i.php">Mrs. Donoho</a>
                        </div>
                    </li>

                    <li class="hidden top-menu-li align-right <?php echo (strpos($_SERVER['PHP_SELF'], '/sports/') !== false) ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/sports/index.php">Sports <i class="fa-solid fa-caret-down"></i></a>
                        <div class="dropdown-content">
                            <a href="<?php echo $base; ?>/sports/baseball.php">Baseball</a>
                            <a href="<?php echo $base; ?>/sports/basketball.php">Basketball</a>
                            <a href="<?php echo $base; ?>/sports/bowling.php">Bowling</a>
                            <a href="<?php echo $base; ?>/sports/cheerleading.php">Cheerleading</a>
                            <a href="<?php echo $base; ?>/sports/cross-country.php">Cross Country</a>
                            <a href="<?php echo $base; ?>/clubs/scholar-bowl.php">Scholar Bowl</a>
                            <a href="<?php echo $base; ?>/sports/volleyball.php">Volleyball</a>
                        </div>
                    </li>

                    <li class="hidden top-menu-li align-right <?php echo (strpos($_SERVER['PHP_SELF'], '/clubs/') !== false) ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/clubs/index.php">Clubs <i class="fa-solid fa-caret-down"></i></a>
                        <div class="dropdown-content">
                            <a href="<?php echo $base; ?>/clubs/student-council.php">Student Council</a>
                            <a href="<?php echo $base; ?>/clubs/yearbook.php">Yearbook</a>
                        </div>
                    </li>
					
					<li class="top-menu-li align-right <?php echo ($current_page == 'pto.php') ? 'active' : ''; ?>">
                        <a href="<?php echo $base; ?>/pto.php">Family <i class="fa-solid fa-caret-down"></i></a>
                        <div class="dropdown-content">
                            <a href="<?php echo $base; ?>/pto.php">PTO</a>
                        </div>
                    </li>
					
                </ul>
            </div>
        </div>
		
		<div id="site-main"> <!-- beginning of site-main /-->