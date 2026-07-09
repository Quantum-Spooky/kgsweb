Notes from Copilot 06/01/2026

# Kell Grade School Website - Comprehensive Project Analysis

## 1\. Repository Overview

**Repository:** Quantum-Spooky/kgsweb  
**Language:** PHP  
**Description:** A lightweight, school-focused CMS for Kell Grade School District  
**Created:** September 2025  
**Last Updated:** June 1, 2026  
**Status:** Active Development

### Purpose

The **kgsweb** project is a Google Drive-backed PHP website platform designed for rural school districts. It serves as a hybrid content management system that enables non-technical staff (administrators, teachers, secretaries) to manage school website content through familiar tools (Google Docs, Google Sheets) without the complexity and maintenance burden of a traditional database-driven CMS.

### Core Philosophy

- **Content-first**: All content lives in kgs-content/pages/ filesystem JSON files (source of truth)
- **Stateless rendering**: Pages are rendered fresh each request with optional caching
- **Zero-database**: No SQL database required; JSON files + optional Google Drive sync
- **Volunteer-friendly**: Designed for staff with limited technical background
- **Cache-optional**: System works even if cache layer is deleted or stale

### Key Technology Stack

- **Backend:** PHP 8.0+
- **Content Storage:** JSON (filesystem-based)
- **External Integration:** Google Drive, Google Sheets, Google Calendar
- **Frontend Framework:** Bootstrap 5.3.3
- **Frontend Assets:** CSS, JavaScript (minimal)
- **Deployment:** Apache with mod_rewrite

## 2\. Folder Structure

Code

kgsweb/

│

├── /app/ \[APPLICATION LAYER - Views & Components\]

│ ├── /components/ \[Reusable UI Components\]

│ │ ├── button-group.php - Button group renderers

│ │ ├── calendar-embed.php - Google Calendar widget

│ │ ├── facebook-feed.php - Facebook feed embed

│ │ ├── file-list.php - Document/file listings

│ │ ├── hero.php - Hero banner section

│ │ ├── live-feed.php - News/announcements ticker

│ │ ├── page-header.php - Page title/header

│ │ ├── photo-gallery.php - Image gallery

│ │ ├── quick-links.php - Quick navigation buttons

│ │ ├── renderer.php - Component rendering engine

│ │ ├── rich-text.php - Rich HTML content

│ │ ├── school-highlights.php - Pride/highlights section

│ │ ├── sidebar.php - Sidebar widget

│ │ ├── staff-directory.php - Staff listing

│ │ ├── widgets-section.php - Multi-widget container

│ │ └── contact-info.php - Contact information block

│ │

│ └── /layouts/ \[Layout Templates\]

│ ├── header.php - Global site header (navigation)

│ ├── footer.php - Global site footer

│ ├── navigation.php - Navigation bar

│ ├── default.php - Default page layout

│ ├── home.php - Home page layout

│ │

│ └── /page/ \[Page-specific layout wrappers\]

│ ├── default-start.php - Standard page start wrapper

│ ├── default-end.php - Standard page end wrapper

│ ├── home-start.php - Home page wrapper

│ ├── home-end.php

│ ├── athletics-start.php - Athletics section wrapper

│ ├── athletics-end.php

│ └── full-width-start/end.php - Full-width layout option

│

├── /cfg/ \[CONFIGURATION LAYER\]

│ ├── config.php - Main site configuration

│ ├── cache.php - Cache directory paths

│ ├── google.php - Google API configuration

│ ├── GoogleConfig.php - Google config class

│ └── google-service-account.json - Google OAuth credentials

│

├── /kgs-core/ \[CORE FRAMEWORK - Application Logic\]

│ ├── bootstrap.php - Application initialization

│ ├── autoload.php - PSR-4 autoloader

│ ├── Router.php - URL routing & orchestration

│ ├── CacheManager.php - Cache management (placeholder)

│ ├── EventBus.php - Event system (optional)

│ ├── ModuleManager.php - Module loading

│ │

│ ├── /bootstrap/ \[Dependency Injection & Setup\]

│ │ ├── ServiceContainer.php - Global service registry

│ │ └── RuntimeRules.php - Architecture enforcement

│ │

│ ├── /cms/ \[CONTENT MANAGEMENT SYSTEM\]

│ │ ├── ContentCMS.php - Source of truth content loader

│ │ ├── ContentCMSService.php - CMS bridge/orchestrator

│ │ ├── CMSCache.php - Application cache layer

│ │ ├── ComponentRenderer.php - Component rendering logic

│ │ ├── ComponentSchema.php - Component validation schema

│ │ └── ComponentValidator.php - Data validation

│ │

│ ├── /config/ \[Configuration Management\]

│ │ ├── ConfigRepository.php - Config getter/setter

│ │ ├── google.php - Google service config

│ │ └── modules.php - Module configuration

│ │

│ ├── /google/ \[Google API Integration\]

│ │ ├── GoogleDriveCache.php - Low-level cache engine

│ │ ├── GoogleDriveClient.php - Google Drive API wrapper

│ │ ├── GoogleDrive.php - Google Drive operations

│ │ ├── GoogleDriveSync.php - Content sync logic

│ │ ├── GoogleCalendar.php - Calendar integration

│ │ ├── GoogleSheets.php - Sheets API wrapper

│ │ ├── GoogleSlides.php - Slides API wrapper

│ │ ├── GoogleService.php - Service factory

│ │ ├── GoogleConfig.php - Google config reader

│ │ └── GoogleHelpers.php - Utility functions

│ │

│ ├── /services/ \[Business Logic Services\]

│ │ ├── ContentCMSService.php - CMS orchestration

│ │ └── RouteAliasService.php - URL alias management

│ │

│ ├── /settings/ \[Settings Management\]

│ │ └── Settings.php - Settings registry

│ │

│ └── /workers/ \[Background Jobs\]

│ └── drive-sync.php - Content sync worker

│

├── /kgs-cache/ \[CACHE STORAGE - Generated\]

│ └── /google/ \[Google integrations cache\]

│ ├── /cms_pages_cache/ - Cached page data

│ ├── /documents/ - Document cache

│ ├── /drive/ - Drive tree cache

│ ├── /events/ - Calendar events cache

│ ├── /menus/ - Menu data cache

│ ├── /sheets/ - Sheet data cache

│ ├── /slides/ - Slides cache

│ └── /ticker/ - News ticker cache

│

├── /kgs-content/ \[SOURCE OF TRUTH - Content Storage\]

