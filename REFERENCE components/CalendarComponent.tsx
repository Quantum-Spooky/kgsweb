import React, { useState, useEffect } from 'react';
import { Calendar as CalendarIcon, Filter, Layers, List, Table, ChevronRight, ChevronLeft, MapPin, Clock, Globe, Download, Eye } from 'lucide-react';
import { SiteConfig } from '../types';

interface CalendarComponentProps {
  config: SiteConfig;
}

interface SchoolEvent {
  id: string;
  title: string;
  date: string;
  time: string;
  location: string;
  category: 'kgs' | 'board' | 'sports' | 'pto';
}

const schoolEvents: SchoolEvent[] = [
  { id: "e-1", title: "KGS Back-to-School Picnic", date: "2026-08-15", time: "5:00 PM - 7:00 PM", location: "KGS Playground", category: "pto" },
  { id: "e-2", title: "First Day of School (Early Dismissal)", date: "2026-08-18", time: "8:00 AM - 11:30 AM", location: "Kell Grade School Campus", category: "kgs" },
  { id: "e-3", title: "Regular School Board Meeting", date: "2026-08-20", time: "6:00 PM", location: "KGS Main Library", category: "board" },
  { id: "e-4", title: "KGS Boys Baseball vs. Iuka", date: "2026-08-24", time: "4:30 PM", location: "Iuka Athletic Fields", category: "sports" },
  { id: "e-5", title: "Regular School Board Meeting - Budget Hearing", date: "2026-09-17", time: "6:00 PM", location: "KGS Main Library", category: "board" },
  { id: "e-6", title: "KGS Girls Volleyball vs. Raccoon", date: "2026-09-22", time: "5:00 PM", location: "KGS Gymnasium", category: "sports" },
  { id: "e-7", title: "Fall PTO Cookie Dough Fundraiser Begins", date: "2026-10-02", time: "All Day", location: "KGS District", category: "pto" }
];

