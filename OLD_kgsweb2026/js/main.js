/**
 * kgsweb2026/js/main.js
 * Master logic for Ticker, Weather, Calendar, and Mobile UI
 */
document.addEventListener('DOMContentLoaded', function() {

// --- GLOBAL CONFIG ---
    const API_ROOT = '/public/kgsweb2026/api';
	
// --- HELPERS ---
	function getDirectLink(url) {
		if (!url) return 'assets/img/staff-default.png';
		if (url.includes('drive.google.com')) {
			const match = url.match(/\/d\/(.+?)\/(view|edit|usp|$)/);
			const fileId = match ? match[1] : null;
			// FIXED: Replaced "1{fileId}" with the proper template literal `${fileId}`
			return fileId ? `https://lh3.googleusercontent.com/u/0/d/${fileId}` : url;
		}
		return url;
	}

	function getFileIcon(fileName, mimeType = '') {
		const ext = fileName.split('.').pop().toLowerCase();
        const mime = (mimeType || '').toLowerCase();
		if (ext === 'pdf' || mime.includes('pdf')) return 'fa-file-pdf text-danger';
		if (['doc', 'docx'].includes(ext) || mime.includes('word')) return 'fa-file-word text-primary';
		if (['xls', 'xlsx', 'csv'].includes(ext) || mime.includes('sheet')) return 'fa-file-excel text-success';
		if (['ppt', 'pptx'].includes(ext) || mime.includes('presentation')) return 'fa-file-powerpoint text-warning';
		if (['jpg', 'jpeg', 'png', 'gif'].includes(ext) || mime.includes('image')) return 'fa-file-image text-info';
		return 'fa-file text-secondary';
	}
	
	
// --- NAVIGATION MENU LOGIC ---

	document.querySelectorAll('.top-menu-li.align-right > a').forEach(link => {
		link.addEventListener('click', function(e) {
			// Only run this logic on mobile/tablet (850px and down)
			if (window.innerWidth <= 850) {
				const dropdown = this.nextElementSibling;
				
				// If this item has a dropdown...
				if (dropdown && dropdown.classList.contains('dropdown-content')) {
					e.preventDefault(); // Stop the link from navigating immediately
					
					// Toggle the "is-open" class
					dropdown.classList.toggle('is-open');
					
					// Optional: Close other open dropdowns if you click a new one
					document.querySelectorAll('.dropdown-content').forEach(other => {
						if (other !== dropdown) other.classList.remove('is-open');
					});
				}
			}
		});
	});

// --- TICKER ENGINE LOGIC ---
    const tickerTrack = document.getElementById('ticker-track');
    const tickerContainer = document.getElementById('school-ticker');
    const tickerLabel = document.getElementById('ticker-label');

    async function fetchTickerData() {
        if (!tickerTrack) return; 
        try {
            const response = await fetch(`${API_ROOT}/get-data.php?type=ticker`);
            const data = await response.json();
            if (data.ticker && !data.error) {
                const newText = `${data.ticker}  <i class="fa-solid fa-feather"></i><i class="fa-solid fa-k"></i><i class="fa-solid fa-g"></i><i class="fa-solid fa-s"></i> `;
                tickerTrack.innerHTML = '';
                const span1 = document.createElement('span'); span1.innerHTML = newText;
                const span2 = document.createElement('span'); span2.innerHTML = newText;
                tickerTrack.appendChild(span1); tickerTrack.appendChild(span2);

                const isEmergency = ['URGENT', 'CLOSING', 'CLOSED', 'CANCELED', 'EMERGENCY', 'DELAYED'].some(k => data.ticker.toUpperCase().includes(k));
                if (tickerContainer) {
                    tickerContainer.classList.toggle('emergency-mode', isEmergency);
                    if (tickerLabel) tickerLabel.innerText = isEmergency ? 'ALERT:' : 'LATEST:';
                }
            }
        } catch (e) { console.error("Ticker error:", e); }
    }

// --- MOBILE MENU LOGIC ---
    document.querySelectorAll('.top-menu-li').forEach(item => {
        const link = item.querySelector('a');
        const dropdown = item.querySelector('.dropdown-content');
        if (dropdown) {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isOpen = dropdown.classList.contains('is-open');
                    document.querySelectorAll('.dropdown-content').forEach(d => d.classList.remove('is-open'));
                    if (!isOpen) dropdown.classList.add('is-open');
                }
            });
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.top-menu-li')) {
            document.querySelectorAll('.dropdown-content').forEach(d => d.classList.remove('is-open'));
        }
    });