│ └── /pages/ \[Page Hierarchy\]

│ ├── /home/

│ │ ├── meta.json - Page metadata

│ │ └── components.json - Component array

│ ├── /about/

│ │ ├── meta.json

│ │ ├── components.json

│ │ ├── /documents/

│ │ ├── /policies/

│ │ ├── /school-board/

│ │ ├── /employment/

│ │ └── /staff-directory/

│ ├── /academics/

│ ├── /activities/

│ │ ├── /athletics/

│ │ │ ├── /baseball/

│ │ │ ├── /basketball/

│ │ │ ├── /bowling/

│ │ │ ├── /cheerleading/

│ │ │ ├── /cross-country/

│ │ │ └── /volleyball/

│ │ └── /clubs/

│ │ ├── /book-club/

│ │ ├── /brain-games/

│ │ ├── /cooking-club/

│ │ ├── /scholar-bowl/

│ │ ├── /student-council/

│ │ └── /yearbook/

│ ├── /calendar/

│ ├── /contact/

│ ├── /dining/

│ ├── /family/

│ │ └── /pto/

│ ├── /news/

│ │ └── /announcements/

│ └── ... \[other pages\]

│

├── /public/ \[WEB ROOT - Public Entry Point\]

│ ├── index.php - Main router entry point

│ ├── test-drive.php - Development/testing endpoint

│ ├── .htaccess - Apache URL rewrite rules

│ │

│ └── /assets/ \[Static Assets\]

│ ├── /css/ - Stylesheets

│ │ ├── base.css

│ │ ├── cards.css

│ │ ├── footer.css

│ │ ├── hero.css

│ │ ├── layout.css

│ │ ├── navigation.css

│ │ ├── page-header.css

│ │ └── style.css

│ ├── /img/ - Images

│ └── /js/ - JavaScript files

│

├── /tools/ \[Development & Admin Tools\]

│ ├── /admin/

│ │ ├── dashboard.php - Admin control panel

│ │ └── page-editor.php - Page content editor

│ │

│ └── /scripts/

│ ├── migrate_drive_to_cmscache.php - Data migration tool

│ ├── migrate_home_to_cmscache.php

│ └── architecture-test.php - Architecture validator

│

├── README.md - Project documentation

├── tree.txt - Directory listing

├── CMS Architecture - current state as of 06-01-2026.txt - Architecture spec

├── .htaccess - Global URL rewrite config

└── composer.json / vendor/ - PHP dependencies

## 3\. Core Modules & Components

### 3.1 Content Management System (CMS)

#### **ContentCMS** (kgs-core/cms/ContentCMS.php)

**Responsibility:** Source of truth content loader

Code

Input: route (e.g., "home", "about/staff-directory")

↓

Process: Load from kgs-content/pages/{route}/

\- meta.json (page metadata)

\- components.json (component array)

↓

Output:

{

'meta' => {

'title': string,

'description': string,

'layout': string

},

'components' => \[

{

'type': string, // component name

'data': array // component props

},

...

\]

}

**Key Rules:**

- ✅ **MUST** read from kgs-content/pages/ only
- ❌ **MUST NOT** write to cache
- ❌ **MUST NOT** know about routing logic
- ❌ **MUST NOT** access Google Drive services

#### **ContentCMSService** (kgs-core/services/ContentCMSService.php)

**Responsibility:** Bridge between Router and ContentCMS

**Flow:**

Code

Router

↓

ContentCMSService.load(route)

↓

\[Try CMSCache.get() first\]

↓

\[Cache miss? Load from ContentCMS\]

↓

\[Store in CMSCache\]

↓

Return normalized page array → Router

**Responsibilities:**

- Decide between cache and source
- Normalize CMS output
- Abstract caching from Router

#### **CMSCache** (kgs-core/cms/CMSCache.php)

**Responsibility:** Application-level page cache

**Features:**

- Wraps GoogleDriveCache for transparent persistence
- Handles TTL (time-to-live) invalidation
- Stores complete meta + components arrays
- Can be deleted/cleared without breaking the app

**Storage Location:** kgs-cache/google/cms_pages_cache/

### 3.2 Cache Infrastructure

#### **GoogleDriveCache** (kgs-core/google/GoogleDriveCache.php)

**Responsibility:** Generic JSON file cache engine

**Design:** Infrastructure-level, knows nothing about CMS or pages

**Features:**

- get(group, key, ttl) - Retrieve with TTL check
- set(group, key, data, meta) - Store with metadata
- delete/clearGroup/clearAll - Cleanup operations
- Schema versioning for migrations
- Atomic writes (tmp + rename pattern)
- File-based storage in kgs-cache/google/{group}/

**Supported Groups:**

- cms_pages_cache - CMS pages
- documents - Document trees
- menus - Menu data
- ticker - News ticker
- events - Calendar events
- sheets - Google Sheets data
- slides - Google Slides cache

### 3.3 Routing & Orchestration

#### **Router** (kgs-core/Router.php)

**Responsibility:** URL routing and rendering orchestration

**Flow:**

Code

1\. Normalize route (remove special chars, trailing slashes)

2\. Apply route aliases (if configured)

3\. Default to 'home' if empty

4\. Call ContentCMSService.load(route)

5\. Extract meta and components

6\. Select layout template

7\. Render: header → layout-start → components → layout-end → footer

**Key Methods:**

- dispatch(route) - Main entry point
- alias(from, to) - Register URL alias
- loadAliasesFromSheet(rows) - Load aliases from Google Sheets

**Security:**

- Strips ../, \\ from routes (directory traversal protection)
- Sanitizes component types before file inclusion

### 3.4 Component Rendering

#### **renderer.php** (app/components/renderer.php)

**Responsibility:** Map component type → PHP file and render

**Process:**

Code

Input: {type: "hero", data: {title: "...", image: "..."}}

↓

1\. Normalize and sanitize type name

2\. Load ComponentSchema for validation

3\. Merge defaults from schema

4\. Extract data into PHP variable scope

5\. Include app/components/{type}.php

6\. Output HTML

**Safety Features:**

- Blocks path traversal in component names
- Schema-based default values
- Try-catch error handling

**Component Examples:**

- hero.php - Large banner section
- widgets-section.php - Multi-widget container
- staff-directory.php - Searchable staff table
- file-list.php - Document tree listing
- calendar-embed.php - Embedded Google Calendar
- facebook-feed.php - Facebook page feed

### 3.5 Google Integration

