// js/weather.js
async function fetchWeather() {
    const elTemp = document.getElementById('temperature');
    if (!elTemp) return; // Exit if widget isn't on this page

    try {
        const response = await fetch('api/get-data.php?type=weather');
        const data = await response.json();

        if (data.current) {
            // Update Current
            elTemp.innerText = data.current.temp;
            if (document.getElementById('weather-icon')) 
                document.getElementById('weather-icon').src = data.current.icon;
            if (document.getElementById('detailed-forecast')) 
                document.getElementById('detailed-forecast').innerText = data.current.detailed;
            if (document.getElementById('wind-info')) 
                document.getElementById('wind-info').innerText = `Wind: ${data.current.wind}`;
            if (document.getElementById('weather-time')) 
                document.getElementById('weather-time').innerText = data.current.time;
            if (document.getElementById('weather-date')) 
                document.getElementById('weather-date').innerText = data.current.date;
            
            // Update Forecast Panel
            const panel = document.querySelector('.weather-forecast-panel');
            if (panel && data.forecast) {
                panel.innerHTML = data.forecast.map(p => `
                    <div class="forecast-box">
                        <strong class="period-name">${p.name}:</strong>
                        <span class="detailed-forecast">${p.detailedForecast}</span>
                    </div>
                `).join('');
            }
        }
    } catch (e) {
        console.error("Weather Error:", e);
    }
}

// Logic for the accordion toggle
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.weather-forecast-accordion');
    if (!btn) return;

    const panel = btn.nextElementSibling;
    const caret = btn.querySelector('.fa-caret-down');
    
    // Toggle using a class or explicit display
    const isHidden = !panel.style.display || panel.style.display === 'none';
    
    panel.style.display = isHidden ? 'block' : 'none';
    btn.setAttribute('aria-expanded', isHidden);
    
    if (caret) {
        caret.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
    }
});

fetchWeather();