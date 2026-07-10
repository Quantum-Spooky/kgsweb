import React, { useState, useEffect } from 'react';
import { Cloud, Sun, CloudRain, Wind, Thermometer, ChevronDown, ChevronUp, MapPin } from 'lucide-react';
import { WeatherForecast, SiteConfig } from '../types';

interface WeatherWidgetProps {
  config: SiteConfig;
}

export default function WeatherWidget({ config }: WeatherWidgetProps) {
  const [weather, setWeather] = useState<WeatherForecast | null>(null);
  const [collapsed, setCollapsed] = useState(true);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Fetch live weather from our real API endpoint
    fetch('/api/weather')
      .then((res) => res.json())
      .then((data) => {
        setWeather(data);
        setLoading(false);
      })
      .catch((err) => {
        console.error("Failed to fetch weather", err);
        setLoading(false);
      });
  }, [config.weather_location]);

  if (loading) {
    return (
      <div className="bg-white rounded-xl border border-gray-100 p-6 flex items-center justify-center h-28 animate-pulse">
        <span className="text-sm text-gray-400">Retrieving weather.gov forecast...</span>
      </div>
    );
  }

  if (!weather) return null;

  // Map weather.gov descriptions to custom Lucide icons
  const getWeatherIcon = (forecast: string) => {
    const desc = forecast.toLowerCase();
    if (desc.includes('sunny') || desc.includes('clear')) {
      return <Sun className="text-amber-500 animate-spin-slow" size={28} />;
    }
    if (desc.includes('rain') || desc.includes('shower') || desc.includes('storm')) {
      return <CloudRain className="text-blue-500" size={28} />;
    }
    return <Cloud className="text-gray-400" size={28} />;
  };

  return (
    <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" id="weather-widget">
      {/* Header Bar */}
      <div 
        onClick={() => setCollapsed(!collapsed)}
        className="p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors select-none"
      >
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-blue-50 text-blue-600">
            {getWeatherIcon(weather.shortForecast)}
          </div>
          <div>
            <div className="flex items-center gap-1.5">
              <span className="text-xs font-semibold uppercase tracking-wider text-gray-500">Local Weather</span>
              <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-800 font-medium">{config.weather_location || "62853"}</span>
            </div>
            <p className="text-sm font-bold text-gray-800 flex items-center gap-1">
              {weather.temperature}°F — {weather.shortForecast}
            </p>
          </div>
        </div>
        <button className="text-gray-400 hover:text-gray-600 p-1">
          {collapsed ? <ChevronDown size={18} /> : <ChevronUp size={18} />}
        </button>
      </div>

      {/* Expanded Forecast Panel */}
      {!collapsed && (
        <div className="border-t border-gray-100 bg-gray-50/50 p-4 space-y-4 animate-fadeIn">
          {/* Detailed Info */}
          <div className="grid grid-cols-2 gap-4">
            <div className="flex items-center gap-2 bg-white p-2.5 rounded-lg border border-gray-100 shadow-2xs">
              <Thermometer className="text-orange-500" size={16} />
              <div>
                <span className="text-[10px] text-gray-400 block font-medium">Feels Like</span>
                <span className="text-xs font-bold text-gray-700">{weather.temperature}°F</span>
              </div>
            </div>
            <div className="flex items-center gap-2 bg-white p-2.5 rounded-lg border border-gray-100 shadow-2xs">
              <Wind className="text-sky-500" size={16} />
              <div>
                <span className="text-[10px] text-gray-400 block font-medium">Wind Speed</span>
                <span className="text-xs font-bold text-gray-700">{weather.windSpeed || "7 mph"}</span>
              </div>
            </div>
          </div>

          <p className="text-xs text-gray-600 leading-relaxed italic bg-white p-3 rounded-lg border border-gray-100">
            "{weather.detailedForecast}"
          </p>

          {/* 3-day overview */}
          <div className="space-y-2 border-t border-gray-100 pt-3">
            <h4 className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">3-Day Forecast Cycle</h4>
            <div className="grid grid-cols-3 gap-2">
              {weather.periods.slice(1, 4).map((p, idx) => (
                <div key={idx} className="bg-white p-2 rounded-lg border border-gray-100 text-center flex flex-col items-center gap-1">
                  <span className="text-[10px] font-bold text-gray-500 truncate w-full">{p.name}</span>
                  {getWeatherIcon(p.shortForecast)}
                  <span className="text-xs font-extrabold text-gray-800">{p.temperature}°F</span>
                  <span className="text-[8px] text-gray-400 leading-tight truncate w-full">{p.shortForecast}</span>
                </div>
              ))}
            </div>
          </div>
          
          <div className="text-[9px] text-right text-gray-400 font-mono">
            Source: weather.gov
          </div>
        </div>
      )}
    </div>
  );
}