#### **GoogleDriveClient** (kgs-core/google/GoogleDriveClient.php)

**Responsibility:** Google API OAuth client wrapper

**Capabilities:**

- OAuth 2.0 authentication
- Drive file/folder queries
- Sheets row fetching
- Service account support

#### **GoogleDrive** (kgs-core/google/GoogleDrive.php)

**Responsibility:** Google Drive operations

**Capabilities:**

- Fetch folder structure
- List files by ID
- Support for shared drives

#### **GoogleSheets** (kgs-core/google/GoogleSheets.php)

**Responsibility:** Google Sheets operations

**Capabilities:**

- Fetch sheet rows as structured data
- Support multiple sheet tabs
- Cache friendly row fetching

#### **GoogleCalendar** (kgs-core/google/GoogleCalendar.php)

**Responsibility:** Calendar event fetching

**Capabilities:**

- Fetch upcoming events
- Optional recurring event expansion
- Event metadata extraction

## 4\. Key Files & Their Purposes

| **File**                                | **Purpose**                                            | **Type**       |
| --------------------------------------- | ------------------------------------------------------ | -------------- |
| public/index.php                        | Web entry point, route dispatcher                      | Router entry   |
| kgs-core/bootstrap.php                  | Application initialization, dependency setup           | Bootstrap      |
| kgs-core/Router.php                     | URL routing, layout selection, component orchestration | Core           |
| kgs-core/cms/ContentCMS.php             | Source of truth content loader                         | CMS Core       |
| kgs-core/services/ContentCMSService.php | CMS + Cache bridge                                     | Service        |
| kgs-core/cms/CMSCache.php               | Application page cache                                 | Cache          |
| kgs-core/google/GoogleDriveCache.php    | Generic file cache engine                              | Infrastructure |
| kgs-core/bootstrap/ServiceContainer.php | Global service registry                                | DI             |
| app/components/renderer.php             | Component type → PHP mapping                           | Renderer       |
| app/layouts/header.php                  | Global site header + nav                               | Layout         |
| app/layouts/footer.php                  | Global site footer                                     | Layout         |
| cfg/config.php                          | Site configuration constants                           | Config         |
| kgs-content/pages/                      | Page source of truth (JSON)                            | Data           |
| kgs-cache/google/                       | Generated cache storage                                | Cache          |

## 5\. Dependencies & Reference Chains

### 5.1 Request Flow (Complete Chain)

Code

Browser Request (HTTP GET)

↓

public/index.php

↓

\[Parse URL into route\]

↓

Router::dispatch(\$route)

↓

\[Normalize route + apply aliases\]

↓

ServiceContainer::get('cms')

↓

ContentCMSService::load(\$route)

↓

\[Check CMSCache first\]

↓

ContentCMS::loadPage(\$route)

↓

\[Read kgs-content/pages/{route}/meta.json & components.json\]

↓

\[Store in CMSCache via GoogleDriveCache\]

↓

\[Return normalized page array\]

↓

Router \[select layout based on meta\['layout'\]\]

↓

include app/layouts/header.php

include app/layouts/page/{layout}-start.php

↓

\[For each component:\]

render_component(type, data)

↓

include app/components/{type}.php

↓

Output HTML

↓

include app/layouts/page/{layout}-end.php

include app/layouts/footer.php

↓

HTTP Response (complete HTML)

### 5.2 Internal Import Dependencies

Code

bootstrap.php

├─→ cfg/config.php

├─→ kgs-core/autoload.php

├─→ kgs-core/google/GoogleDriveClient.php

├─→ kgs-core/bootstrap/ServiceContainer.php

├─→ kgs-core/services/ContentCMSService.php

├─→ kgs-core/Router.php

└─→ kgs-core/services/RouteAliasService.php

└─→ kgs-core/google/GoogleSheets.php

ContentCMSService

└─→ kgs-core/cms/ContentCMS.php

ContentCMS

└─→ kgs-content/pages/{route}/meta.json

└─→ kgs-content/pages/{route}/components.json

Router

├─→ ServiceContainer::get('cms')

├─→ app/layouts/header.php

├─→ app/layouts/page/{layout}-start.php

├─→ app/components/renderer.php

├─→ app/layouts/page/{layout}-end.php

└─→ app/layouts/footer.php

render_component()

├─→ ComponentSchema::getSchema(\$type)

└─→ app/components/{type}.php

GoogleDriveCache

└─→ kgs-cache/google/{group}/{key}.json

### 5.3 External Dependencies

Code

Composer (vendor/autoload.php)

├─→ Google API Client Library

│ ├─→ OAuth 2.0 support

│ ├─→ Google Drive API

│ ├─→ Google Sheets API

│ └─→ Google Calendar API

│

Bootstrap 5.3.3 (CDN)

├─→ CSS framework

└─→ JS components

Font & Icon Libraries (external CDN)

## 6\. High-Level Architecture

### 6.1 Overall System Architecture

Code

┌────────────────────────────────────────────────────────────────┐

│ WEB BROWSER REQUEST │

└────────────────────────────────────────────────────────────────┘

│

▼

┌────────────────────────────────────────────────────────────────┐

│ PUBLIC LAYER (public/index.php) │

│ - Parse URL/query string into route │

│ - Load configuration & bootstrap │

└────────────────────────────────────────────────────────────────┘

│

▼

┌────────────────────────────────────────────────────────────────┐

│ ROUTING LAYER (Router.php) │

│ - Normalize route (remove special chars) │

│ - Resolve route aliases (if configured) │

│ - Default to 'home' if route empty │

│ - Delegate to CMS service │

└────────────────────────────────────────────────────────────────┘

│

▼

┌────────────────────────────────────────────────────────────────┐

│ CMS ORCHESTRATION LAYER (ContentCMSService) │

│ - Check application cache first (CMSCache) │

│ - Fallback to content source (ContentCMS) │

│ - Store result in cache │

│ - Return: {meta: {...}, components: \[...\]} │

└────────────────────────────────────────────────────────────────┘

│ │

│ cache hit │ cache miss

▼ ▼

┌──────────────┐ ┌──────────────────────┐

│ CMSCache │ │ ContentCMS (Truth) │

│ (wrapper) │ │ kgs-content/pages/ │

└──────────────┘ └──────────────────────┘

│ │

└────────────────┬───────────────────┘

│

▼

Normalized Page Array Returned

│

▼

┌────────────────────────────────────────────────────────────────┐

│ RENDERING LAYER (Router continues) │