// --- CALENDAR ENGINE LOGIC ---
    function formatEventDisplay(event) {
		let start = event.isAllDay ? new Date(event.start + 'T00:00:00') : new Date(event.start);
		const end = event.end ? new Date(event.end) : null;
		const dateStr = start.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
		let timeStr = "All Day";
		if (!event.isAllDay) {
			const options = { hour: 'numeric', minute: '2-digit', hour12: true };
			const startTime = start.toLocaleTimeString('en-US', options);
			timeStr = (end && !isNaN(end.getTime())) ? `${startTime} – ${end.toLocaleTimeString('en-US', options)}` : startTime;
		}
		return { dateStr, timeStr };
	}
		
    async function fetchCalendarEvents() {
        const container = document.getElementById('upcoming-events-calendar-div');
        if (!container) return;
        try {
            const response = await fetch(`${API_ROOT}/get-data.php?type=events`);
            const events = await response.json();
            if (events && events.length > 0) {
                container.innerHTML = events.map(event => {
                    const { dateStr, timeStr } = formatEventDisplay(event);
                    const isToday = new Date(event.start).toDateString() === new Date().toDateString();
                    return `<div class="calendar-row ${isToday ? 'today-highlight' : ''}">
                                <div class="cal-col date">${dateStr}</div>
                                <div class="cal-col title">${event.summary}</div>
                                <div class="cal-col duration">${timeStr}</div>
                            </div>`;
                }).join('') + `<div class="calendar-footer"><a href="calendar.php" class="events-view-all-btn more-btn">View Full School Calendar</a></div>`;
            }
        } catch (e) { console.error("Calendar error:", e); }
    }
	
	
	
// --- UNIVERSAL CALENDAR SYNC --- 
		document.addEventListener('DOMContentLoaded', function() {
			const syncBtn = document.getElementById('sync-cal-btn');
			
			// If we aren't on the calendar page, stop here so we don't cause errors
			if (!syncBtn) return;

			// 1. Detect device
			const isApple = /iPhone|iPad|iPod/.test(navigator.userAgent);
			
			// 2. Grab the IDs that PHP baked into the HTML data attributes
			const calId = syncBtn.getAttribute('data-calid');
			const icalId = syncBtn.getAttribute('data-ical');

			// 3. Set the href based on the device
			if (isApple) {
				// iPhone/Mac "Subscribe" Protocol
				syncBtn.href = `webcal://calendar.google.com/calendar/ical/${icalId}/public/basic.ics`;
			} else {
				// Google/Android "Add" Protocol
				syncBtn.href = `https://calendar.google.com/calendar/render?cid=${calId}`;
			}
		});


// --- SCHOOL BOARD LOGIC ---
    async function initBoardPage() {
        const boardTable = document.getElementById('board-members-body');
        const meetingTable = document.getElementById('board-meetings-body');
        if (meetingTable) fetchBoardMeetings();
        if (boardTable) fetchBoardMembers();
    }

    async function fetchBoardMeetings() {
        const tbody = document.getElementById('board-meetings-body');
        try {
            const response = await fetch(`${API_ROOT}/get-data.php?type=calendar&source=board`);
            const events = await response.json();
            tbody.innerHTML = (events && events.length > 0) ? events.map(event => `<tr><td>${new Date(event.start).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</td></tr>`).join('') : '<tr><td>No upcoming board meetings found.</td></tr>';
        } catch (e) { tbody.innerHTML = '<tr><td>Error loading meeting dates.</td></tr>'; }
    }

    async function fetchBoardMembers() {
        const tbody = document.getElementById('board-members-body');
        try {
            const response = await fetch(`${API_ROOT}/get-data.php?type=board-members`);
            const data = await response.json();
            tbody.innerHTML = (data && data.length > 0) ? data.map(m => `<tr><td class="fw-bold text-dark board-member-name">${m.name}</td><td><span class="fw-bold text-dark board-member-position">${m.position}</span></td></tr>`).join('') : '<tr><td>No board members found.</td></tr>';
        } catch (e) { tbody.innerHTML = '<tr><td>Error loading members.</td></tr>'; }
    }
	

