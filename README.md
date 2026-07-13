# KELL ENGINE CMS & SITE ARCHITECTURE: MASTER BLUEPRINT (v4.0) 

This master document serves as the unified, single source of truth for the Kell Engine CMS powering Kell Grade School (currently staging at /kgs2026/ac/public/). It synthesizes the system's architectural philosophy, interface specifications, filesystem rules, database (Google Sheet) schemas, page layout architectures, and strict safety guidelines into a single technical contract.  

## 1. ARCHITECTURAL PHILOSOPHY & PRINCIPLES          

Core Principles
* Separation of Concerns: JSON acts as the data layer, PHP manages the application and rendering logic, and CSS handles presentation. 
* DRY (Don’t Repeat Yourself): Highly reusable components, layouts, and helpers prevent code duplication. 
* Performance: High-speed delivery via flat files, cached JSON, and local asset mirroring. 

Headless Infrastructure
* Zero Local Database: Google Drive serves as the asset and file storage system; Google Sheets acts as the relational database and admin panel. 
* The Single Source of Truth: The unified configuration helper config() resolves settings deterministically in the following priority:  
1. config_map.json (synced dynamically from Google Sheets) 
2. config.php
3. google.php
4. Hardcoded PHP Constants
* Subdirectory Compatibility: To prevent broken routing in staging or nested environments (e.g., /kgs2026/ac/public/), all internal site links, assets, and redirects must pass through the url($path) helper. 
* Asset Performance: To eliminate API overhead and network latency, binary assets (such as school logos, favicons, and hero images) are downloaded by the background Worker to public/assets/img/[Key].png. 

Documentation & Maintenance Deficit

Because the CMS bypasses standardized framework documentation (such as Laravel or WordPress), it has a high "Bus Factor." To ensure long-term maintainability by external developers:   
1. Strict Index Mapping: Data models are bound directly to Google Sheet column indices. Changes to sheet structures must update the corresponding index constants immediately. 
2. Explicit Routing: Standardized components must clearly describe how a Spreadsheet row is parsed, mapped, and translated into a visual Component Stack. 

## 2. KELL ENGINE INTERFACE SPECIFICATION (v4.0)          

This interface specification is a strict technical contract. Do not deviate from these signatures, assume undocumented functions, or employ generic/relative filesystem paths.  

2.1 Global Constants & Variables
* ROOT_PATH: The absolute server path used for all file includes and system requires. Never use ./ or ../. Always construct paths like require_path: ROOT_PATH . 'folder/file.php'. 
* config($key): The unified configuration map helper. 
* url($path): Prepends the correct BASE_URL and dynamically handles nested subdirectories. 
* KGS_ASSET_VER: A timestamp-based query parameter appended to stylesheet and script links to force browser cache-busting. 