│ - Select layout template from meta\['layout'\] │

│ - Include app/layouts/header.php │

│ - Include app/layouts/page/{layout}-start.php │

│ - For each component: render_component(type, data) │

│ - Include app/layouts/page/{layout}-end.php │

│ - Include app/layouts/footer.php │

└────────────────────────────────────────────────────────────────┘

│

▼

┌────────────────────────────────────────────────────────────────┐

│ COMPONENT RENDERING (renderer.php) │

│ - Map component type → PHP file │

│ - Load component schema (validation) │

│ - Merge default values │

│ - Extract data into scope │

│ - Include app/components/{type}.php │

│ - Catch and display errors │

└────────────────────────────────────────────────────────────────┘

│

▼

┌────────────────────────────────────────────────────────────────┐

│ VIEW LAYER (Component files) │

│ - Render HTML using \$data scope │

│ - Pure presentation logic only │

│ - No CMS/cache/routing access │

└────────────────────────────────────────────────────────────────┘

│

▼

┌────────────────────────────────────────────────────────────────┐

│ HTTP RESPONSE (Complete HTML) │

└────────────────────────────────────────────────────────────────┘

### 6.2 Data Flow Diagram

Code

DATA SOURCES

│

├─→ kgs-content/pages/ (PRIMARY - JSON files)

│ ├─→ {route}/meta.json

│ └─→ {route}/components.json

│

└─→ Google Drive (OPTIONAL - sync source)

├─→ Google Sheets (config, menus, staff)

├─→ Google Calendar (events)

└─→ Google Docs (content pages)

↓ ContentCMS (loads)

│

↓

CMSCache Layer (app-level)

│

↓

GoogleDriveCache (filesystem)

│

↓

kgs-cache/google/cms_pages_cache/ (JSON files)

↓ On request:

│

↓

ContentCMSService (orchestrates)

│

├─→ Try: CMSCache.get(route) → GoogleDriveCache.get()

│ └─→ Success: Return cached page

│

└─→ Miss: ContentCMS.loadPage(route)

├─→ Read kgs-content/pages/{route}/meta.json

├─→ Read kgs-content/pages/{route}/components.json

├─→ Store in CMSCache → GoogleDriveCache

└─→ Return normalized page

↓ Page Array → Router

│

├─→ Layout Selection (meta\['layout'\])

│

├─→ Header Render

│

├─→ Components Iteration

│ └─→ For each component:

│ └─→ render_component(type, data)

│ ├─→ Load schema

│ ├─→ Merge defaults

│ └─→ Include & render component file

│

└─→ Footer Render

│

↓

Complete HTML Response

### 6.3 Layered Architecture

Code

┌─────────────────────────────────────────────────────────────┐

│ LAYER 1: PUBLIC ENTRY (public/) │

│ - Web-accessible entry point │

│ - Bootstrap application │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 2: ROUTING & ORCHESTRATION (Router.php) │

│ - URL parsing and normalization │

│ - Layout selection │

│ - Rendering pipeline │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 3: CMS SERVICE (ContentCMSService.php) │

│ - Cache decision logic │

│ - Content source selection │

│ - Normalization │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 4: CMS CORE (ContentCMS.php) │

│ - Source of truth (kgs-content/pages/) │

│ - File reading & parsing │

│ - Metadata extraction │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 5: APPLICATION CACHE (CMSCache.php) │

│ - Page-level caching │

│ - TTL management │

│ - GoogleDriveCache wrapper │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 6: INFRASTRUCTURE (GoogleDriveCache.php) │

│ - Generic JSON file cache │

│ - Atomic writes │

│ - Schema versioning │

│ - Multi-group storage │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 7: PERSISTENT STORAGE │

│ - kgs-cache/google/ (filesystem cache) │

│ - kgs-content/pages/ (source JSON) │

│ - Google Drive (optional sync source) │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 8: RENDERING (renderer.php) │

│ - Component type mapping │

│ - Schema validation │

│ - Component file inclusion │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 9: VIEW (app/components/) │

│ - Pure presentation logic │

│ - HTML generation │

│ - Asset rendering │

└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐

│ LAYER 10: HTTP RESPONSE │

│ - Complete HTML document │

└─────────────────────────────────────────────────────────────┘

### 6.4 Design Patterns Used

| **Pattern**                 | **Location**                   | **Purpose**                                     |
| --------------------------- | ------------------------------ | ----------------------------------------------- |
| **Service Locator**         | ServiceContainer               | Global access to CMS, Router, Google client     |
| **Facade**                  | ContentCMSService              | Abstracts cache + content layer                 |
| **Strategy**                | Router, layout selection       | Different rendering paths for different layouts |
| **Component Pattern**       | app/components/ + renderer.php | Composable UI building blocks                   |
| **Repository**              | ContentCMS                     | Abstract content source                         |
| **Factory**                 | GoogleService (optional)       | Creates Google API clients                      |
| **Template Method**         | Router::dispatch()             | Defines rendering pipeline steps                |
| **Chain of Responsibility** | Autoloader, file inclusion     | Tries multiple locations                        |

## 7\. Visual Architecture Diagrams

### 7.1 High-Level System Diagram

Mermaid

graph TB

subgraph "Browser & HTTP"

REQ\["HTTP Request"\]

RESP\["HTTP Response"\]

end

subgraph "Public Entry"

INDEX\["public/index.php"\]

end

subgraph "Routing Layer"

ROUTER\["Router.php"\]

end

subgraph "CMS Services"

CMS_SVC\["ContentCMSService"\]

end

subgraph "Content Sources"

TRUTH\["ContentCMS&lt;br/&gt;(Source of Truth)"\]

TRUTH_STORAGE\["kgs-content/pages/"\]

end

subgraph "Cache Layers"

APP_CACHE\["CMSCache&lt;br/&gt;(App Level)"\]

FILE_CACHE\["GoogleDriveCache&lt;br/&gt;(Infrastructure)"\]

FILE_STORAGE\["kgs-cache/google/"\]

end

subgraph "Google Integration"

GOOGLE\["Google APIs&lt;br/&gt;(Drive, Sheets, Calendar)"\]

end

subgraph "Rendering"

RENDERER\["render_component()"\]

COMPONENTS\["app/components/"\]

end

subgraph "Layouts"

LAYOUTS\["app/layouts/"\]

end

REQ --> INDEX

INDEX --> ROUTER

ROUTER --> CMS_SVC

CMS_SVC -->|cache check| APP_CACHE

