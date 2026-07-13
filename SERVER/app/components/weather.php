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
$collapsedConfig = config('weather_collapsed', 'true');
$isCollapsed = ($collapsedConfig === 'true' || $collapsedConfig === '1' || $collapsedConfig === true);
?>

<style>
.kgs-weather-header {
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
}
.kgs-weather-header:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
.kgs-weather-main-icon {
    display: block;
    max-width: 50px;
    height: auto;
}
.weather-sub-icon {
    display: block;
    max-width: 35px;
    height: auto;
    margin: 4px auto;
}
.kgs-weather-widget .card-body {
    padding: 0;
}
</style>

<div class="kgs-widget-container">
    <?php if (!empty($title) && $title !== 'None'): ?>
        <h5 class="rich-text-title"><span><?= htmlspecialchars($title) ?></span></h5>
    <?php endif; ?>

    <div class="card border-0 shadow-sm kgs-weather-widget mb-4">
        <!-- Header Bar -->
        <div class="kgs-weather-header p-3 d-flex align-items-center justify-content-between select-none">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 rounded bg-primary bg-opacity-10 text-primary">
                    <canvas class="kgs-weather-main-icon" data-icon="<?= htmlspecialchars($current['shortForecast']) ?>" width="50" height="50"></canvas>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-xs font-semibold text-uppercase tracking-wider text-muted small">Local Weather</span>
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary font-medium" style="font-size: 10px;"><?= htmlspecialchars($weatherLocation) ?></span>
                    </div>
                    <p class="m-0 fw-bold text-dark" style="font-size: 14px;">
                        <?= $current['temperature'] ?>&deg;F &mdash; <?= htmlspecialchars($current['shortForecast']) ?>
                    </p>
                </div>
            </div>
            <button class="btn btn-link text-muted p-1 kgs-weather-header-toggle">
                <i class="fa-solid <?= $isCollapsed ? 'fa-chevron-down' : 'fa-chevron-up' ?> fs-6"></i>
            </button>
        </div>

        <!-- Expanded Forecast Panel -->
        <div class="kgs-weather-panel border-top bg-light p-3" style="<?= $isCollapsed ? 'display: none;' : '' ?>">
            <!-- Detailed Info -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 bg-white p-2 rounded border shadow-sm">
                        <i class="fa-solid fa-thermometer text-warning fs-5"></i>
                        <div>
                            <span class="text-muted block fw-medium" style="font-size: 10px; display: block; line-height: 1.2;">Feels Like</span>
                            <span class="fw-bold text-dark" style="font-size: 12px;"><?= $current['temperature'] ?>&deg;F</span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 bg-white p-2 rounded border shadow-sm">
                        <i class="fa-solid fa-wind text-info fs-5"></i>
                        <div>
                            <span class="text-muted block fw-medium" style="font-size: 10px; display: block; line-height: 1.2;">Wind Speed</span>
                            <span class="fw-bold text-dark" style="font-size: 12px;"><?= htmlspecialchars($current['windSpeed'] ?? '7 mph') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted bg-white p-3 rounded border mb-3 italic" style="font-size: 11px; line-height: 1.4; font-style: italic;">
                &ldquo;<?= htmlspecialchars($current['detailedForecast']) ?>&rdquo;
            </p>

            <!-- 3-day overview -->
            <div class="border-top pt-3">
                <h6 class="text-uppercase text-muted fw-bold mb-2" style="font-size: 10px; letter-spacing: 0.5px;">3-Day Forecast Cycle</h6>
                <div class="row g-2">
                    <?php for ($i = 1; $i < 4; $i++): 
                        if (isset($weatherData[$i])): 
                            $p = $weatherData[$i];
                    ?>
                        <div class="col-4">
                            <div class="bg-white p-2 rounded border text-center d-flex flex-column align-items-center gap-1 shadow-sm">
                                <span class="text-muted fw-bold truncate w-100" style="font-size: 10px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></span>
                                <canvas class="weather-sub-icon" data-icon="<?= htmlspecialchars($p['shortForecast']) ?>" width="35" height="35"></canvas>
                                <span class="fw-extrabold text-dark" style="font-size: 12px; font-weight: 800;"><?= $p['temperature'] ?>&deg;F</span>
                                <span class="text-muted truncate w-100" style="font-size: 8px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($p['shortForecast']) ?>"><?= htmlspecialchars($p['shortForecast']) ?></span>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endfor; ?>
                </div>
            </div>
            
            <div class="text-end text-muted mt-2 font-monospace" style="font-size: 9px;">
                Source: weather.gov
            </div>
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
        const header = e.target.closest('.kgs-weather-header');
        if (header) {
            const widget = header.closest('.kgs-weather-widget');
            if (widget) {
                const panel = widget.querySelector('.kgs-weather-panel');
                const icon = widget.querySelector('.kgs-weather-header-toggle i');
                if (panel && icon) {
                    const isVisible = panel.style.display !== 'none';
                    if (isVisible) {
                        panel.style.display = 'none';
                        icon.className = 'fa-solid fa-chevron-down fs-6';
                    } else {
                        panel.style.display = 'block';
                        icon.className = 'fa-solid fa-chevron-up fs-6';
                    }
                }
            }
        }
    });

    // 2. Initialize Skycons
    const loadSkycons = () => {
        if (window.Skycons) {
            initSkycons();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://darkskyapp.github.io/skycons/skycons.js';
        script.onload = () => {
            initSkycons();
        };
        document.head.appendChild(script);
    };

    const initSkycons = () => {
        const skycons = new Skycons({"color": "#015BA7"});
        
        function getSkyconType(forecast) {
            if (!forecast) return Skycons.CLEAR_DAY;
            const desc = forecast.toLowerCase();
            if (desc.includes('partly cloudy') || desc.includes('partly sunny')) return Skycons.PARTLY_CLOUDY_DAY;
            if (desc.includes('mostly cloudy')) return Skycons.CLOUDY;
            if (desc.includes('cloudy')) return Skycons.CLOUDY;
            if (desc.includes('rain') || desc.includes('showers') || desc.includes('storm') || desc.includes('thunderstorm')) return Skycons.RAIN;
            if (desc.includes('sleet')) return Skycons.SLEET;
            if (desc.includes('snow')) return Skycons.SNOW;
            if (desc.includes('wind')) return Skycons.WIND;
            if (desc.includes('fog')) return Skycons.FOG;
            return Skycons.CLEAR_DAY;
        }

        // Initialize main weather icon
        document.querySelectorAll('.kgs-weather-main-icon').forEach((canvas, idx) => {
            const forecast = canvas.dataset.icon;
            const id = 'weather-icon-' + idx;
            canvas.id = id;
            skycons.add(id, getSkyconType(forecast));
        });

        // Initialize sub-icons
        document.querySelectorAll('.weather-sub-icon').forEach((canvas, idx) => {
            const forecast = canvas.dataset.icon;
            const id = 'weather-sub-icon-' + idx;
            canvas.id = id;
            skycons.add(id, getSkyconType(forecast));
        });

        skycons.play();
    };
    loadSkycons();
}
</script>