// --- STAFF DIRECTORY ---
async function fetchStaffDirectory() {
	const container = document.getElementById('staff-directory-grid');
	if (!container) return; 
	try {
		const response = await fetch(`${API_ROOT}/get-data.php?type=staff-directory`);
		const data = await response.json();
		if (data && data.length > 0) {
			container.innerHTML = data.map(s => `
				<div class="staff-profile-card horizontal">
					<div class="profile-img-container">
						<img src="${getDirectLink(s.photo)}" alt="${s.fname}" onerror="this.src='assets/img/staff-default.png'">
					</div>
					<div class="profile-info">
						<div class="profile-header">
							<h3>${s.fname} ${s.lname}</h3>
							<span class="position-badge">${s.position}</span>
						</div>
						<p class="other-duties">${s.duties || ''}</p>
						<div class="profile-contact">
							<a href="mailto:${s.email}" title="Email"><i class="fa-solid fa-envelope"></i></a>
							${s.phone ? `<a href="tel:${s.phone}" title="Call"><i class="fa-solid fa-phone"></i></a>` : ''}
							${s.webpage ? `<a href="${s.webpage}" target="_blank" title="Website"><i class="fa-solid fa-globe"></i></a>` : ''}
						</div>
					</div>
				</div>`).join('');
	}
} catch (e) { console.error("Staff Error:", e); }
}				
	
// --- DOCUMENTS (DRIVE TREE) ---
	async function fetchFullDocsTree() {
		const rootUl = document.getElementById('drive-root');
		if (!rootUl) return;
		try {
			const response = await fetch(`${API_ROOT}/get-data.php?type=tree`);
			const fullData = await response.json();
			const buildHtml = (items) => items.map(item => {
				if (item.type === 'folder') {
					return `<li class="folder-node">
						<div class="folder-header" onclick="toggleFolder(this)">
							<i class="fa-solid fa-folder text-warning me-2 folder-icon"></i>
							<span class="folder-link">${item.name}</span>
						</div>
						<ul class="nested-docs">${buildHtml(item.children || [])}</ul>
					</li>`;
				}
				const iconClass = getFileIcon(item.name, item.mimeType || '');
				return `<li class="file-node"><i class="fa-regular ${iconClass} me-2"></i><a href="${item.link}" target="_blank">${item.name}</a></li>`;
			}).join('');
			rootUl.innerHTML = buildHtml(fullData);
		} catch (e) { console.error("Tree error:", e); }
	}
	
	window.toggleFolder = function(element) {
		const parent = element.parentElement;
		const icon = element.querySelector('.folder-icon');
		parent.classList.toggle('is-open');
		if (parent.classList.contains('is-open')) {
			icon.classList.replace('fa-folder', 'fa-folder-open');
		} else {
			icon.classList.replace('fa-folder-open', 'fa-folder');
		}
	};

// --- LIGHTBOX LOGIC ---
    const lightbox = document.getElementById('menu-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const captionText = document.getElementById('lightbox-caption');
    const closeBtn = document.querySelector('.lightbox-close');

    if (lightbox) {
        document.body.addEventListener('click', function(e) {
            if (e.target.classList.contains('zoomable-menu')) {
                lightbox.style.display = "flex"; 
                lightboxImg.src = e.target.src;
                captionText.innerHTML = e.target.alt;
                document.body.style.overflow = "hidden";
            }
        });
        lightbox.onclick = function(event) {
            if (event.target === lightbox || event.target === closeBtn) {
                lightbox.style.display = "none";
                document.body.style.overflow = "auto";
            }
        };
    }

// --- BACK TO TOP ---
    const btt = document.getElementById("backToTop");
    if (btt) {
        window.addEventListener('scroll', () => { btt.style.display = (window.scrollY > 300) ? "flex" : "none"; });
        btt.addEventListener('click', (e) => { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
    }

// --- CACHE CLEAR --- 
    document.addEventListener('click', function(e) {
        if (e.target.id === 'cache-buster-link') {
            e.preventDefault();
            const toast = document.getElementById('cache-buster-toast');
            fetch(`${API_ROOT}/clear-cache.php?t=` + Date.now())
                .then(res => res.json())
                .then(data => {
                    if (toast) {
                        toast.innerText = "Deleted: " + data.count; 
                        toast.classList.add('show');
                        if(data.count > 0) setTimeout(() => window.location.reload(), 1500);
                    }
                });
        }
    });

// --- INITIALIZE ALL ---
    fetchTickerData();
    setInterval(fetchTickerData, 300000); 
	fetchFullDocsTree();
    fetchCalendarEvents();
    initBoardPage();
	fetchStaffDirectory();

});