CMS_SVC -->|miss| TRUTH

APP_CACHE --> FILE_CACHE

FILE_CACHE --> FILE_STORAGE

TRUTH --> TRUTH_STORAGE

TRUTH -->|optional sync| GOOGLE

ROUTER --> LAYOUTS

ROUTER --> RENDERER

RENDERER --> COMPONENTS

COMPONENTS --> RESP

style TRUTH fill:#ff9999

style TRUTH_STORAGE fill:#ffcccc

style GOOGLE fill:#99ccff

### 7.2 Component Dependency Diagram

Mermaid

graph TB

subgraph "Bootstrap Phase"

B\["bootstrap.php"\]

CONFIG\["cfg/config.php"\]

AUTOLOAD\["autoload.php"\]

SC\["ServiceContainer"\]

end

subgraph "Request Dispatch"

PUBLIC\["public/index.php"\]

ROUTER\["Router"\]

end

subgraph "CMS Layer"

CMS_SVC\["ContentCMSService"\]

CMS\["ContentCMS"\]

CMS_CACHE\["CMSCache"\]

end

subgraph "Cache Infrastructure"

CACHE\["GoogleDriveCache"\]

end

subgraph "Google Services"

GC\["GoogleDriveClient"\]

GD\["GoogleDrive"\]

GS\["GoogleSheets"\]

end

subgraph "Rendering"

REND\["renderer.php"\]

SCHEMA\["ComponentSchema"\]

COMPONENTS\["Components"\]

end

B --> CONFIG

B --> AUTOLOAD

B --> SC

B --> CMS_SVC

B --> ROUTER

PUBLIC --> ROUTER

SC --> PUBLIC

ROUTER --> CMS_SVC

CMS_SVC --> CMS_CACHE

CMS_SVC --> CMS

CMS_CACHE --> CACHE

CACHE -.->|generic storage| GC

GC --> GD

GC --> GS

ROUTER --> REND

REND --> SCHEMA

SCHEMA --> COMPONENTS

style CMS fill:#ff9999

style TRUTH_STORAGE fill:#ffcccc

style CACHE fill:#99ff99

### 7.3 Request Lifecycle Sequence Diagram

Mermaid

sequenceDiagram

participant Browser

participant index.php

participant Router

participant CMS_SVC as ContentCMSService

participant CMS_CACHE as CMSCache

participant CMS as ContentCMS

participant File as kgs-content/pages

participant Renderer as renderer.php

participant Components as Component Files

Browser->>index.php: GET /about/staff-directory

index.php->>index.php: Parse route

index.php->>Router: dispatch('about/staff-directory')

Router->>CMS_SVC: load('about/staff-directory')

CMS_SVC->>CMS_CACHE: get('about/staff-directory')

alt Cache HIT

CMS_CACHE-->>CMS_SVC: return cached page

else Cache MISS

CMS_SVC->>CMS: loadPage('about/staff-directory')

CMS->>File: Read meta.json

CMS->>File: Read components.json

CMS-->>CMS_SVC: return page array

CMS_SVC->>CMS_CACHE: set(page array)

end

CMS_SVC-->>Router: {meta: {...}, components: \[...\]}

Router->>Router: Select layout

Router->>Router: Include header.php

loop For each component

Router->>Renderer: render_component(type, data)

Renderer->>Renderer: Load schema & validate

Renderer->>Components: extract & include

Components-->>Renderer: HTML output

end

Router->>Router: Include footer.php

Router-->>Browser: Complete HTML

### 7.4 Cache Hierarchy Diagram

Mermaid

graph TD

REQ\["Request for page"\]

REQ -->|1. Check| APP_CACHE\["CMSCache.get()"\]

APP_CACHE -->|2a. Hit| RETURN1\["Return cached page"\]

APP_CACHE -->|2b. Miss| TRUTH\["ContentCMS.loadPage()"\]

TRUTH -->|3. Read from| CONTENT\["kgs-content/pages/"\]

CONTENT -->|4. Parse| META\["meta.json"\]

CONTENT -->|4. Parse| COMP\["components.json"\]

META --> NORM\["Normalize page array"\]

COMP --> NORM

NORM -->|5. Store| FILE_CACHE\["GoogleDriveCache.set()"\]

FILE_CACHE -->|6. Write| STORAGE\["kgs-cache/google/&lt;br/&gt;cms_pages_cache/"\]

NORM -->|7. Return| RETURN2\["Return normalized page"\]

RETURN1 --> RENDER\["Rendering Pipeline"\]

RETURN2 --> RENDER

RENDER --> HTML\["HTML Output"\]

style APP_CACHE fill:#99ff99

style TRUTH fill:#ff9999

style CONTENT fill:#ffcccc

style FILE_CACHE fill:#99ff99

style STORAGE fill:#ccffcc

## 8\. Architecture Rules & Constraints

### 8.1 The "No Confusion Ruleset" (CRITICAL)

These rules prevent architectural drift and future refactoring chaos:

#### **RULE 1: ONE SOURCE OF TRUTH**

Code

✓ ONLY authoritative source:

kgs-content/pages/{route}/meta.json

kgs-content/pages/{route}/components.json

✗ Cache is NEVER truth:

\- Can be deleted

\- Can be stale

\- Can be corrupted

\- System must still work

#### **RULE 2: ONE DIRECTION OF DEPENDENCY**

Code

✓ ALLOWED flow:

Router

↓

ContentCMSService

↓

CMSCache

↓

GoogleDriveCache

↓

kgs-cache/google/

✗ NEVER allowed:

\- Router → GoogleDriveCache (bypass service layer)

\- GoogleDriveCache → CMSCache → ContentCMS (reverse)

