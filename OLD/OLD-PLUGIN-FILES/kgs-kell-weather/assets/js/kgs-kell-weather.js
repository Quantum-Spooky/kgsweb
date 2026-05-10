jQuery(document).ready(function($) {
    console.log("jQuery document ready");

    $('.accordion').click(function() {
        console.log("Accordion clicked");
        $('.panel').toggleClass('active');
        var panel = $('.panel');
        panel.slideToggle();
        $('.accordion').find('.caret').html($('.panel').hasClass('active') ? '&#9650;' : '&#9660;');
    });

    // Ensure Skycons is fully loaded
    function waitForSkycons(callback) {
        var checkSkycons = setInterval(function() {
            if (typeof Skycons !== 'undefined') {
                clearInterval(checkSkycons);
                callback();
            }
        }, 100);
    }

    waitForSkycons(function() {
        console.log("Skycons library loaded");

        fetch('https://api.weather.gov/points/38.4913,-88.9033') // Replace with your desired coordinates
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("API data received:", data);
                const weather_url = data.properties.forecast;
                return fetch(weather_url);
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(weatherData => {
                console.log("Weather data received:", weatherData);
                const currentConditions = weatherData.properties.periods[0];
                console.log("Current conditions:", currentConditions);
                const icon = mapWeatherToIcon(currentConditions.shortForecast);
                console.log("Mapped weather icon:", icon);
                displayWeather(icon);
            })
            .catch(error => {
                console.error('Error fetching weather data:', error);
            });
    });

    function mapWeatherToIcon(forecast) {
        console.log("Mapping forecast to icon:", forecast);
        forecast = forecast.toLowerCase();
        if (forecast.includes('clear')) return 'CLEAR_DAY';
        if (forecast.includes('partly cloudy')) return 'PARTLY_CLOUDY_DAY';
        if (forecast.includes('cloudy')) return 'CLOUDY';
        if (forecast.includes('rain')) return 'RAIN';
        if (forecast.includes('sleet')) return 'SLEET';
        if (forecast.includes('snow')) return 'SNOW';
        if (forecast.includes('wind')) return 'WIND';
        if (forecast.includes('fog')) return 'FOG';
        return 'CLEAR_DAY';
    }

    function displayWeather(icon) {
        console.log("Displaying weather icon:", icon);
        const skycons = new Skycons({"color": "#000000"});
        console.log("Skycons object:", skycons); // Debugging Skycons object

        if (!Skycons[icon]) {
            console.error("Invalid Skycons icon:", icon);
            icon = 'CLEAR_DAY'; // Default icon if mapping fails
        }

        // Verify the canvas element
        const canvas = document.getElementById("weather-icon");
        if (canvas) {
            console.log("Canvas element found:", canvas);
            skycons.add("weather-icon", Skycons[icon]);
            skycons.play();
            // Check if icon is added
            console.log("Icon added to canvas:", Skycons[icon]); // Debug icon added
        } else {
            console.error("Canvas element not found!");
        }
    }
});