export default function CalendarComponent({ config }: CalendarComponentProps) {
  const [activeCategoryFilters, setActiveCategoryFilters] = useState<Record<string, boolean>>({
    kgs: true,
    board: true,
    sports: true,
    pto: true
  });
  const [viewType, setViewType] = useState<'embed' | 'agenda' | 'pdf'>('embed');
  const [activePdfDoc, setActivePdfDoc] = useState<'academic' | 'monthly'>('academic');
  const [calendarPaths, setCalendarPaths] = useState({
    academic: {
      pdf: "/gdrive/kgsweb_public/Calendar/Academic Calendar/Academic Calendar (2025-2026).pdf",
      png: "/gdrive/kgsweb_public/Calendar/Academic Calendar/Academic-Calendar-2025-2026.png"
    },
    monthly: {
      pdf: "/gdrive/kgsweb_public/Calendar/Monthly Calendar/May 2026.pdf",
      png: "/gdrive/kgsweb_public/Calendar/Monthly Calendar/Dec 2025 monthly calendar.png"
    }
  });

  const primaryColor = config.color_primary || '#015BA7';

  // Load actual mapped calendar file paths from server
  useEffect(() => {
    fetch('/api/calendars')
      .then((res) => res.ok ? res.json() : null)
      .then((data) => {
        if (data) {
          setCalendarPaths(data);
        }
      })
      .catch((err) => console.error("Error loading calendar configurations", err));
  }, []);

  // Toggle category
  const toggleCategory = (cat: string) => {
    setActiveCategoryFilters(prev => ({
      ...prev,
      [cat]: !prev[cat]
    }));
  };

  const filteredEvents = schoolEvents.filter(e => activeCategoryFilters[e.category]);

  const categories = [
    { key: 'kgs', label: 'School Events', color: 'bg-blue-600' },
    { key: 'board', label: 'School Board Meetings', color: 'bg-purple-600' },
    { key: 'sports', label: 'Sports', color: 'bg-green-600' },
    { key: 'pto', label: 'PTO Events', color: 'bg-orange-500' }
  ];

  const currentPath = activePdfDoc === 'academic' 
    ? (calendarPaths.academic.pdf || calendarPaths.academic.png) 
    : (calendarPaths.monthly.pdf || calendarPaths.monthly.png);

  const isImage = currentPath && (
    currentPath.toLowerCase().endsWith('.png') || 
    currentPath.toLowerCase().endsWith('.jpg') || 
    currentPath.toLowerCase().endsWith('.jpeg')
  );

  return (
    <div className="space-y-6" id="calendar-module">
      {/* View Switcher Controls */}
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 bg-white p-4 rounded-xl border border-gray-100 shadow-2xs">
        <div className="flex items-center gap-3">
          <CalendarIcon className="text-blue-600" style={{ color: primaryColor }} size={24} />
          <div>
            <h3 className="text-base font-bold text-gray-900 leading-tight">School Calendars & Schedules</h3>
            <p className="text-xs text-gray-400">View upcoming events or access static calendar PDFs</p>
          </div>
        </div>
        
        <div className="flex bg-gray-50 p-1 rounded-lg border border-gray-100 self-start sm:self-auto gap-1">
          <button
            onClick={() => setViewType('embed')}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer ${
              viewType === 'embed' ? 'bg-white shadow-2xs text-gray-900 font-bold' : 'text-gray-500 hover:text-gray-900'
            }`}
          >
            <Globe size={14} /> Google Calendar
          </button>
          <button
            onClick={() => setViewType('agenda')}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer ${
              viewType === 'agenda' ? 'bg-white shadow-2xs text-gray-900 font-bold' : 'text-gray-500 hover:text-gray-900'
            }`}
          >
            <List size={14} /> Agenda List
          </button>
          <button
            onClick={() => setViewType('pdf')}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer ${
              viewType === 'pdf' ? 'bg-white shadow-2xs text-gray-900 font-bold' : 'text-gray-500 hover:text-gray-900'
            }`}
          >
            <Table size={14} /> PDF Documents
          </button>
        </div>
      </div>

      {viewType === 'embed' ? (
        /* Live Google Calendar Embed [TASK 13, 62, 63] */
        <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm overflow-hidden" id="google-calendar-embed">
          <div className="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
            <span className="text-xs font-extrabold text-gray-500">Live Google Calendar: Home of the Indians</span>
            <span className="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-mono font-bold">America/Chicago</span>
          </div>
          <div className="w-full h-[650px] bg-gray-50 rounded-lg overflow-hidden border border-gray-100 relative">
            <iframe
              src={`https://calendar.google.com/calendar/embed?src=${encodeURIComponent(config.google_calendar_id || 'c_35c7f773dea0cc46099f7607201bed993a0a29d94d5456aa00594ed16ffb5071@group.calendar.google.com')}&ctz=America%2FChicago`}
              className="w-full h-full"
              title="Google Calendar Live Feed"
              style={{ border: 'none' }}
            />
          </div>
        </div>
      ) : viewType === 'agenda' ? (
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
          {/* Filters Sidebar */}
          <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm space-y-4 lg:col-span-1 self-start">
            <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 flex items-center gap-1.5">
              <Filter size={12} /> Filter Calendars
            </h4>
            <div className="space-y-2.5">
              {categories.map((cat) => (
                <button
                  key={cat.key}
                  onClick={() => toggleCategory(cat.key)}
                  className="w-full flex items-center justify-between p-2.5 rounded-lg border text-left transition-all text-xs font-semibold cursor-pointer"
                  style={{
                    borderColor: activeCategoryFilters[cat.key] ? primaryColor : '#f3f4f6',
                    backgroundColor: activeCategoryFilters[cat.key] ? `${primaryColor}05` : '#ffffff'
                  }}
                >
                  <div className="flex items-center gap-2">
                    <span className={`w-3 h-3 rounded-full ${cat.color}`} />
                    <span className="text-gray-700">{cat.label}</span>
                  </div>
                  <input
                    type="checkbox"
                    checked={activeCategoryFilters[cat.key]}
                    readOnly
                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-3.5 w-3.5 pointer-events-none"
                  />
                </button>
              ))}
            </div>
            
            <div className="text-[11px] text-gray-400 leading-relaxed bg-blue-50/50 p-3 rounded-lg border border-blue-50 mt-4">
              <strong>Tip:</strong> Keep categories enabled to see conflicts and plan your family's weekly schedule.
            </div>
          </div>

          {/* Agenda Grid Feed */}
          <div className="lg:col-span-3 bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-6">
            <h4 className="text-sm font-bold text-gray-900 flex items-center gap-2">
              <Layers size={16} className="text-blue-600" style={{ color: primaryColor }} />
              Upcoming District Schedule (2026)
            </h4>

            {filteredEvents.length === 0 ? (
              <div className="text-center py-16 text-gray-400 text-sm">
                No active events matching your filter. Choose at least one calendar above.
              </div>
            ) : (
              <div className="divide-y divide-gray-100">
                {filteredEvents.map((evt) => {
                  const categoryInfo = categories.find(c => c.key === evt.category);
                  const d = new Date(evt.date);
                  const month = d.toLocaleDateString('en-US', { month: 'short' });
                  const day = d.toLocaleDateString('en-US', { day: '2-digit' });

                  return (
                    <div key={evt.id} className="py-4 flex gap-4 first:pt-0 last:pb-0 group hover:bg-gray-50/40 px-2 rounded-lg transition-colors">
                      {/* Colored Date badge */}
                      <div className="flex flex-col items-center justify-center w-12 h-14 bg-gray-50 border border-gray-100 rounded-lg shrink-0 select-none">
                        <span className="text-[10px] font-bold text-gray-400 uppercase leading-none">{month}</span>
                        <span className="text-lg font-black text-gray-800 mt-0.5 leading-none">{day}</span>
                      </div>

                      {/* Event Details */}
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap mb-1">
                          <span className={`text-[9px] font-extrabold px-2 py-0.5 rounded-full text-white ${categoryInfo?.color || 'bg-gray-500'}`}>
                            {categoryInfo?.label}
                          </span>
                        </div>
                        <h5 className="text-sm font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                          {evt.title}
                        </h5>
                        
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-gray-400">
                          <span className="flex items-center gap-1">
                            <Clock size={12} /> {evt.time}
                          </span>
                          <span className="flex items-center gap-1">
                            <MapPin size={12} /> {evt.location}
                          </span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      ) : (
        /* PDF/Image Calendar Rendering matching [TASK 44] */
        <div className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <button
              onClick={() => setActivePdfDoc('academic')}
              className={`p-5 rounded-xl border text-left transition-all flex flex-col gap-2 shadow-xs cursor-pointer ${
                activePdfDoc === 'academic' 
                  ? 'bg-blue-50/30 border-blue-200 ring-2 ring-blue-500/10' 
                  : 'bg-white border-gray-100 hover:border-gray-200'
              }`}
            >
              <div className="flex justify-between items-start w-full">
                <h4 className="text-sm font-bold text-gray-800">2026-2027 Academic Year Calendar</h4>
                <Eye size={16} className={activePdfDoc === 'academic' ? 'text-blue-600' : 'text-gray-300'} style={{ color: activePdfDoc === 'academic' ? primaryColor : undefined }} />
              </div>
              <p className="text-xs text-gray-500">Official schedule detailing holidays, teacher institutes, and regular student attendance cycles.</p>
              <div className="flex gap-2 mt-2">
                <a
                  href={calendarPaths.academic.pdf || calendarPaths.academic.png}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-1 px-3 py-1 bg-white rounded border border-gray-200 text-[10px] font-bold text-gray-700 hover:bg-gray-50 transition-colors"
                  onClick={(e) => e.stopPropagation()}
                >
                  <Download size={10} /> Download File
                </a>
              </div>
            </button>

            <button
              onClick={() => setActivePdfDoc('monthly')}
              className={`p-5 rounded-xl border text-left transition-all flex flex-col gap-2 shadow-xs cursor-pointer ${
                activePdfDoc === 'monthly' 
                  ? 'bg-blue-50/30 border-blue-200 ring-2 ring-blue-500/10' 
                  : 'bg-white border-gray-100 hover:border-gray-200'
              }`}
            >
              <div className="flex justify-between items-start w-full">
                <h4 className="text-sm font-bold text-gray-800">Monthly Event Calendar</h4>
                <Eye size={16} className={activePdfDoc === 'monthly' ? 'text-blue-600' : 'text-gray-300'} style={{ color: activePdfDoc === 'monthly' ? primaryColor : undefined }} />
              </div>
              <p className="text-xs text-gray-500">Regularly updated monthly schedule listing physical games, parent days, and field activities.</p>
              <div className="flex gap-2 mt-2">
                <a
                  href={calendarPaths.monthly.pdf || calendarPaths.monthly.png}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-1 px-3 py-1 bg-white rounded border border-gray-200 text-[10px] font-bold text-gray-700 hover:bg-gray-50 transition-colors"
                  onClick={(e) => e.stopPropagation()}
                >
                  <Download size={10} /> Download File
                </a>
              </div>
            </button>
          </div>

          {/* Interactive full-width viewer mimicking smooth Apptegy style [TASK 44] */}
          <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm overflow-hidden" id="pdf-viewer">
            <div className="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
              <span className="text-xs font-extrabold text-gray-500 uppercase tracking-wider">
                Viewing: {activePdfDoc === 'academic' ? 'Academic Calendar' : 'Monthly Calendar'}
              </span>
              <span className="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-mono font-bold">
                {isImage ? "Image Viewer" : "PDF Viewer - FitH"}
              </span>
            </div>
            <div className="w-full h-[700px] bg-gray-50 rounded-lg overflow-hidden border border-gray-100 flex items-center justify-center">
              {currentPath ? (
                isImage ? (
                  <div className="w-full h-full p-2 flex items-center justify-center bg-white overflow-auto">
                    <img
                      src={currentPath}
                      alt="School Calendar View"
                      className="max-w-full max-h-full object-contain shadow-xs rounded"
                    />
                  </div>
                ) : (
                  <iframe
                    src={`${currentPath}?view=FitH`}
                    className="w-full h-full"
                    title="School Calendar Doc View"
                    style={{ border: 'none' }}
                  />
                )
              ) : (
                <div className="text-center p-8 text-gray-400 text-sm">
                  No document found for this selection.
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