\- Components → cache (views don't load data)

\- Renderer → CMS (presentation doesn't fetch)

#### **RULE 3: NO DUAL CMS LOGIC**

Code

✗ NEVER have:

\- Both "DriveCMS" and "ContentCMS"

\- Parallel loading paths

\- Multiple sources of truth

\- Content split between systems

✓ Only:

\- One ContentCMS (loads from kgs-content/pages/)

\- One ContentCMSService (orchestrates)

\- Optional Google Drive sync (pulls INTO kgs-content/pages/)

#### **RULE 4: CACHE NEVER DEFINES STRUCTURE**

Code

✓ Cache may contain:

\- meta (page metadata)

\- components (component array)

\- \_meta (internal timestamp/version)

\- data (complete page array)

✗ Cache must NEVER contain:

\- Routing rules

\- File system structure

\- Logic (transformations, business rules)

\- Directory indexes

#### **RULE 5: ROUTER IS PURE ORCHESTRATION**

Code

✓ Router does:

route → normalize → service call → render

✗ Router does NOT:

\- Load content directly

\- Access cache

\- Access filesystem

\- Decide page structure

#### **RULE 6: COMPONENTS ARE DUMB RENDERERS**

Code

✓ Components receive:

\$data (extracted into scope)

✗ Components do NOT:

\- Load CMS pages

\- Check cache

\- Perform routing

\- Fetch from Google

\- Have business logic

#### **RULE 7: GOOGLE CACHE IS GENERIC INFRASTRUCTURE**

Code

✓ GoogleDriveCache knows:

\- group + key + payload (JSON)

\- TTL checks

\- File I/O

\- Schema versioning

✗ GoogleDriveCache does NOT know:

\- "pages" exist

\- "CMS" exists

\- "routes" exist

\- Content structure

## 9\. File Organization Summary

### Content Structure

Code

kgs-content/pages/

├── home/

│ ├── meta.json (page title, layout, description)

│ └── components.json (array of components to render)

├── about/

│ ├── meta.json

│ ├── components.json

│ ├── documents/

│ │ ├── meta.json

│ │ └── components.json

│ ├── policies/

│ │ ├── meta.json

│ │ └── components.json

│ ├── school-board/

│ └── staff-directory/

├── academics/

├── activities/

│ ├── athletics/

│ │ ├── baseball/

│ │ ├── basketball/

│ │ └── ...

│ └── clubs/

├── calendar/

├── dining/

├── family/

├── news/

└── ... \[other pages\]

### Configuration Files

Code

cfg/

├── config.php (site constants, URLs, branding)

├── cache.php (cache directory paths)

├── google.php (Google API credentials)

├── GoogleConfig.php (Google config class)

└── google-service-account.json (OAuth JSON key)

### Component Catalog

Code

app/components/

├── button-group.php ({buttons: \[\]})

├── calendar-embed.php ({calendar_id: string})

├── callout.php ({heading: string, text: string})

├── contact-info.php ({...details})

├── facebook-feed.php ({page_id: string})

├── file-list.php ({files: \[\]})

├── hero.php ({image: string, heading: string})

├── live-feed.php ({items: \[\]})

├── page-header.php ({title: string})

├── photo-gallery.php ({images: \[\]})

├── quick-links.php ({links: \[\]})

├── renderer.php (component dispatcher)

├── rich-text.php ({html: string})

├── school-highlights.php ({highlights: \[\]})

├── sidebar.php ({widgets: \[\]})

├── staff-directory.php ({staff: \[\]})

└── widgets-section.php ({widgets: \[\]})

## Summary

The **Kell Grade School Website** is a carefully architected hybrid CMS system that prioritizes:

- **Simplicity** - Non-technical staff can edit content via JSON + Google Drive
- **Reliability** - Single source of truth prevents data inconsistency
- **Maintainability** - Clear separation of concerns, no code/content mixing
- **Scalability** - Stateless rendering, optional caching, Google integration
- **Resilience** - Works even if cache is deleted; content never depends on cache
- **Testability** - Each layer can be tested independently
- **Extensibility** - New components and pages can be added without touching core

The architecture enforces strict boundaries between layers, preventing the "code/cache/content confusion" that plagues many homegrown CMS projects. This makes it an excellent template for rural schools building web presence with volunteer/staff support.

Notes from Copilot 06/02/2026

**Final Architecture: File-Based, Google-Centric**

**Storage Structure**

Code

website/

├── cfg/

│ ├── config.php (constants, paths, credentials path)

│ └── google-service-account.json (Google OAuth credentials)

│

├── kgs-data/

│ └── settings.json (admin-editable configuration)

│

├── kgs-cache/

│ ├── google/

│ │ ├── drive/

│ │ │ ├── documents_ABC123.json

│ │ │ └── documents_XYZ789.json

│ │ ├── calendar/

│ │ │ └── events_ABC123.json

│ │ ├── sheets/

│ │ ├── slides/

│ │ ├── menus/

│ │ └── ticker/

│ ├── status/

│ │ ├── drive.json

│ │ ├── calendar.json

│ │ └── sheets.json

│ └── locks/

│ ├── drive.lock

│ ├── calendar.lock

│ └── sheets.lock

│

├── kgs-core/

│ ├── bootstrap.php

│ ├── config/

│ │ └── SettingsManager.php

│ └── google/

│ ├── GoogleService.php

│ ├── GoogleDriveManager.php

│ ├── GoogleCalendarManager.php

│ ├── GoogleSheetsManager.php

│ └── GoogleSlidesManager.php

│

├── kgs-content/

│ └── pages/

│

├── public/

│ ├── index.php

│ └── assets/

│

└── tools/

└── workers/

├── refresh-drive-cache.php

├── refresh-calendar-cache.php

└── refresh-sheets-cache.php

**What This Eliminates**

❌ No database setup ❌ No credentials.php ❌ No PDO connections ❌ No table migrations ❌ No backup coordination ❌ No charset issues ❌ No connection pool management ❌ No ORM ❌ No query builder

**SettingsManager: JSON-Backed**

PHP

<?php

// kgs-core/config/SettingsManager.php

class SettingsManager {

private static \$memory = \[\];

private static \$file = null;

public static function init(\$settingsFile) {

self::\$file = \$settingsFile;

}

public static function get(\$key, \$default = null) {

// 1. Memory cache

if (isset(self::\$memory\[\$key\])) {

return self::\$memory\[\$key\];

}

// 2. File cache

\$all = self::loadFile();

if (isset(\$all\[\$key\])) {

self::\$memory\[\$key\] = \$all\[\$key\];

return \$all\[\$key\];

}

// 3. Default

return \$default;

}

public static function set(\$key, \$value) {

\$all = self::loadFile();

\$all\[\$key\] = \$value;

self::saveFile(\$all);

self::\$memory\[\$key\] = \$value;

}

public static function all() {

return self::loadFile();

}

private static function loadFile() {

if (!file_exists(self::\$file)) {

return \[\];

}

return json_decode(file_get_contents(self::\$file), true) ?? \[\];

}

private static function saveFile(\$data) {

// Atomic write: temp file + rename

\$temp = self::\$file . '.tmp';

file_put_contents(\$temp, json_encode(\$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

rename(\$temp, self::\$file);

}

}

// Global helper

function settings(\$key, \$default = null) {

return SettingsManager::get(\$key, \$default);

}

?>

**Initial settings.json**

JSON

{

"calendar_id": "",

"documents_root_folder_id": "",

"breakfast_menu_folder_id": "",

"lunch_menu_folder_id": "",

"ticker_file_id": "",

"upload_root_folder_id": "",

"cache_ttl": 3600,

"debug_mode": false

}

**Bootstrap Integration**

PHP

<?php

// kgs-core/bootstrap.php

if (!defined('ROOT_PATH')) {

define('ROOT_PATH', dirname(\__DIR_\_) . '/');

}

if (defined('KGS_BOOTSTRAP_LOADED')) {

return;

}

define('KGS_BOOTSTRAP_LOADED', true);

/\*

|--------------------------------------------------------------------------

| CONFIG

|--------------------------------------------------------------------------

\*/

require_once ROOT_PATH . 'cfg/config.php';

/\*

|--------------------------------------------------------------------------

| SETTINGS MANAGER

|--------------------------------------------------------------------------

\*/

require_once ROOT_PATH . 'kgs-core/config/SettingsManager.php';

SettingsManager::init(ROOT_PATH . 'kgs-data/settings.json');

/\*

|--------------------------------------------------------------------------

| GOOGLE SERVICE

|--------------------------------------------------------------------------

\*/

require_once ROOT_PATH . 'kgs-core/google/GoogleService.php';

/\*

|--------------------------------------------------------------------------

| HELPERS

|--------------------------------------------------------------------------

\*/

function settings(\$key, \$default = null) {

return SettingsManager::get(\$key, \$default);

}

function config(\$key, \$default = null) {

\$const = strtoupper(\$key);

return defined(\$const) ? constant(\$const) : \$default;

}

function cache_status(\$type) {

\$file = ROOT_PATH . "kgs-cache/status/{\$type}.json";

if (!file_exists(\$file)) {

return null;

}

return json_decode(file_get_contents(\$file), true);

}

function write_cache_status(\$type, \$data) {

\$dir = ROOT_PATH . 'kgs-cache/status';

if (!is_dir(\$dir)) {

mkdir(\$dir, 0755, true);

}

\$file = \$dir . '/' . \$type . '.json';

file_put_contents(\$file, json_encode(\$data, JSON_PRETTY_PRINT));

}

?>

**GoogleService: Credentials Only**

PHP

<?php

// kgs-core/google/GoogleService.php

require_once config('GOOGLE_CREDENTIALS_PATH');

use Google\\Client;

use Google\\Service\\Drive;

use Google\\Service\\Calendar;

use Google\\Service\\Sheets;

class GoogleService {

private static \$client = null;

private static \$drive = null;

private static \$calendar = null;

private static \$sheets = null;

public static function client() {

if (self::\$client !== null) {

return self::\$client;

}

\$credentialsPath = config('GOOGLE_CREDENTIALS_PATH');

if (!file_exists(\$credentialsPath)) {

throw new Exception("Google credentials not found at: {\$credentialsPath}");

}

\$credentials = json_decode(file_get_contents(\$credentialsPath), true);

if (!is_array(\$credentials)) {

throw new Exception("Invalid Google credentials JSON");

}

\$client = new Client();

\$client->setAuthConfig(\$credentials);

\$client->setScopes(\[

Drive::DRIVE_READONLY,

Calendar::CALENDAR_READONLY,

Sheets::SPREADSHEETS_READONLY,

\]);

self::\$client = \$client;

return \$client;

}

public static function drive() {

if (self::\$drive === null) {

self::\$drive = new Drive(self::client());

}

return self::\$drive;

}

public static function calendar() {

if (self::\$calendar === null) {

self::\$calendar = new Calendar(self::client());

}

return self::\$calendar;

}

public static function sheets() {

if (self::\$sheets === null) {

self::\$sheets = new Sheets(self::client());

}

return self::\$sheets;

}

}

?>

**GoogleDriveManager: Recursive Folder Listing**

PHP

<?php

// kgs-core/google/GoogleDriveManager.php

class GoogleDriveManager {

/\*\*

\* List folder contents recursively

\* Returns tree structure of all files and folders

\*/

public static function listFolder(\$folderId, \$recursive = true) {

\$service = GoogleService::drive();

return self::fetchFolderRecursive(\$service, \$folderId, \$recursive);

}

private static function fetchFolderRecursive(\$service, \$folderId, \$recursive) {

\$query = "'{\$folderId}' in parents and trashed=false";

try {

\$results = \$service->files->listFiles(\[

'q' => \$query,

'pageSize' => 1000,

'spaces' => 'drive',

'fields' => 'files(id, name, mimeType, modifiedTime, webViewLink, size)'

\]);

} catch (Exception \$e) {

error_log("GoogleDriveManager::fetchFolderRecursive error: " . \$e->getMessage());

throw \$e;

}

\$items = \[\];

\$isFolder = 'application/vnd.google-apps.folder';

foreach (\$results->getFiles() as \$file) {

\$item = \[

'id' => \$file->getId(),

'name' => \$file->getName(),

'type' => \$file->getMimeType() === \$isFolder ? 'folder' : 'file',

'mimeType' => \$file->getMimeType(),

'modifiedTime' => \$file->getModifiedTime(),

'webViewLink' => \$file->getWebViewLink(),

'size' => \$file->getSize(),

\];

if (\$item\['type'\] === 'folder' && \$recursive) {

\$item\['children'\] = self::fetchFolderRecursive(

\$service,

\$item\['id'\],

\$recursive

);

} else {

\$item\['children'\] = \[\];

}

\$items\[\] = \$item;

}

return \$items;

}

}

?>

**Cache Refresh Worker: Drive**

PHP

<?php

// tools/workers/refresh-drive-cache.php

#!/usr/bin/env php

require_once dirname(\__DIR_\_) . '/../kgs-core/bootstrap.php';

\$folderId = settings('documents_root_folder_id');

if (!\$folderId) {

error_log("documents_root_folder_id not configured in settings.json");

exit(1);

}

\$lockFile = ROOT_PATH . 'kgs-cache/locks/drive.lock';

\$cacheFile = ROOT_PATH . "kgs-cache/google/drive/documents_{\$folderId}.json";

\$tempFile = \$cacheFile . '.tmp';

// Acquire lock

if (file_exists(\$lockFile)) {

\$lockAge = time() - filemtime(\$lockFile);

if (\$lockAge < 600) { // Lock expires after 10 minutes

error_log("Drive cache refresh already in progress (lock age: {\$lockAge}s)");

exit(0);

}

// Lock is stale, proceed

}

touch(\$lockFile);

\$status = \[

'last_refresh' => time(),

'status' => 'pending',

'records' => 0,

'error' => null

\];

try {

error_log("Starting Drive cache refresh for folder: {\$folderId}");

// Fetch data

\$tree = GoogleDriveManager::listFolder(\$folderId, true);

if (!is_array(\$tree)) {

throw new Exception("Invalid response from GoogleDriveManager");

}

// Ensure directory exists

\$dir = dirname(\$cacheFile);

if (!is_dir(\$dir)) {

mkdir(\$dir, 0755, true);

}

// Write temp file

\$json = json_encode(\$tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

file_put_contents(\$tempFile, \$json);

// Atomic rename

rename(\$tempFile, \$cacheFile);

\$status\['status'\] = 'success';

\$status\['records'\] = count(\$tree);

error_log("Drive cache refresh complete. Records: " . count(\$tree));

} catch (Exception \$e) {

\$status\['status'\] = 'error';

\$status\['error'\] = \$e->getMessage();

error_log("Drive cache refresh FAILED: " . \$e->getMessage());

// Clean up temp file

if (file_exists(\$tempFile)) {

unlink(\$tempFile);

}

} finally {

// Release lock

if (file_exists(\$lockFile)) {

unlink(\$lockFile);

}

// Write status

write_cache_status('drive', \$status);

}

?>

Make it executable:

bash

chmod +x tools/workers/refresh-drive-cache.php

**Component: Documents Tree**

PHP

<?php

// app/components/documents-tree.php

/\*\*

\* COMPONENT: Documents Tree

\*

\* Reads from cache only (never calls Google API)

\* Renders recursive folder/file structure

\*/

\$folderId = settings('documents_root_folder_id');

if (!\$folderId) {

echo '&lt;div class="alert alert-warning"&gt;Documents folder not configured.&lt;/div&gt;';

return;

}

\$cacheKey = "documents_{\$folderId}";

\$tree = GoogleDriveCache::get('drive', \$cacheKey);

if (!\$tree) {

echo '&lt;div class="alert alert-info"&gt;Documents cache not yet populated. Run cache refresh worker.&lt;/div&gt;';

return;

}

echo '&lt;div class="documents-tree"&gt;';

render_tree(\$tree);

echo '&lt;/div&gt;';

function render_tree(\$items, \$depth = 0) {

if (empty(\$items)) {

return;

}

\$indent = str_repeat(' ', \$depth);

foreach (\$items as \$item) {

if (\$item\['type'\] === 'folder') {

echo '&lt;div class="folder-item" style="margin-left: ' . (\$depth \* 20) . 'px"&gt;';

echo '&lt;i class="fas fa-folder"&gt;&lt;/i&gt; ';

echo '&lt;strong&gt;' . htmlspecialchars(\$item\['name'\]) . '&lt;/strong&gt;';

echo '&lt;/div&gt;';

if (!empty(\$item\['children'\])) {

render_tree(\$item\['children'\], \$depth + 1);

}

} else {

echo '&lt;div class="file-item" style="margin-left: ' . (\$depth \* 20) . 'px"&gt;';

echo '&lt;a href="' . htmlspecialchars(\$item\['webViewLink'\]) . '" target="\_blank"&gt;';

echo '&lt;i class="fas fa-file"&gt;&lt;/i&gt; ';

echo htmlspecialchars(\$item\['name'\]);

echo '&lt;/a&gt;';

echo '&lt;/div&gt;';

}

}

}

?>

**Test Workflow**

**1\. Set up credentials**

bash

\# Copy your Google service account JSON to:

cfg/google-service-account.json

**2\. Update config.php**

PHP

<?php

// cfg/config.php

define('SITE_NAME', 'Kell Grade School');

define('BASE_URL', '/');

define('ROOT_PATH', dirname(\__DIR_\_) . '/');

define('GOOGLE_CREDENTIALS_PATH', ROOT_PATH . 'cfg/google-service-account.json');

define('DEBUG_MODE', true);

?>

**3\. Create settings.json with a real folder ID**

bash

\# Get folder ID from Google Drive URL:

\# <https://drive.google.com/drive/folders/FOLDER_ID_HERE>

echo '{

"documents_root_folder_id": "PASTE_YOUR_FOLDER_ID",

"calendar_id": "",

"breakfast_menu_folder_id": "",

"lunch_menu_folder_id": "",

"ticker_file_id": ""

}' > kgs-data/settings.json

**4\. Test GoogleService**

PHP

<?php

require_once 'kgs-core/bootstrap.php';

try {

\$client = GoogleService::drive();

echo "✓ Google Drive client initialized successfully\\n";

} catch (Exception \$e) {

echo "✗ Error: " . \$e->getMessage() . "\\n";

}

?>

**5\. Manually run cache refresh**

bash

php tools/workers/refresh-drive-cache.php

Check output:

Code

Starting Drive cache refresh for folder: ABC123

Drive cache refresh complete. Records: 47

**6\. Verify cache was created**

bash

cat kgs-cache/google/drive/documents_ABC123.json | head -20

cat kgs-cache/status/drive.json

**7\. Create page with component**

JSON

// kgs-content/pages/about/documents/components.json

\[

{

"type": "page-header",

"data": {"title": "Documents"}

},

{

"type": "documents-tree",

"data": {}

}

\]

**8\. Load page**

Code

<http://yoursite.com/about/documents>

Should display the cached folder tree.

**Cron Setup**

bash

\# Add to crontab (runs every hour)

0 \* \* \* \* /usr/bin/php /home/user/public_html/tools/workers/refresh-drive-cache.php >> /var/log/kgsweb-drive-refresh.log 2>&1

**What This Gives You**

✅ **No database needed** ✅ **No credentials setup** ✅ **Simple file backups** (zip the directory) ✅ **Easy migration** (copy files, update one setting) ✅ **Debuggable** (JSON files are human-readable) ✅ **Proven architecture** (Google → Cache → Component)

Once this works end-to-end, repeat for:

- Calendar
- Sheets
- Menus
- Ticker

**Next Step**

Should I write out:

- **GoogleCalendarManager** (events fetching)?
- **Calendar cache refresh worker**?
- **Calendar component**?
- **All three to get a second data source working**?

What's next?