2.2 Core Functions & Signatures
* render_component(string $type, array data): Extracts variables from `data` into the local scope using EXTR_SKIP to prevent variable collision, then includes the target component fromapp/components/{$type}.php`. 
* get_link_group(string $category): Retrieves and filters an array of links from links_map.json matching the provided category. 
* get_site_menu(?string slug):**Parses `site_menu.json` and returns a recursive navigation tree starting at the optional `slug` node. 
* get_drive_url(string $id, int $size): Formats and returns a proxy URL for Google Drive image thumbnails. 
* kgs_find_folder_in_tree(array $items, string $id): Recursively traverses a local JSON tree structure to locate and return children of a target folder ID. 

2.3 Filesystem Rules & Geography
* Layouts: Separated into visual "sandwich" wrappers located at app/layouts/page/{$layout}-start.php and app/layouts/page/{$layout}-end.php. 
* Components: Modular files stored in app/components/{$name}.php. 
* Content & Cache Directories: 
* kgs-cache/google/drive-trees/: Contains the master JSON index of files. 
* kgs-cache/google/html-content/: Sanitized, safe HTML exports of Google Documents (completely stripped of destructive inline <style> blocks). 
* kgs-cache/google/sheets/: Raw and parsed JSON outputs of synced spreadsheets. 
* public/assets/img/: Local mirror repository for technical assets. 

2.4 Component Design Standards
* Early Exit: Components must self-validate at execution start. If critical data points are absent, use: if (empty($required_var)) return; // Silent Auto-Hide 
* No Active Echoing in Loops: Use PHP output buffering (ob_start() / ob_get_clean()) inside complex looping components to prevent raw, unstructured layout rendering. 

## 3. THE SPREADSHEET SCHEMAS (INDEX-CRITICAL)          

The Kell Engine reads structural data directly from Google Sheet tabs by processing row arrays via exact column indices. Maintain these strict schemas:   

A. Standard Tab (Google IDs, Core Settings)
* Col 0: Key (Lookup key used by the config() helper)  
* Col 1: Value (The raw configuration variable)  
* Col 2: Display Title (User-friendly label)  
* Col 3: Category (Group grouping) 
* Col 4: Description (Contextual notes for admin)  

B. Links Tab (Columns A to I)
* Col 0: Key (Unique identifier) 
* Col 1: Value (A destination URL or an @token pointing to a Google Drive folder) 
* Col 2: Display Title 
* Col 3: Category 
* Col 4: Description 
* Col 5: External? (Boolean string: TRUE or FALSE)  
* Col 6: File Filter (Regex pattern applied to ignore files during token lookups) 
* Col 7: Icon Class 
* Col 8: Icon Style 
* Smart Seeker Logic: If Value starts with an @ character, Worker Task 3 initiates a recursive search through the Google Drive tree to resolve and link the newest file in that target folder. 

C. Widget Registry (Columns A to E)
* Col 0: Key (System identifier) 
* Col 1: Friendly Name 
* Col 2: Component (The target system component template file)  
* Col 3: Data Source (Identifies the external feed or sheet tab)  
* Col 4: Parameter (Defines the configuration variable parsed into the component) 

D. People Directory (Columns A to K)
* Col 0: Title (e.g., Mr., Mrs.) 
* Col 1: First 
* Col 2: Last 
* Col 3: Email 
* Col 4: Role (e.g., 1st Grade Teacher, President)  
* Col 5: Image (Image filename/slug) 
* Col 6: Bio 
* Col 7: Editor? (Boolean) 
* Col 8: Category (Grouping) 
* Col 9: Context (Limits sorting, e.g., staff or board)  
* Col 10: Sort (Integer sorting index)  
* Sieve Logic: Worker Task 5 reads this sheet and filters data by Col 9 context, compiling them into separate caching modules: people_staff.json and people_board.json. 

E. Navigation Tab (Columns A to G)
* Col 0: Key (Page identifier) 
* Col 1: Label (The display name in navigation)  
* Col 2: Parent (Establishes hierarchical parenting)  
* Col 3: Show? (Boolean visibility toggle)  
* Col 4: Icon 
* Col 5: Style 
* Col 6: Sort (Ordering weight) 
* Routing Logic: Worker Task 10 parses these values to compile site_menu.json as a recursive tree with normalized path slugs (parent_slug/child_slug/). 

## 4. THE WORKER ENGINE (refresh-drive-cache.php)          

The background Worker script executes actions in this order to resolve data dependencies without crashing:  
1. Sync Config: Downloads system settings sheets to build config_map.json and layout_map.json. 
2. Index Drive: Crawls the school's Master Root Google Drive folder recursively and caches the nested structure in drive-trees/tree_MASTER_ID.json. 
3. Resolve Tokens: Checks the Links Tab for active @tokens. Resolves tokens against the cached Drive Tree to link the newest files while applying the specified File Filter (Col 6). 
4. Sync Registries: Compiles widget_registry.json and the label-to-key lookup table widget_lookup.json. 
5. Sync People: Matches user profile photos against files indexed in the Drive Tree. Compiles the user security whitelist authorized_users.json. 
6. Sync Aliases: Compares routing changes and triggers cache invalidations via CMSCache::invalidateMany(). 
7. Sync Live Feed: Pulls the social/live feed from its designated 6-column spreadsheet and reverses the array index to ensure chronologically newest entries appear first. 
8. Sync Assets: Downstreams structural layout images and downloads Google Docs source material as sanitized, markup-cleaned HTML files stored in html-content/. 
9. Build Menu: Audits the filesystem at kgs-content/pages/ to cross-reference menu definitions and dynamically write structural updates to site_menu.json. 

## 5. DYNAMIC LAYOUTS & SITEMAP SPECIFICATION         

The site navigation is structured to support simple, low-overhead maintenance. To reduce staff burden, sub-pages for related areas (such as classrooms, clubs, and sports) are bundled into consolidated sectional layouts with tabbed navigation or structural accordion displays.  

Header (Top Bar: Contacts, TeacherEase, Admin Link | Main: Logo, Recursive Nav)  
├── Home Page  
├── Our District  
│  ├── Compliance (Dynamic table referencing policy docs and folder trees) 
│  ├── School Board (Single layout combining board schedule, members, and minutes) 
│  ├── Documents (Recursive document folder tree layout) 
│  ├── Employment (Default content layout) 
│  └── Staff Directory (Compact visual directory compiled from people_staff.json) 
├── Academics  
│  ├── Pre-K (Displays direct classroom information sections) 
│  ├── Grade School (Accordion layouts for Kindergarten through 5th Grade) 
│  ├── Junior High (Accordion layout with subjects: ELA, Math, Science, Social Studies, P.E.) 
│  └── Special Education (Direct classroom layout format) 
├── Student Life  
│  ├── Sports (Single aggregated page displaying calendar, rules, and profiles of coaches) 
│  ├── Clubs (Single aggregated page showing active clubs and faculty sponsor profiles) 
│  ├── Dining (Breakfast and Lunch calendar iframe embeds or linked PDF docs) 
│  ├── Library (Default layout) 
│  ├── Student Handbook (Direct document link or default doc render) 
│  └── Learning Links (Quick reference portal lists) 
├── Family  
│  ├── Registration / Attendance & Absences (Detailed policies, contact options, links) 
│  ├── Forms (Unified health, sports, and supply list folder tree) 
│  └── Parent Teacher Organization (PTO) (Default layout with doc render) 
├── Calendars (Google Calendar integrations with schedule view layouts)  
└── Contact (Interactive contact info and local maps)  
Footer (Main: Contact Info, Quick Links, Suicide Prevention | Copyright + Last Updated Timestamp)  

Page Components & Content Flow

**1. Home Page Layout**         

* Hero Image Container: Full-width rotating hero section reading images cached at public/assets/img/.
* Main Container:
* Row 1: Live Feed Component (left column) + Weather Widget (right column).          
* Row 2: Quick Access Cards (Link tiles reading from config sheets, displaying links to TeacherEase, Calendars, Dining, and Attendance).          
* Row 3: Facebook Feed iframe (left column) + Google Calendar Agenda View (right column).          

**2. Default Page Layout**         

* Main Container:
* Row 1: Breadcrumb trail (parsed dynamically).          
* Row 2: Dynamic Page Title.          
* Row 3: "About" Document (parsed from introductory Google Doc cache).          
* Row 4: "Content" Document (parsed from main content Google Doc cache).          
* Row 5: Custom layout columns.          
* Row 6: Sub-page Cards (automatically populates child-link tiles based on structural sub-menus).          

**3. School Board Page Layout**         

* Main Container:
* Row 1-3: Breadcrumbs, Page Title, and About Document.          
* Row 4: General Agenda Content Document.          
* Row 5 (Left Column): Board Meeting Schedule (sourced from Board Calendar).          
* Row 5 (Right Column): School Board Members list (compact style, displaying Name, Title, and Email from people_board.json without photos).          
* Row 6: Board Document Tree (displays folders with minutes, training certificates, and schedules).          

**4. Academics Page Layout (Pre-K, Grade School, Junior High, Special Ed)**         

* Main Container:
* Row 1-4: Standard Breadcrumbs, Title, About, and Content Docs.          
* Row 5: Quick Access Link Cards (Google Classroom, AR Books, Learning Links, TeacherEase - automatically hidden on Pre-K page via config parameters).          
* Row 6: Class / Subject Accordion Section. Sourced from Google Sheet.          
* Grade School & Junior High: Renders as a single-select accordion (opening one panel automatically collapses active siblings).         
* Pre-K & Special Education: Bypasses the accordion layout to render content sections directly inline.         
* Panel Contents: Left: Class content Google Doc. Right: Teacher profile card populated from people_staff.json.         

**5. Sports & Clubs Layouts**         

* Main Container:
* Row 1-4: Standard Breadcrumbs, Title, About, and Content Docs.          
* Row 5: Upcoming athletic games (from Sports Google Calendar) or Club event calendars.          
* Row 6: Sport or Club content rows:          
* Layout: Left: Information content Google Doc. Right: Coach or Sponsor bio card.         

## 6. COMPONENT LOGIC & TECHNICAL SAFETY         

6.1 The Smart Grid (smart-grid.php)

To provide design flexibility, column sizing is resolved dynamically using proportional math: 
\text{Remaining Width} = 12 - \sum(\text{Explicitly Defined Columns}) 
\text{Auto Width} = \max\left(6, \left\lfloor \frac{\text{Remaining Width}}{\text{Unassigned Column Count}} \right\rfloor\right) 

* Auto-Hide Logic: Columns wrap their inclusion in ob_start(). If the evaluated layout component returns empty data, the column is discarded and the dynamic width of remaining items in the row is recalculated on the fly. 

6.2 Date Sorting Engine (Regex Parsers)

When parsing dates across calendars or files to order content chronologically, bootstrap parses strings using three regex passes: 

1. YYYY-MM-DD format: /(\d{4})[-_ ](\d{1,2})[-_ ](\d{1,2})/
2. YYYY-MM format: /\b(\d{4})[-_ ](0[1-9]|1[0-2])\b/
3. Named dates: /(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{1,2})?,?\s*(\d{4})/i

6.3 Critical Guidelines ("The Gotchas")

* The Muzzle: For AJAX, API, or Worker loop operations, the constant KGS_SILENT_MODE must evaluate to true. Output mechanisms (e.g., console_log() or repairPagesCache()) must check this constant before outputting data to prevent malformed headers or broken JSON payloads. 
* No Whitespace: Core PHP files (config.php, google.php, bootstrap.php) must omit the closing ?> tag. This prevents accidental carriage returns or whitespace from corrupting headers or causing "headers already sent" errors. 
* Variable Shadowing: In the Router loop, use $compType for dynamic component matching. Never use generic loop variables like $type, which can overwrite parent layout parameters. - PDF Embed Fit: Google Drive PDF preview frames should use the ?view=FitH URL parameters with a container height explicitly set to 850px to allow a scroll-free viewing experience on desktop displays. 
* Bust Safari SNAPSHOTS: To bypass mobile Safari's aggressive caching of page state redirects, manual sync reload requests must append a fresh timestamp to the redirection query parameter: window.location.href = window.location.pathname + '?v=' + Date.now(); 

## 7. PENDING ROADMAP & SYSTEM REFINEMENTS         

This consolidated roadmap represents the remaining development tasks, architectural questions, and optimization goals for the Kell Engine.  

A. Administration & Control GUIs

**1. Live Feed Dashboard GUI (live-feed-gui.php)**         

* Objective: Develop a lightweight, secure PHP administrative portal to post, edit, or delete items on the Live Feed.
* Requirements: Enforce a secure administrative login using a credentials check matched directly against the compiled authorized_users.json whitelist.
* Provide a basic web form interface so authorized staff can publish live feed items on-the-go without needing to access or manually edit raw Google Sheets.

**2. Admin Settings Dashboard GUI (admin-gui.php)**         

* Objective: Construct a unified master settings control panel.
* Requirements:
* Secure via the same authorization checks as the Live Feed GUI.
* Implement user-friendly UI controls (e.g., visual toggle icons) to adjust site-wide configuration flags dynamically.

B. Sheets-Driven Site Creator (Sitemap & Page Configuration)

1. Centralized Page & Meta Configuration

* Objective: Migrate static page metadata (meta.json) and layout component architecture (components.json) off the web server and directly into Google Sheets.
* Implementation Strategy:
* Establish a "Pages" master Google Sheet with individual tabs representing each page of the website.
* Row-by-Row Layout Assembly: Build pages from top to bottom.
* Header Metadata: Reserve the top rows of each sheet tab to define metadata (e.g., Title, Layout, and parent: relationships, such as parent: sports to programmatically nest the Volleyball subpage under the Sports parent section).
* Component List: Position component mapping definitions directly below the metadata section to tell the Router, row-by-row, which blocks to fetch and render.

**2. Dual-Layer Show/Hide Page Toggles**         

* Objective: Create a unified visibility pipeline for custom stakeholder pages.
* Requirements:
* Support two layers of visibility: 1. A master site-wide configuration setting tab to toggle pages. 2. Individual page-level sheet tabs with local show/hide options.         
* Synchronize the states so that toggling visibility on either the master configuration sheet or an individual page sheet updates the state of both.

C. Page Layouts, Templates, & Component Enhancements

**1. Academic, Sports, & Club Page Templates**         

* Objective: Finalize unified templates for classroom, athletic, and extracurricular sections.
* Implementation Strategy:
* Create standalone Google Document "content" sheets for subpages (Academics, Sports, Clubs, PTO, Main Office, etc.). If content exists, it displays automatically.
* Add settings toggles so administrators can choose to show or hide the "About Document" and "Content Document" sections independently on a per-page or global basis.
* Fallback Content for News Page: Design a fallback layout for the /news page so that helpful content or general updates are rendered even if the current Live Feed is completely empty.

**2. Google Sites Integration Templates**         

* Objective: Provide a path for teachers, coaches, and school organizations who prefer building and maintaining their own secondary Google Sites.
* Requirements:
* Design clean Google Sites design templates (for sports teams, individual classrooms, PTO, school board, and main office).
* Write a guide on how to configure and link these external Google Sites into the main school directory and navigation structures.

**3. District Page Layout Revamp**         

* Objective: Re-architect the primary District page layout to match the historical organization of /administration/ on the live site.
* Requirements: Consolidate officer directories, board links, and administrative profiles into a structured, highly readable portal.

**4. People List Default Profile Photos**         

* Objective: Simplify photo management for staff profiles.
* Requirements: Allow the People directory rendering logic to accept an @key (e.g., @default-avatar) to display a fallback placeholder graphic if a staff member does not have an uploaded photo in the Google Drive asset directory.

D. System Optimization, Backups, & Documentation

**1. Configuration Sheet Optimization (Dry Sweep)**         

* Objective: Clean up the master cfg spreadsheet to remove redundancies.
* Target Corrections:
* Reference Keys for Staff Profiles: Instead of hardcoding staff names like the Superintendent, Principal, or Secretary inside the "Site Settings" tab, dynamically reference their index entries on the "People"sheet using unique identifier keys (e.g., @superintendent).
* Duplicate Links: Identify and remove duplicated data resources (such as multiple entries for the Illinois School District Report Card link).

**2. Data Portability & System Backups**         

* Objective: Establish a backup strategy for the entire school platform.
* Requirements:
* Develop a secure, scriptable pipeline to bundle and export the code, flat files, local cache directories, and Google Drive directories into an offline, compressed archive.

**3. Comprehensive Documentation & Knowledge Base**         

* Objective: Address the "Bus Factor" by writing a comprehensive wiki and administration guide.
* Content Scope:
* Domain registry details and server hosting environments.
* Detailed technical guidelines on Google Drive and Sheet API integrations.
* Best-practice instructions for school staff to update sheets, upload documents, manage files, and configure external Google Sites.
