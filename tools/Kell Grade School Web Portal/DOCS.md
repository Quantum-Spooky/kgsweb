# Kell Grade School CMS ("The Kell Engine") - Technical Implementation Documentation

Welcome to the comprehensive technical documentation for the rebooted Kell Grade School Web CMS (The Kell Engine v3.1), built in React, TypeScript, Vite, and Express. 

This document outlines the precise system architecture, file dependencies, routing, dynamic server mappings, and step-by-step instructions to download the complete codebase.

---

## 📂 Codebase Export Instructions

Since you are running in Google AI Studio, you have access to a built-in export system that packages your exact code, directories, server routers, assets, and caching layers with zero overhead.

### How to Download Your Code as a ZIP File:
1. Locate the **Settings Menu** (represented by a gear icon) in the top-right corner of the Google AI Studio interface.
2. Click **Export to ZIP** or **Export to GitHub**.
3. Save the package locally. It will contain all custom components, Express backend integrations, CSS stylings, and localized static assets.

---

## 🛠️ Feature Architectures & Technical Blueprints

### 1. Live Feed Component
The Live Feed replicates the responsive feel of Apptegy's active school announcements, powered by a dynamic file/sheet parser.
- **Data Source**: Synchronized directly with `feed_18MuCzAUmPSY8mB2s2NENGgQO39xrkl7uFP5pV2OItrw.json` and loaded via `/api/feed` from the cache.
- **Server Endpoint**: `/api/feed` resolves announcements in chronological order (newest first).
- **Client Render**: Implements smooth transition blocks with custom category labels (e.g., Announcements, Deadlines, Reminders) and fully styled media cards for post attachments.
- **Manual Sync Trigger**: Triggers a full backend reload using a custom key listener on the footer copyright character (see [TASK 11]).

### 2. Admin Settings & Manual Sync Portal
An integrated dashboard is available for authorized stakeholders to manage caching cycles, view logs, and trigger data synchronization from Google Sheets/Drive.
- **Access Route**: Pressing **Portal** in the bottom right corner opens the credentialed login slide.
- **Cache Invalidation**: Triggers `/api/refresh-cache` on the server which invalidates current page views, restarts local disk-state caching, and updates index headers.
- **Auto-Sync Bypass**: Includes a cache buster parameter (`?v=' + Date.now()`) to bypass aggressive mobile Safari and chrome offline snapshots.

### 3. Dynamic Local Weather Integration
Provides real-time rural community forecasting modeled after local Illinois agricultural portals.
- **Configurable Location**: Sourced from the control panel (`weather_location: 62853`).
- **Server Endpoint**: `/api/weather` handles external API requests to NOAA (`weather.gov`) server-side to hide private endpoints.
- **Responsive Layout**: Designed as a collapsible horizontal card inside the Smart Grid. Remembers the user's preference and defaults to the configuration state specified in the site-settings.

### 4. Hybrid Google & PDF Calendar View
Implements an interactive dual-view interface solving [TASK 44] and [TASK 63].
- **Live Embed View**: Renders the school's public Google Calendar utilizing a customized `ctz=America%2FChicago` responsive iframe.
- **Agenda View**: Formats active district events into modern high-contrast date tiles, sorted recursively.
- **PDF Fit View**: Employs Google Doc/PDF viewer integrations with native height scaling (`700px`) and `?view=FitH` query variables to eliminate viewport scrolling issues.

### 5. Athletics Pages
Templates configured for junior high sports (Baseball, Basketball, Bowling, Cheer, Cross Country, Volleyball).
- **Subpage Automation**: Dynamic template router parses page layouts based on `/api/navigation` hierarchies.
- **Media Hub**: Sourced from individual in-house folders on Google Drive. If a specific athletic folder is populated with PDFs (e.g. schedules, rosters), the page displays them inside a clean file listing. If the folder is empty, the template hides the section dynamically.

### 6. Clubs Pages
Extensible components for student-led extracurricular activities (Student Council, Scholar Bowl, Yearbook, Book Club).
- **Sponsor Directories**: Uses relational matching to load designated sponsors and teachers (e.g., matching Sponsor role strings directly into staff listings).
- **Shared Documents**: Auto-mounts registration forms and club handbooks directly from the Drive index.

### 7. Self-Hydrating Contact Page
Builds out a clean, fully accessible rural contact screen solving [TASK 54].
- **Dynamic Bindings**: Pulls physical address, office phone, office fax, and primary administrative email dynamically from the Site Settings config mapping.
- **Responsive Form**: Built using controlled Tailwind inputs with robust success states, ready to be wired up to SMTP or external mailers.

### 8. ROE Website Compliance Audit Section
Specifically modeled after Illinois Regional Office of Education audit cycles [TASK 11].
- **Structured Table**: Evaluates 19 mandatory school-district public fields (Annual Budgets, Collective Bargaining Agreements, Special Education references, SOPPA policies).
- **Token Resolution Seeker**: If the value contains a folder token (e.g. `@board_agendas_folder_id`), it recursively searches the Google Drive tree to resolve and display the newest official PDF directly, ensuring administrators don't have to update URLs manually.

---

## 📐 The Kell Engine Architecture Core Helpers

### Proportional Grid Distribution Formula:
```typescript
Remaining_Columns = 12 - Explicitly_Assigned_Manual_Widths
Auto_Column_Width = Math.floor(Remaining_Columns / Unassigned_Active_Widgets)
// Min-width is bound at 6 columns on medium viewports to preserve clean legibility.
```

### Date Sieve Sort Matcher:
```typescript
// YYYY-MM-DD
const dateRegex = /(\d{4})[-_ ](\d{1,2})[-_ ](\d{1,2})/;
```

---

*Designed and engineered in compliance with Regional Office of Education website criteria. Kell Consolidated School District #2, 2026.*
