<?php
/**
 * UI COMPONENT: Weather
 */

$weatherLocation = $location ?? config('weather_location', 'Kell, IL');
$cachePath = ROOT_PATH . 'kgs-cache/weather_data.json';
$weatherData = null;

// 1. CACHE LOGIC: Only fetch from API once every 30 minutes
if (file_exists($cachePath) && (time() - filemtime($cachePath) < 1800)) {
    $weatherData = json_decode(file_get_contents($cachePath), true);
}

if (!$weatherData) {
    // API URL for Kell, IL (National Weather Service)
    $api_url = 'https://api.weather.gov/gridpoints/LSX/141,70/forecast';
    
    $options = ['http' => ['header' => "User-Agent: KGSWebsite/1.0 (contact@kellgradeschool.com)\r\n"]];
    $context = stream_context_create($options);
    $response = @file_get_contents($api_url, false, $context);

    if ($response) {
        $decoded = json_decode($response, true);
        if (isset($decoded['properties']['periods'])) {
            $weatherData = $decoded['properties']['periods'];
            file_put_contents($cachePath, json_encode($weatherData));
        }
    }
}

// EARLY EXIT: If API is down and no cache exists, hide component
if (empty($weatherData)) {
    echo "<!-- Weather Hidden: No location set in config -->";
    return;
}

$current = $weatherData[0];
?>

<div class="card border-0 shadow-sm kgs-weather-widget mb-4">
    <?php if (!empty($title) && $title !== 'None'): ?>
        <h5 class="rich-text-title"><span><?= htmlspecialchars($title) ?></span></h5>
    <?php endif; ?>

    <div class="card-body text-center">
        <h6 class="text-muted text-uppercase small letter-spacing-1"><?= htmlspecialchars($weatherLocation) ?></h6>
        
        <!-- Skycons Animated Icon -->
        <canvas id="weather-icon" width="100" height="100" data-icon="<?= $current['shortForecast'] ?>"></canvas>
        
        <div class="kgs-weather-temp"><?= $current['temperature'] ?>&deg;F</div>
        <p class="mb-1 fw-bold"><?= $current['shortForecast'] ?></p>
        <p class="small text-muted mb-3">Wind: <?= $current['windSpeed'] ?> from <?= $current['windDirection'] ?></p>

        <!-- Forecast Accordion -->
        <button class="btn btn-sm w-100 kgs-weather-forecast-toggle d-flex justify-content-between align-items-center">
            <span>Upcoming Forecast</span>
            <i class="fa-solid fa-chevron-down transition-transform"></i>
        </button>
        
        <div class="kgs-weather-panel text-start mt-3">
            <?php for ($i = 1; $i < 8; $i++): $p = $weatherData[$i]; ?>
                <div class="forecast-row">
                    <span class="forecast-day"><?= $p['name'] ?></span>
                    <span class="small"><?= $p['temperature'] ?>&deg;F - <?= $p['shortForecast'] ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
/**
 * Weather UI Logic
 * Initialized only once if multiple widgets exist
 */
if (typeof kgsWeatherInit === 'undefined') {
    const kgsWeatherInit = true;
    
    // 1. Handle Accordion
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('.kgs-weather-forecast-toggle');
        if (toggle) {
            const panel = toggle.nextElementSibling;
            const icon = toggle.querySelector('.fa-chevron-down');
            const isVisible = window.getComputedStyle(panel).display !== 'none';
            
            panel.style.display = isVisible ? 'none' : 'block';
            icon.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    });

    // 2. Initialize Skycons
    const loadSkycons = () => {
        const script = document.createElement('script');
        script.src = 'https://darkskyapp.github.io/skycons/skycons.js';
        script.onload = () => {
            const skycons = new Skycons({"color": "#015BA7"});
            const canvas = document.getElementById('weather-icon');
            if(!canvas) return;

            const forecast = canvas.dataset.icon.toLowerCase();
            let iconType = Skycons.CLEAR_DAY;

            if (forecast.includes('partly cloudy')) iconType = Skycons.PARTLY_CLOUDY_DAY;
            else if (forecast.includes('cloudy')) iconType = Skycons.CLOUDY;
            else if (forecast.includes('rain') || forecast.includes('showers')) iconType = Skycons.RAIN;
            else if (forecast.includes('sleet')) iconType = Skycons.SLEET;
            else if (forecast.includes('snow')) iconType = Skycons.SNOW;
            else if (forecast.includes('wind')) iconType = Skycons.WIND;
            else if (forecast.includes('fog')) iconType = Skycons.FOG;

            skycons.add("weather-icon", iconType);
            skycons.play();
        };
        document.head.appendChild(script);
    };
    loadSkycons();
}
</script>