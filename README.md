# kgsweb
KGS Master Technical Blueprint (System Version 3.1).

I have designed this document to be a "reboot-proof" data packet. It combines
the architectural philosophy with the exact technical "DNA" (regex, indices, and
math) required to maintain the Kell Engine without deviation.

KGS PROJECT MASTER BLUEPRINT: THE "KELL ENGINE" (JULY 2026)

1. ARCHITECTURAL PHILOSOPHY

  - Headless Infrastructure: Zero local database. Google Drive is the file
    system; Google Sheets is the Admin Panel/Database.
  - Single Source of Truth: The config() helper in bootstrap.php.
      - Priority: 1. config_map.json (Synced from Sheets) | 2. config.php | 3.
        google.php | 4. PHP Constants.
  - Pathing: All internal links MUST use url($path) to ensure compatibility with
    the /kgs2026/ac/public/ subdirectory.
  - Asset Management: The Worker downloads binary assets (Logo, Favicon, Hero)
    to public/assets/img/[Key].png to eliminate API overhead and ensure CSS
    stability.

2. THE SPREADSHEET SCHEMAS (INDICES ARE CRITICAL)

A. Standard Tabs (Google IDs, Site Settings)

Col 0:Key | Col 1:Value | Col 2:Display Title | Col 3:Category |
Col 4:Description

B. Links Tab (A:I)

Col 0:Key | Col 1:Value (URL or @token) | Col 2:Display Title | Col 3:Category |
Col 4:Description | Col 5:External? (TRUE/FALSE) | Col 6:File Filter |
Col 7:Icon Class | Col 8:Icon Style

  - Logic: If Value starts with @, Worker Task 3 performs a "Smart Seeker"
    look-up for the newest file in that folder.

C. Widget Registry (A:E)

Col 0:Key | Col 1:Friendly Name | Col 2:Component | Col 3:Data Source |
Col 4:Parameter

  - Logic: Parameter (Col 4) defines the variable name passed to the component
    (e.g., folder_id, doc_id, url).

D. People Directory (A:K)

Col 0:Title | Col 1:First | Col 2:Last | Col 3:Email | Col 4:Role | Col 5:Image
(Slug) | Col 6:Bio | Col 7:Editor? | Col 8:Category | Col 9:Context
(staff/board) | Col 10:Sort

  - Sieve Logic: Worker Task 5 splits rows into people_staff.json and
    people_board.json based on Col 9.

E. Navigation Tab (A:G)

Col 0:Key | Col 1:Label | Col 2:Parent | Col 3:Show? | Col 4:Icon | Col 5:Style
| Col 6:Sort

  - Pathing: Worker Task 10 builds site_menu.json recursively:
    parent_slug/child_slug/.

3. THE WORKER ENGINE (refresh-drive-cache.php)

Executes in this strict order to handle dependencies:

1.  Sync Config: Builds config_map.json and layout_map.json.
2.  Index Drive: Recursively crawls Master Root into
    drive-trees/tree_MASTER_ID.json.
3.  Resolve Tokens: Checks Links Tab for @tokens. Searches index for newest
    file. Applies File Filter (Col 6) to skip "junk" files (e.g., matching
    'Policy' only).
4.  Sync Registries: Builds widget_registry.json and widget_lookup.json
    (Label-to-Key map).
5.  Sync People: Resolves lastname-firstname photos using the Drive Index.
    Builds authorized_users.json whitelist.
6.  Sync Aliases: Detects changes and runs CMSCache::invalidateMany().
7.  Sync Live Feed: Processes 6-column sheet; reverses array for "Newest First"
    display.
8.  Sync Assets: Downloads images and exports Google Docs to sanitized
    html-content/.
9.  Build Menu: Scans kgs-content/pages/ to automate the site navigation JSON.

4. COMPONENT LOGIC & CORE HELPERS

The Smart Grid (smart-grid.php)

  - Hybrid Logic: Resolves Label \rightarrow Lookup JSON \rightarrow Registry
    Key \rightarrow Data.
  - Proportional Fill Math: Remaining = 12 - Total_Manual_Widths Auto_Width =
    floor(Remaining / Unassigned_Active_Count) Minimum_Width = 6 (Prevents
    unreadability).
  - Auto-Hide: Uses ob_start(). If component output is empty, the grid column is
    deleted from the row.

Date Sorting Regex (bootstrap.php)

1.  YYYY-MM-DD: /(\d{4})[-_ ](\d{1,2})[-_ ](\d{1,2})/
2.  YYYY-MM: /\b(\d{4})[-_ ](0[1-9]|1[0-2])\b/
3.  Named: /(January|...|December)\s+(\d{1,2})?,?\s*(\d{4})/i

The Seeker (bootstrap.php)

kgs_find_folder_in_tree($items, $targetId): Recursively searches the master JSON
for a folder ID and returns the children array.

5. TECHNICAL SAFETY ("THE GOTCHAS")

  - The Muzzle: KGS_SILENT_MODE must be true for AJAX/API calls. console_log and
    repairPagesCache MUST check this constant before echoing.
  - No Whitespace: PHP files (config, google, bootstrap) MUST NOT have a closing
    ?> tag.
  - Variable Shadowing: In the Router loop, use $compType for the loop variable.
    NEVER use $type, or it will overwrite the $type needed by the Sidebar.
  - PDF Fit: Use ?view=FitH in Google iFrames with a height of 850px for
    scroll-free menus.
  - Bust Cache: Append ?v=' + Date.now() to window.location.href for the manual
    sync reload to kill mobile Safari "snapshots."

6. FOLDER GEOGRAPHY

  - kgs-cache/google/drive-trees/: Master JSON index.
  - kgs-cache/google/html-content/: Sanitized Doc exports (no <style> tags).
  - kgs-cache/google/sheets/: Live feed and spreadsheet JSONs.
  - app/layouts/page/: The "Sandwich" wrappers (now all 12-column).
  - public/assets/img/: Local mirror of technical Drive assets.

7. PENDING ROADMAP

  - [TASK 24]: Finalizing the Pages tab logic in the Spreadsheet to replace
    server-side folders.
  - [TASK 7]: Integration of real-time weather.gov API data.
  - [TASK 4/18]: Native-feel CSS for the Facebook Feed container.


/////

//////////

Here is the current folder/file structure of the server and of the google drive (CMS):

///// HOSTED SERVER /////

```
SERVER_ROOT
|	.htaccess
|   composer.json
|	composer.lock
|   README.md
|   
+---admin
|	   callback.php
|	   dashboard.php
|	   live-feed-post.php
|	   page-editor.php
|	   upload-handler.php
|	   
+---app
|   +---components
|   |	   button-group.php
|   |	   calendar-embed.php
|   |	   callout.php
|   |	   compliance-table.php
|   |	   contact-info.php
|   |	   facebook-feed.php
|   |	   file-list.php
|   |	   google-doc-content.php
|   |	   hero.php
|   |	   latest-file-view.php
|   |	   link-list.php
|   |	   link-tiles.php
|   |	   live-feed.php
|   |	   page-header.php
|   |	   people-list.php
|   |	   photo-gallery.php
|   |	   quick-links.php
|   |	   renderer.php
|   |	   rich-text.php
|   |	   school-highlights.php
|   |	   smart-grid.php
|   |	   staff-directory.php
|   |	   weather.php
|   |	   
|   \---layouts
|	   |   default.php
|	   |   footer.php
|	   |   header.php
|	   |   home.php
|	   |   navigation.php
|	   |   
|	   \---page
|			academics-end.php
|			academics-start.php
|			activities-end.php
|			activities-start.php
|			athletics-end.php
|			athletics-start.php
|			clubs-end.php
|			clubs-start.php
|			default-end.php
|			default-start.php
|			district-end.php
|			district-start.php
|			full-width-end.php
|			full-width-start.php
|			home-end.php
|			home-start.php
|			school-board-end.php
|			school-board-start.php
|			   
+---cfg
|		cache.php
|		config.php
|		google-service-account.json
|		google.php
|	   
+---kgs-cache
|   +---google
|   |   |   readme.txt
|   |   |   
|   |   +---cms_pages_cache
|   |   +---documents
|   |   +---drive
|   |   +---drive-trees
|   |   |	   tree_1o0X89kQL_cXEUpPOSZXcCUFz8bzCtmAp.json
|   |   |	   
|   |   +---events
|   |   +---html-content
|   |   |		11ialScCxV3Dzu5HchqkqUCzJDL8mLdNbodeHOvykVOU.html
|   |   |		11P9LYb0ov40zDxi22eqIVgR9r1veVEDJlOzB_N7GL0o.html
|   |   |		1bQL5uJV1Vbd0e19Zr4aZoJYgZzfEsBYcRW_1vzp73YU.html
|   |   |		1bSlpoXwdnj78olP1RhIWlRCHgSfVXKW-uS_1LQ15xww.html
|   |   |		1i8NOPakDDpJRZDzikGE-YR3o-1TYoHiZG3pdsHOAjKc.html
|   |   |		1JvZGs5tpB31_gWQOhqPE61xZRcNY2rOSbtDXL7OIG9g.html
|   |   |		1KFtLFL8clL-dvnMWUs5KW7Uh5vcaHe0LSTHA_FUhWGU.html
|   |   |		1Ud9untovTpG1ePFZvTKvFyPI2hPMLKNbPxVdpcygCM4.html
|   |   |		1y2gZBxipLh2gld_tqMq2wnGCmpZV1igp7NlJAiEgZhQ.html
|   |   |		1Zirp9wWczzHzTH0O2vYeiANSKXOP3WjChViNP9F3SlI.html
|   |   |	   
|   |   +---menus
|   |   +---sheets
|   |   |		feed_18MuCzAUmPSY8mB2s2NENGgQO39xrkl7uFP5pV2OItrw.json
|   |   |		
|   |   \---slides
|   |			aliases_map.json
|   |			authorized_users.json
|   |			config_map.json
|   |			icon_map.json
|   |			last_refresh.json
|   |			layout_map.json
|   |			links_map.json
|   |			people_board.json
|   |			people_staff.json
|   |			readme.txt
|   |			site_menu.json
|   |			tile_lookup.json
|   |			tile_registry.json
|   |			widget_lookup.json
|   |			widget_registry.json
|   +---locks
|   |	   	manual_refresh_time.txt
|   |	   
|   \---weather_data.json
|   
+---kgs-content
|   \---pages
|	   +---academics
|	   |	   components.json
|	   |	   meta.json
|	   |	   
|	   +---activities
|	   |   |   components.json
|	   |   |   meta.json
|	   |   |   
|	   |   +---athletics
|	   |   |   |   components.json
|	   |   |   |   meta.json
|	   |   |   |   
|	   |   |   +---baseball
|	   |   |   |	   components.json
|	   |   |   |	   meta.json
|	   |   |   |	   
|	   |   |   +---basketball
|	   |   |   |	   components.json
|	   |   |   |	   meta.json
|	   |   |   |	   
|	   |   |   +---bowling
|	   |   |   |	   components.json
|	   |   |   |	   meta.json
|	   |   |   |	   
|	   |   |   +---cheerleading
|	   |   |   |	   components.json
|	   |   |   |	   meta.json
|	   |   |   |	   
|	   |   |   +---cross-country
|	   |   |   |	   components.json
|	   |   |   |	   meta.json
|	   |   |   |	   
|	   |   |   \---volleyball
|	   |   |		   components.json
|	   |   |		   meta.json
|	   |   |		   
|	   |   \---clubs
|	   |	   |   components.json
|	   |	   |   meta.json
|	   |	   |   
|	   |	   +---book-club
|	   |	   |	   components.json
|	   |	   |	   meta.json
|	   |	   |	   
|	   |	   +---brain-games
|	   |	   |	   components.json
|	   |	   |	   meta.json
|	   |	   |	   
|	   |	   +---cooking-club
|	   |	   |	   components.json
|	   |	   |	   meta.json
|	   |	   |	   
|	   |	   +---scholar-bowl
|	   |	   |	   components.json
|	   |	   |	   meta.json
|	   |	   |	   
|	   |	   +---student-council
|	   |	   |	   components.json
|	   |	   |	   meta.json
|	   |	   |	   
|	   |	   \---yearbook
|	   |			   components.json
|	   |			   meta.json
|	   |			   
|	   +---calendar
|	   |	   components.json
|	   |	   meta.json
|	   |	   
|	   +---contact
|	   |	   components.json
|	   |	   meta.json
|	   |	   
|	   +---dining
|	   |	   components.json
|	   |	   meta.json
|	   |	   
|	   +---district
|	   |   |   components.json
|	   |   |   meta.json
|	   |   |   
|	   |   +---compliance
|	   |   |	   components.json
|	   |   |	   meta.json
|	   |   |	   
|	   |   +---documents
|	   |   |   |   components.json
|	   |   |   |   meta.json
|	   |   |   |   
|	   |   |   \---policies
|	   |   |	   |   components.json
|	   |   |	   |   meta.json
|	   |   |	   |   
|	   |   |	   \---soppa
|	   |   |			   components.json
|	   |   |			   meta.json
|	   |   |			   
|	   |   +---employment
|	   |   |	   components.json
|	   |   |	   meta.json
|	   |   |	   
|	   |   +---school-board
|	   |   |   |   components.json
|	   |   |   |   meta.json
|	   |   |   |   
|	   |   |   +---school-board-agendas
|	   |   |   |	   components.json
|	   |   |   |	   meta.json
|	   |   |   |	   
|	   |   |   +---school-board-members
|	   |   |   +---school-board-minutes
|	   |   |   |	   components.json
|	   |   |   |	   meta.json
|	   |   |   |	   
|	   |   |   +---school-board-schedule
|	   |   |   \---school-board-training
|	   |   \---staff-directory
|	   |		   components.json
|	   |		   meta.json
|	   |		   
|	   +---family
|	   |   |   components.json
|	   |   |   meta.json
|	   |   |   
|	   |   \---pto
|	   |		   components.json
|	   |		   meta.json
|	   |		   
|	   +---home
|	   |	   components.json
|	   |	   meta.json
|	   |	   
|	   \---news
|			   components.json
|			   meta.json
|			   
+---kgs-core
|   |   autoload.php
|   |   bootstrap.php
|   |   CacheManager.php
|   |   EventBus.php
|   |   ModuleManager.php
|   |   Router.php
|   |   
|   +---bootstrap
|   |	   RuntimeRules.php
|   |	   ServiceContainer.php
|   |	   
|   +---cms
|   |	   CMSCache.php
|   |	   ComponentRenderer.php
|   |	   ComponentSchema.php
|   |	   ComponentValidator.php
|   |	   ContentCMS.php
|   |	   
|   +---google
|   |	   GoogleCalendar.php
|   |	   GoogleConfig.php
|   |	   GoogleDrive.php
|   |	   GoogleDriveCache.php
|   |	   GoogleDriveClient.php
|   |	   GoogleDriveManager.php
|   |	   GoogleDriveSync.php
|   |	   GoogleHelpers.php
|   |	   GoogleService.php
|   |	   GoogleSheets.php
|   |	   GoogleSlides.php
|   |	   
|   +---services
|   |	   ContentCMSService.php
|   |	   RouteAliasService.php
|   |	   
|   \---workers
|		   drive-sync.php
|		   refresh-drive-cache.php
|		   
+---public
|   |   .htaccess
|   |   icons.php
|   |   index.php
|   |   refresh-cache.php
|   |   
|   \---assets
|	   +---css
|	   |	   base.css
|	   |	   footer.css
|	   |	   hero.css
|	   |	   layout.css
|	   |	   navigation.css
|	   |	   style.css
|	   |	   weather.css
|	   |	   
|	   +---img
|	   |	   emp_placeholder_240x300-feather.png
|	   |	   emp_placeholder_240x300.png
|	   |	   feathers.png
|	   |	   feathers_3.png
|	   |	   hero_image.png
|	   |	   hero_img.png
|	   |	   il_ed_job_bank_logo.png
|	   |	   indian-head-bluewhite_300.png
|	   |	   indian_head.png
|	   |	   indian_head_2.png
|	   |	   indian_head_2.svg
|	   |	   indian_head_300x300.png
|	   |	   indian_head_32x32.png
|	   |	   indian_head_512x512.png
|	   |	   indian_head_768x768.png
|	   |	   indian_head_blue.png
|	   |	   indian_head_img_ID.png
|	   |	   k-feather.png
|	   |	   kell_wall.png
|	   |	   k_feather.png
|	   |	   k_feather_300x300.png
|	   |	   k_feather_768x768.png
|	   |	   k_feather_bluewhite.png
|	   |	   k_feather_transparent_outline.png
|	   |	   nav_bg_img_ID.png
|	   |	   site_favicon_img_ID.png
|	   |	   staff_photo_placeholder_img_id.png
|	   |	   
|	   \---js
|
+---tools
|   +---for-reference-only
|   |   \---wordpress-plugins
|   |	   +---kgs-kell-weather
|   |	   |   |   kgs-kell-weather.php
|   |	   |   |   
|   |	   |   +---assets
|   |	   |   |   +---css
|   |	   |   |   |	   kgs-kell-weather.css
|   |	   |   |   |	   
|   |	   |   |   \---js
|   |	   |   |		   kgs-kell-weather.js
|   |	   |   |		   
|   |	   |   \---includes
|   |	   |		   weather-display.php
|   |	   |		   
|   |	   \---kgsweb-google-integration
|   |		   |   Actions Required by the Google Workspace for Education Administrator - Email.docx
|   |		   |   full-project-scaffold.txt
|   |		   |   kgsweb-google-integration-spec-doc.txt
|   |		   |   kgsweb-google-integration.php
|   |		   |   README - kgsweb-google-integration.txt
|   |		   |   visual flowchart diagram.png
|   |		   |   
|   |		   +---css
|   |		   |	   kgsweb-style.css
|   |		   |	   
|   |		   +---includes
|   |		   |	   class-kgsweb-google-admin.php
|   |		   |	   class-kgsweb-google-display.php
|   |		   |	   class-kgsweb-google-drive-docs.php
|   |		   |	   class-kgsweb-google-helpers.php
|   |		   |	   class-kgsweb-google-integration.php
|   |		   |	   class-kgsweb-google-menus.php
|   |		   |	   class-kgsweb-google-rest-api.php
|   |		   |	   class-kgsweb-google-sheets.php
|   |		   |	   class-kgsweb-google-shortcodes.php
|   |		   |	   class-kgsweb-google-slides.php
|   |		   |	   class-kgsweb-google-ticker.php
|   |		   |	   class-kgsweb-google-upcoming-events.php
|   |		   |	   
|   |		   \---js
|   |				   kgsweb-admin.js
|   |				   kgsweb-cache.js
|   |				   kgsweb-calendar.js
|   |				   kgsweb-datetime.js
|   |				   kgsweb-display.js
|   |				   kgsweb-documents.js
|   |				   kgsweb-folders.js
|   |				   kgsweb-format.js
|   |				   kgsweb-helpers.js
|   |				   kgsweb-menus.js
|   |				   kgsweb-sheets.js
|   |				   kgsweb-slides.js
|   |				   kgsweb-ticker.js
|   |						  
|	+---notes
|   |	   Automating the Sync.txt
|   |	   CMS Architecture - current state as of 06-01-2026.txt
|   |	   Notes from Copilot 06-02-2026.docx
|   |	   Notes-from-Copilot_06-02-2026.md
|   |	   tree.txt
|   |		
|   \---scripts
|	   	architecture-test.php
|	   	icon-preview.php
|	   	migrate_drive_to_cmscache.php
|	   	migrate_home_to_cmscache.php
|	   	test-drive.php
|				  
\---vendor
		[all of the vendor files]
```

///// GOOGLE DRIVE (THE CMS) /////
```
GOOGLE DRIVE ROOT
|   
\---kgsweb
    +---kgsweb_inhouse
    |   +---_kgsweb_admin
    |   |       [KGS] WEBSITE CONTROL PANEL.gsheet
    |   |       
    |   +---Alert Scroll
    |   |   |   How to use the Ticker.docx
    |   |   |   USE THIS for Alert Scroll Updates.gdoc

    |   |   \---USE THIS for Alert Scroll Updates
    |   +---videos
    |   |       every-letter-makes-a-sound-leapfrog.mp4
    |   |       
    |   +---Hallway Display
    |   |       2025-02.pptx
    |   |       
    |   +---Editable Docs
    |   |   |   template_yyyy-mm-dd_board_special-meeting_publichearing_agenda.docx
    |   |   |   template_school-board_yyyy-mm-dd_special-meeting_budget-hearing_agenda.docx
    |   |   |   mckinney-vento-homeless-program.docx
    |   |   |   template_kell_school-supplies-list_20xx-20xx.docx
    |   |   |   template_school-board_yyyy-mm-dd_meeting_agenda.docx
    |   |   |   template_school-board_yyyy-mm-dd_special-meeting_agenda.docx
    |   |   |   
    |   |   \---letterhead
    |   |           kellcsd2-letterhead.docx
    |   |           kellcsd2-school-board-letterhead.docx
    |   |           
    |   \---Kgsweb Content
    |       |   Live-Feed.gsheet
    |       |   
    |       +---People	
    |       |   |   [KGS] PEOPLE DIRECTORY.gsheet
    |       |   |   
    |       |   \---Staff Photos
    |       |       	slater-alexandra.png
    |       |       	knepp-penny.png
    |       |       	meador-dawna.png
    |       |       	pearce-kim.png
    |       |       	donoho-jeanna.png
    |       |       	arnold-lori.png
    |       |       	knight-keith.png
    |       |       	garrison-karen.png
    |       |       	benjamin-julie.png
    |       |       	taylor-tom.png
    |       |       	hoyt-george.png
    |       |       	juday-lyric.png
    |       |       	staff_photo_placeholder_img.png
    |       |               
    |       +---Brand Images
    |       |       k_feather_300x300.png
    |       |       indian_head_500x500.png
    |       |       indian_head_img_ID.png
    |       |       kell_wall.png
    |       |       feathers_3.png
    |       |       feathers.png
    |       |       k_feather_bluewhite.png
    |       |       site_favicon_img_ID.png
    |       |       k_feather_768x768.png
    |       |       indian_head.png
    |       |       indian_head_512x512.png
    |       |       hero-image.png
    |       |       staff_photo_placeholder_img_id.png
    |       |       nav_bg_img_ID.png
    |       |       k_feather.png
    |       |       k_feather_transparent_outline.png
    |       |       hero_image.png
    |       |       
    |       +---Live Feed Files
    |       +---About Sections
    |       |       About KGS.gdoc
    |       |       About the School Board.gdoc
    |       |       About PTO.gdoc
    |       |       About KGS Academics.gdoc
    |       |       About KGS Athletics.gdoc
    |       |       About KGS Dining.gdoc
    |       |       About KGS Calendars.gdoc
    |       |       About KGS Clubs.gdoc
    |       |       About KGS Family.gdoc
    |       |       About Employment Opportunities.gdoc
    |       |       
    |       \---Feature Images
    |           \---PTO Feature Image
    |                   kellgradeschoolpto.png
    |                   
    \---kgsweb_public
        +---Documents
        |   +---Financial
        |   |   |   ASA form for FY25.pdf
        |   |   |   
        |   |   +---Budget
        |   |   |       budget_fy-2023.pdf
        |   |   |       Budget FY'26 (2025-2026) Final.xlsx
        |   |   |       
        |   |   +---Annual Contracts over $25,000
        |   |   |       Contracts Exceeding 25000 (FY 2022).pdf
        |   |   |       Annual Contracts over $25,000.docx
        |   |   |       
        |   |   +---Esser
        |   |   |       Esser III Spending Plan (2021).pdf
        |   |   |       Esser.rtf
        |   |   |       
        |   |   \---Admin-Teacher Compensation Report
        |   |           Salary Schedule (2022-2023).pdf
        |   |           Admin-Teacher Compensation Report (FY 2022).pdf
        |   |           EIS Administrator and Teacher Salary Report 2025.pdf
        |   |           
        |   +---Registration
        |   |   |   OVER THE COUNTER MED AUTHORIZATION.pdf
        |   |   |   Kell Medical Release Form.pdf
        |   |   |   SCHOOL MEDICATION  AUTORIZATION FORM.docx.pdf
        |   |   |   KELL GRADE SCHOOL - COMMUNICATION.pdf
        |   |   |   State Health Requirements.pdf
        |   |   |   child-health-exam-form-revised-01-31-2024.pdf
        |   |   |   dentalexamform20191022.pdf
        |   |   |   eye-examination-waiver-050216.pdf
        |   |   |   eye-examination-report-050216.pdf
        |   |   |   dental-exam-waiver-2025.pdf
        |   |   |   childhood-lead-risk-questionnaire 2025.pdf
        |   |   |   ISBE Race & Ethnicity Data Form.pdf
        |   |   |   Student Information Page.pdf
        |   |   |   VERIFICATION RESIDENCY.pdf
        |   |   |   
        |   |   \---Supply Lists
        |   |           Kell-School-Supplies-List-2025-2026-PDF-VERSION.pdf
        |   |           Kell-School-Supplies-List-2025-2026-WORD-DOC-VERSION.docx
        |   |           
        |   +---Media
        |   +---Policies
        |   |   +---SOPPA Policy
        |   |   |       i-Ready SOPPA.pdf
        |   |   |       ClassDojo SOPPA.pdf
        |   |   |       GoGuardian_-_Illinois SOPPA.pdf
        |   |   |       GoGuardian SOPPA.pdf
        |   |   |       Student-Data-Privacy-Laws.pdf
        |   |   |       Student Data Privacy.docx
        |   |   |       SOPPA.docx
        |   |   |       Teacher Ease SOPPA.pdf
        |   |   |       Renaissance SOPPA.pdf
        |   |   |       Renaissance MyOn NDPA 1.0a SOPPA.pdf
        |   |   |       Prodigy SOPPA.pdf
        |   |   |       GimKit SOPPA.pdf
        |   |   |       Renaissance All SOPPA.pdf
        |   |   |       Annual Notice - SOPPA.pdf
        |   |   |       i-Ready SOPPA.rtf
        |   |   |       ABCmouse SOPPA.pdf
        |   |   |       105-ILCS-85_Student-Online-Personal-Protection-Act.pdf
        |   |   |       
        |   |   +---Delivery Information
        |   |   |       Delivery Information.pdf
        |   |   |       
        |   |   +---Website Privacy Policy
        |   |   |       Website Privacy Policy.pdf
        |   |   |       
        |   |   +---Freedom of Information Act
        |   |   |       Freedom of Information Act.pdf
        |   |   |       FOIA Officer Information.docx
        |   |   |       
        |   |   +---Handbook
        |   |   |       Handbook 2025-2026 Updated #3.docx.pdf
        |   |   |       
        |   |   +---Bullying Prevention Policy
        |   |   |       Bullying Policy (2020-12-10).pdf
        |   |   |       Kell Bullying Prevention (1).docx
        |   |   |       Bullying Prevention Policy (1).pdf
        |   |   |       
        |   |   +---Return to School Plan
        |   |   |       Return to School Plan 2021-2022.pdf
        |   |   |       Plan for Safe Return to School 2021-2022.pdf
        |   |   |       
        |   |   +---Anti-Bias Education Policies
        |   |   |       Anti-Bias Education Policies.pdf
        |   |   |       
        |   |   +---Suicide Prevention Policy
        |   |   |       Suicide Awareness and Prevention Policy & Resources.pdf
        |   |   |       
        |   |   +---Sexual Abuse Policy-Faith's Law Policy
        |   |   |       Sexual Abuse Policy-Faith's Law Policy.pdf
        |   |   |       
        |   |   +---Diabetes Policy
        |   |   |       Understanding Type 1 Diabetes for Parents and Guardians.pdf
        |   |   |       
        |   |   +---Code of Professional Conduct Policy
        |   |   |       Code of Professional Conduct Policy.pdf
        |   |   |       
        |   |   +---School Choice Policy
        |   |   |       School Choice (2013-11-18).pdf
        |   |   |       
        |   |   +---McKinney-Vento Homeless Program
        |   |   |       McKinney-Vento Homeless Program.pdf
        |   |   |       
        |   |   \---Supplemental Educational Services
        |   |           Supplemental Educational Services (2013-11-18).pdf
        |   |           
        |   +---Forms
        |   +---Collective Bargaining Agreements
        |   |       Collective Bargaining Agreement.pdf
        |   |       
        |   \---School Board Records
        |       +---School Board Meeting Schedule
        |       |       Kell School Board meeting dates 2026-27.docx
        |       |       
        |       +---School Board Meeting Minutes
        |       |   +---2025
        |       |   |       school board 2025-01-23 meeting minutes.pdf
        |       |   |       school board 2025-07 meeting minutes.pdf
        |       |   |       school board 2025-08 meeting minutes.pdf
        |       |   |       school board 2025-05 meeting minutes.pdf
        |       |   |       school board 2025-04 meeting minutes.pdf
        |       |   |       Minutes November 2025.docx
        |       |   |       Minutes October 2025.docx
        |       |   |       Minutes December 2025.docx
        |       |   |       Minutes September 2025.docx
        |       |   |       
        |       |   +---2022
        |       |   |       school board 2022-09-08 meeting minutes.pdf
        |       |   |       school board 2022-10-13 meeting minutes.pdf
        |       |   |       school board 2022-08-18 meeting minutes.pdf
        |       |   |       school board 2022-11-03 special meeting minutes.pdf
        |       |   |       school board 2022-09-22 special meeting minutes budget hearing.pdf
        |       |   |       school board 2022-12-08 meeting minutes.pdf
        |       |   |       school board 2022-07-21 meeting minutes.pdf
        |       |   |       school board 2022-11-17 meeting minutes.pdf
        |       |   |       
        |       |   +---2023
        |       |   |       school board 2023-01-12 special meeting minutes.pdf
        |       |   |       school board 2023-01-12 meeting minutes.pdf
        |       |   |       
        |       |   +---2024
        |       |   \---2026
        |       |           Minutes April 2026.docx
        |       |           Minutes January 2026 Updated.docx
        |       |           Minutes February 2026.docx
        |       |           Minutes March 2026.docx
        |       |           
        |       +---School Board Meeting Agendas
        |       |   +---2025
        |       |   |       AGENDA November 2025.docx
        |       |   |       AGENDA October 2025.docx
        |       |   |       AGENDA January 2025.docx
        |       |   |       AGENDA June 2025.docx
        |       |   |       AGENDA April 2025.docx
        |       |   |       AGENDA December 2025 Regular.docx
        |       |   |       AGENDA March 2025.docx
        |       |   |       AGENDA February 2025.docx
        |       |   |       AGENDA September 2025.docx
        |       |   |       AGENDA May 2025.docx
        |       |   |       AGENDA August 2025 2.docx
        |       |   |       AGENDA July 2025.docx
        |       |   |       
        |       |   +---2022
        |       |   |       school board 2022-11-03 special meeting agenda.pdf
        |       |   |       school board 2022-07-21 meeting agenda.pdf
        |       |   |       school board 2022-09-08 meeting agenda.pdf
        |       |   |       school board 2022-08-18 meeting agenda.pdf
        |       |   |       school board 2022-11-17 meeting agenda.pdf
        |       |   |       school board 2022-09-22 special meeting agenda.pdf
        |       |   |       school board 2022-12-08 meeting agenda.pdf
        |       |   |       school board 2022-10-13 meeting agenda.pdf
        |       |   |       
        |       |   +---2023
        |       |   |       school board 2023-01-12 meeting agenda.pdf
        |       |   |       school board 2023-01-12 special meeting agenda.pdf
        |       |   |       school board 2023-02-16 meeting agenda.pdf
        |       |   |       
        |       |   +---2026
        |       |   |       AGENDA March 2026.docx
        |       |   |       AGENDA February 2026.docx
        |       |   |       AGENDA January 2026.docx
        |       |   |       AGENDA April 2026.docx
        |       |   |       
        |       |   \---2024
        |       |           school board 2024-11-21 meeting agenda.pdf
        |       |           
        |       +---School Board Training
        |       |       Board Member Training.pdf
        |       |       
        |       \---School Board Contacts
        |               School Board Contact Information 2026.docx
        |               
        +---Academics
        |   +---physical-education
        |   +---grade-5
        |   +---grade-8
        |   +---grade-6
        |   +---title-i
        |   +---grade-2
        |   +---grade-7
        |   +---pre-k
        |   +---special-education
        |   +---grade-1
        |   +---grade-4
        |   +---kindergarten
        |   \---grade-3
        +---Cafeteria
        |   +---Breakfast Menu
        |   |       breakfast-menu-2025-12.png
        |   |       
        |   \---Lunch Menu
        |           lunch-menu-2025-12.jpg
        |           
        +---Family
        |   \---PTO
        |           Kell Grade School PTO.pdf
        |           
        +---Calendar
        |   +---Monthly Calendar
        |   |       monthly-calendar-2025-08.pdf
        |   |       monthly-calendar-2025-11.png
        |   |       monthly-calendar-2025-10.png
        |   |       Dec 2025 monthly calendar.png
        |   |       May 2026.pdf
        |   |       
        |   \---Academic Calendar
        |           Academic-Calendar-2025-2026.png
        |           Academic-Calendar-2025-2026.docx
        |           Academic Calendar (2025-2026).pdf
        |           
        +---District
        |   +---Superintendent
        |   +---Principal
        |   \---Employment
        \---Activities
            +---Athletics
            |   +---scholar-bowl
            |   +---cheerleading
            |   +---cross country
            |   +---volleyball
            |   +---bowling
            |   +---basketball
            |   +---baseball
            |   \---athletic-director
            \---Clubs
                +---Yearbook
                \---Student Council
```

///// [KGS] WEBSITE CONTROL PANEL.gsheet /////

/// Google IDs tab ///
```
Key	Value	Display Title	Category	Description	External? 
					
	--- MOST IMPORTANT GOOGLE IDS ---		HEADER		
master_root_folder_id	1o0X89kQL_cXEUpPOSZXcCUFz8bzCtmAp	Master Root Folder	System IDs	ROOT: The top-level 'kgsweb' folder ID	
public_root_folder_id	1L2vOHZlPrDnvXrGVFeTZa2duilKv89IL	Public Root Folder	System IDs	The 'kgsweb_public' folder ID	
inhouse_root_folder_id	1mgpVt3hMmrant40i2b2Irm8w5PRm7Juw	In-House Root Folder	System IDs	The 'kgsweb_inhouse' folder ID	
					
	--- CALENDAR : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_calendar_doc_id	1KFtLFL8clL-dvnMWUs5KW7Uh5vcaHe0LSTHA_FUhWGU	About Calendar ID	Content IDs	Google Doc for the "About KGS Calendars" text	
google_calendar_id	c_35c7f773dea0cc46099f7607201bed993a0a29d94d5456aa00594ed16ffb5071@group.calendar.google.com	Calendar	Content IDs	The public ID for the Google Calendar	
monthly_calendar_folder_id	1j26-htFn1QxdEpRg2eHCVBI34rrtfIwP	Monthly Calendar	Content IDs	Google Folder for Monthly Calendars	
academic_calendar_folder_id	1Mxes5W5ZTrTOl0G1xfHEP2o-IInhZWaJ	Academic Calendar	Content IDs	Google Folder for Academic Calendars	
					
	--- LIVE FEED : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
live_feed_sheet_id	18MuCzAUmPSY8mB2s2NENGgQO39xrkl7uFP5pV2OItrw	Live Feed	Content IDs	Google Sheet for latest announcements	
live_feed_images_folder_id	1NY9OlmSu6pOPn7MKI0ATKTRyuMTbY1R4	Live Feed Images	Assets	Google Drive folder for latest announcements	
					
	--- HOME PAGE : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
hero_image	1O5YEMOsFgjC847QNRBYY-RovLL4o6QGX	Main home page image	Assets	Default image for the homepage hero section	
school_highlights_sheet_id		School Highlights	Content IDs	Google Sheet ID for the School Highlights text	
					
	--- ABOUT KGS : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_school_doc_id	1Zirp9wWczzHzTH0O2vYeiANSKXOP3WjChViNP9F3SlI	About Kell Grade School	Content IDs	Google Doc for the "About Our School" text	
					
	--- SCHOOL BOARD : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_board_doc_id	11P9LYb0ov40zDxi22eqIVgR9r1veVEDJlOzB_N7GL0o	About the School Board	Content IDs	Google Doc for School Board information	
board_docs_folder_id	1B7Ro5cF0cCM9eK2kaQ9J2CD1dmzM5k8g	School Board Documents	Content IDs	Main folder ID for School Board records	
board_agendas_folder_id	1rB95xdrQox1CtzsapWo3cqQF7VVGMfwT	School Board Agendas	Content IDs	School Board Subfolder: Agendas	
board_minutes_folder_id	1wn2OrE-jX8lEwQVRNtZTtfjCLkBoFTtf	School Board Minutes	Content IDs	School Board Subfolder: Minutes	
board_schedule_folder_id	184ONdCgSFpUxm9SBqTiZAGhmjX_AyU6u	School Board Schedule	Content IDs	School Board Subfolder: Schedule	
board_training_folder_id	1RV_4loxC_sJ345QKSYuSaMtZ_Kq3gESR	School Board Documents (Training)	Content IDs	School Board Subfolder: Training	
board_contact_folder_id	13U1JV7AsgdfozYvDe3cdcq1U3EfNTWQj	School Board Documents (Contacts)	Content IDs	School Board Subfolder: Contacts	
					
	--- ROE COMPLIANCE : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_compliance_doc_id			Content IDs	Google Doc for Compliance Documents information	
comp_budget_folder_id	1UCCtUWixcP6jFp2zDziNh1N9V5PL4SFo	Current Itemized Annual Budget	Content IDs	Folder ID for Budget	
comp_soppa_folder_id	1R45eHNqQp42GWlB5BCmvefMgpdgZ7Q1o	District SOPPA Policy (3rd Party Agreements and Parent's Privacy Rights)	Content IDs	Folder ID for SOPPA	
comp_antibias_folder_id	1b3cd7O9bW1ITzGkLECv5SBBf9-nWBfN3	Anti-Bias Education Policies (6.70,6.80,7.10,7.20,7.180)	Content IDs	Folder ID for Anti-Bias Ed Policy	
comp_bullying_folder_id	1sj_4sc-vd9lsBy212sBWDH139Fu6yKU_	Bullying Prevention Policy	Content IDs	Folder ID for Bullying Prevention Policy	
comp_suicide_prev_folder_id	1yqBxeuwxZejovaHMaaikG7ZjWecLb2Mn	Suicide Awareness and Prevention Policy with Resources	Content IDs	Folder ID for Suicide Prevention Policy	
comp_foia_folder_id	12O8q7IFKHh8JulAC4DVn42WeLhIiJOhk	FOIA Officer Information	Content IDs	Folder ID for FOIA Policy	
comp_contracts_25k_folder_id	1OVes0NPHUeDNWfr-oSRujB7iaDVxcw7n	All Contracts over $25,000	Content IDs	Folder ID for Contracts Over $25,000	
comp_cba_folder_id	1gj33jZ82-TAVySwFilXZWmMi3rOoqQgo	Collective Bargaining Agreements	Content IDs	Folder ID for Collective Bargaining Agreements documents	
comp_sex_abuse_folder_id	1PN9YHvZH-HQoIQvnCKMp_I_ipoSunVrh	Sexual Abuse Policy - Faith's Law Policy (4.165)	Content IDs	Folder ID for Sexual Abuse Policy	
comp_compensation_folder_id	1zKXCdWgQr_5Wvf9jrrOruMDqngV4QtI7	Administrator and Teacher Compensation Reports	Content IDs	Folder ID for Compensation Reports	
comp_conduct_folder_id	1mBpQxbR38OC0TZnu3zCXWM8poDeyvvc_	Code of Professional Conduct Policy (5:120)	Content IDs	Folder ID for Code of Conduct Policy	
comp_diabetes_folder_id	1Tq7a6hU4BtX865ASSzvx1vlElmxVHSe0	Type I Diabetes Information for Parents	Content IDs	Folder ID for Diabetes Policy	
					
	--- STAFF DIRECTORY : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
people_list_sheet_id	1ccPlkxJBeeKXcsxyeyF_5e1ktOmb8nRWriK_qkKTXq4	People List	Content IDs	Sheet containing staff names and photos	
people_staff_photos_folder_id	1RKy69v5Z6cAZaVOcbDLEsbz932B42P_C	Staff Photos Folder ID	Content IDs	Folder containing staff photos	
default_staff_photo_image	staff_photo_placeholder_img	Default Staff Photo File ID	Content IDs	Placeholder image for missing staff photos	
					
	--- EMPLOYMENT OPPORTUNITIES : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_employment_doc_id	11ialScCxV3Dzu5HchqkqUCzJDL8mLdNbodeHOvykVOU	Employment Opportunities	Content IDs	Google Doc for Employment Information	
					
	--- DOCUMENTS : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
documents_folder_id	1TQIZDXToKV5tvYNSBnZImTc-PQH-E8mo	Documents	Content IDs	Folder ID for documents	
superintendent_folder_id	1k5hgqxXLgydcxRVO-zqJigAJitLEAe2S	Superintendent	Content IDs	Folder ID for Superintendent documents	
principal_folder_id	1ZBjrGP-YCoqQqBsu8LZWfLvDBk2jzOD3	Principal	Content IDs	Folder ID for Principal documents	
policies_folder_id	19v-p26133YM5SiBO8x4l40b9Z4SC2sBP	Policies	Content IDs	Folder ID for Policies documents	
media_folder_id	1O9OZ5kiQvwAkFNqEQJv2F2YlfVeR2VKZ	Media	Content IDs	Folder ID for Media documents	
forms_folder_id	1PHHDKWyZbbs6PW6I-eDpyqLhyQdXk0QS	Forms	Content IDs	Folder ID for Forms documents	
financial_folder_id	1Oivd4RyLbPhgr5ov4dnFXJ3tMKYsUDB-	Financial Documents	Content IDs	Folder ID for Financial documents	
					
	--- DINING ---		HEADER		
about_dining_doc_id	1Ud9untovTpG1ePFZvTKvFyPI2hPMLKNbPxVdpcygCM4	About KGS Dining	Content IDs	Google Doc for Cafeteria Information	
dining_menus_folder_id	1Qw8t0K8eUbP0w7uB8IU-8SrZaPSUZqxe	Dining Menus	Content IDs	Google Folder for Breakfast and Lunch Menus	
breakfast_menu_folder_id	1wK2IziGzOx8XgeDm0lEJp36k4J0N5Nd8	Breakfast Menu	Content IDs	Google Folder for Breakfast Menus	
lunch_menu_folder_id	1hJpKtrg2-8o3m2lTqXArvEDVzc-kgz7l	Lunch Menu	Content IDs	Google Folder for Lunch Menus	
					
	--- FAMILY : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_family_doc_id	1y2gZBxipLh2gld_tqMq2wnGCmpZV1igp7NlJAiEgZhQ	Family	Content IDs	Google Doc for Family information	
					
	--- PTO : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_pto_doc_id	1i8NOPakDDpJRZDzikGE-YR3o-1TYoHiZG3pdsHOAjKc	Parent Teacher Organization (PTO)	Content IDs	Google Doc for PTO information	
pto_feature_img_folder_id	1M_gJ2tcV2z90bRtWe-c-yWtqpbsedAl1	PTO Feature Image	Content IDs	Google Doc for PTO feature image/doc	
					
	--- REGISTRATION : GOOGLE IDS FOR SITE CONTENT ---		HEADER		
about_registration_doc_id			Content IDs	Google Doc for Registration information	
registration_docs_folder_id	1zfN7i1h-Hn1wWiXemoMDxViCoqr81FKj	About Registration	Content IDs	Google Folder for Registration Documents (like Supply Lists)	
supply_list_doc_id	1gbimyT2pFM01Iry2qDGVecz-ptGAt4DF	Registration Documents	Content IDs	Google Doc for Supply List	
					
	--- ACTIVITIES (ATHLETICS AND CLUBS) ---		HEADER		
about_activities_doc_id		Activities	Content IDs	Google Doc for Activities (Athletics and Clubs) information	
					
	--- ATHLETICS ---		HEADER		
about_athletics_doc_id	1bSlpoXwdnj78olP1RhIWlRCHgSfVXKW-uS_1LQ15xww	About KGS Athletics	Content IDs	Google Doc for Athletics information	
about_baseball_doc_id		Baseball	Content IDs	Google Doc for Baseball information	
baseball_folder_id		Baseball Documents	Content IDs	Folder for schedules/forms If empty, page is hidden	
about_basketball_doc_id		Basketball	Content IDs	Google Doc for Basketball information	
basketball_folder_id		Basketball Documents	Content IDs	Folder for schedules/forms If empty, page is hidden	
about_bowling_doc_id		Bowling	Content IDs	Google Doc for Bowling information	
bowling_folder_id		Bowling Documents	Content IDs	Folder for schedules/forms If empty, page is hidden	
about_cheer_doc_id		Cheer	Content IDs	Google Doc for Cheer information	
cheer_folder_id		Cheer Documents	Content IDs	Folder for schedules/forms If empty, page is hidden	
about_cross_country_doc_id		Cross Country	Content IDs	Google Doc for Baseball information	
cross_country_folder_id		Cross Country Documents	Content IDs	Folder for schedules/forms If empty, page is hidden	
about_volleyball_doc_id		Volleyball	Content IDs	Google Doc for Volleyball information	
volleyball_folder_id		Volleyball Documents	Content IDs	Folder for schedules/forms If empty, page is hidden	
					
	--- CLUBS ---		HEADER		
about_clubs_doc_id	1JvZGs5tpB31_gWQOhqPE61xZRcNY2rOSbtDXL7OIG9g	About KGS Clubs	Content IDs	Google Doc for Clubs information	
about_book_club_doc_id		Book Club	Content IDs	Google Doc for Book Club information	
about_cooking_club_doc_id		Cooking Club	Content IDs	Google Doc for Cooking Club information	
about_scholar_bowl_doc_id		Scholar Bowl	Content IDs	Google Doc for Scholar Bowl information	
about_student_council_doc_id		Student Council	Content IDs	Google Doc for Student Council information	
about_yearbook_doc_id		Yearbook	Content IDs	Google Doc for Student Council information	
					
	--- ACADEMICS ---		HEADER		
about_academics_doc_id	1bQL5uJV1Vbd0e19Zr4aZoJYgZzfEsBYcRW_1vzp73YU	About KGS Academics	Content IDs	Google Doc for Academics information	
prek_folder_id		Pre-K	Content IDs	Pre-K folder  ID	
k_folder_id		Kindergarten	Content IDs	Kindergarten folder  ID	
gr1_folder_id		Grade 1	Content IDs	Grade 1 folder  ID	
gr2_folder_id		Grade 2	Content IDs	Grade 2 folder  ID	
gr3_folder_id		Grade 3	Content IDs	Grade 3 folder  ID	
gr4_folder_id		Grade 4	Content IDs	Grade 4 folder  ID	
gr5_folder_id		Grade 5	Content IDs	Grade 5 folder ID	
jh_ela_folder_id		Junior High ELA	Content IDs	Junior High ELA folder  ID	
jh_math_folder_id		Junior High Math	Content IDs	Junior High Math folder  ID	
jh_science_folder_id		Junior High Science	Content IDs	Junior High Science folder  ID	
sped_folder_id		Special Education	Content IDs	Special Education folder  ID	
title1		Title I	Content IDs	Title I folder ID	
```

/// Links tab ///
```
Key	Value	Display Title	Category	Description	External? 	File Filter	Icon Class
							
	--- FACEBOOK LINKS ---		HEADER				
school_facebook_url	https://www.facebook.com/KellCSD2	Follow us on Facebook	Facebook Links	Link to the main school Facebook page.	TRUE		
pto_facebook_url	https://www.facebook.com/groups/1162635645047071	Follow KGS PTO on Facebook	Facebook Links	Link to the PTO Facebook page.	TRUE		
							
	--- HEADER LINK ---		HEADER				
teacherease_url	https://www.teacherease.com/common/login.aspx	TeacherEase	Header Links	Login URL for TeacherEase.	TRUE		
							
	--- FOOTER : QUICK LINKS		HEADER				
Staff Directory	/district/staff-directory	Staff Directory	Footer Links: Quick Links	Link to Staff Directory page	FALSE		
Lunch Menu	/dining	Lunch Menu	Footer Links: Quick Links	Link to Dining page	FALSE		
report_card_url	https://www.illinoisreportcard.com/School.aspx?schoolid=130580020032001	Illinois Report Card	Footer Links: Quick Links	Link to the Illinois Report Card site.	TRUE		
able_url	https://ablenrc.org	ABLE - Achieving a Better Life Experience	Footer Links: Quick Links	Link to ABLE resources.	TRUE		
lda_iep_504_url	https://ldaillinois.org/ieps-vs-504-plans/	Special Education and Section 504 plans	Footer Links: Quick Links	Link to LDA IEP vs 504 plans	TRUE		
ROE Website Compliance	/district/compliance/	ROE Website Compliance	Footer Links: Quick Links	Link to ROE Website Compliance links	FALSE		
							
	--- FOOTER : SAFETY AND RESOURCES LINKS		HEADER				
safe2help_url	https://safe2helpil.com	Illinois Suicide Prevention	Footer Links: Safety and Resources	Link to Safe2Help Illinois.	TRUE		
lifeline_url	https://988lifeline.org	National Suicide Prevention Lifeline	Footer Links: Safety and Resources	Link to Suicide & Crisis Lifeline.	TRUE		
							
	--- ATHLETICS PAGE LINKS ---		HEADER				
baseball_site_url		Baseball	Athletics Page Links	Google Site for Baseball	TRUE		
baseball_site_url		Baseball	Athletics Page Links	Google Site for Baseball	TRUE		
basketball_site_url		Basketball	Athletics Page Links	Google Site for Basketball	TRUE		
bowling_site_url		Bowling	Athletics Page Links	Google Site for Bowling	TRUE		
cheer_site_url		Cheer	Athletics Page Links	Google Site for Cheer	TRUE		
cross_country_site_url		Cross Country	Athletics Page Links	Google Site for Cross Country	TRUE		
volleyball_site_url		Volleyball	Athletics Page Links	Google Site for Volleyball	TRUE		
							
	--- CLUBS PAGE LINKS ---		HEADER				
book_club_site_url		Book Club	Clubs Page Links	Google Site for Book Club	TRUE		
cooking_club_site_url		Cooking Club	Clubs Page Links	Google Site for Cooking Club	TRUE		
scholar_bowl_site_url		Scholar Bowl	Clubs Page Links	Google Site for Scholar Bowl	TRUE		
student_council_site_url		Student Council	Clubs Page Links	Google Site for Student Council	TRUE		
yearbook_site_url		Yearbook	Clubs Page Links	Google Site for Yearbook	TRUE		
							
	--- ACADEMICS PAGE LINKS ---		HEADER				
prek_site_url		Pre-K	Academics Page Links	Google Site for Prek	TRUE		
kindergarten		Kindergarten	Academics Page Links	Google Site for Kindergarten	TRUE		
gr1_site_url		Grade 1	Academics Page Links	Google Site for Grade 1	TRUE		
gr2_site_url		Grade 2	Academics Page Links	Google Site for Grade 2	TRUE		
gr3_site_url		Grade 3	Academics Page Links	Google Site for Grade 3	TRUE		
gr4_site_url		Grade 4	Academics Page Links	Google Site for Grade 4	TRUE		
gr5_site_url		Grade 5	Academics Page Links	Google Site for Grade 5	TRUE		
jh_ela_site_url		Junior High ELA	Academics Page Links	Google Site for Junior High ELA	TRUE		
jh_math_site_url		Junior High Math	Academics Page Links	Google Site for Junior High Math	TRUE		
jh_science_site_url		Junior High Science	Academics Page Links	Google Site for Junior High Science	TRUE		
sped_site_url		Special Education	Academics Page Links	Google Site for Special Education	TRUE		
title1_site_url		Title 1	Academics Page Links	Google Site for Title 1	TRUE		
							
	--- COMPLIANCE DOCUMENTS LINKS ---						
comp_board_schedule	@board_schedule_folder_id	Schedule of Regular Board Meetings with Dates, Times, Locations	Compliance Links	5 ILCS 120/2.02	FALSE		
comp_board_agendas	/district/school-board/school-board-agendas/	Agendas for Board Meetings	Compliance Links	5 ILCS 120/2.02	FALSE		
comp_board_minutes	/district/school-board/school-board-minutes/	Minutes from Past Meetings for at Least 60 Days	Compliance Links	5 ILCS 120/2.06	FALSE		
comp_board_training	@board_training_folder_id	List of All Board Members and Leadership Training Completion	Compliance Links	105 ILCS 5/10-16a	FALSE		
comp_board_contact	@board_contact_folder_id	School Board Members Contact Email (School Account)	Compliance Links	50 ILCS 205/20	FALSE		
comp_report_card	https://www.illinoisreportcard.com/School.aspx?schoolid=130580020032001	School District Report Card - Link to District IIRC	Compliance Links	105 ILCS 5/10-17a	TRUE		
comp_budget	@comp_budget_folder_id	Current Itemized Annual Budget	Compliance Links	105 ILCS 5/17-1.2	FALSE		
comp_soppa	/district/documents/policies/soppa/	District SOPPA Policy (3rd Party Agreements and Parent's Privacy Rights)	Compliance Links	105 ILCS 85/27	FALSE		
comp_antibias	@comp_antibias_folder_id	Anti-Bias Education Policies (6.70,6.80,7.10,7.20,7.180)	Compliance Links	105 ILCS 5/27-23.6	FALSE		
comp_bullying	@comp_bullying_folder_id	Bullying Prevention Policy	Compliance Links	105 ILCS 5/27-23.7	FALSE		
comp_suicide_prev	@comp_suicide_prev_folder_id	Suicide Awareness and Prevention Policy with Resources	Compliance Links	105 ILCS 5/2-3.166	FALSE		
comp_foia	@comp_foia_folder_id	FOIA Officer Information	Compliance Links	5 ILCS 140/4	FALSE		
comp_contracts_25k	@comp_contracts_25k_folder_id	All Contracts over $25,000	Compliance Links	105 ILCS 5/10-20.44	FALSE		
comp_cba	@comp_cba_folder_id	Collective Bargaining Agreements	Compliance Links	105 ILCS 5/10-20.44	FALSE		
comp_sex_abuse	@comp_sex_abuse_folder_id	Sexual Abuse Policy - Faith's Law Policy (4.165)	Compliance Links	105 ILCS 5/22-85.5 	FALSE		
comp_compensation	@comp_compensation_folder_id	Administrator and Teacher Compensation Reports	Compliance Links	105 ILCS 5/10-20.47	FALSE		
comp_sped_504	https://ldaillinois.org/ieps-vs-504-plans/	Special Education and Section 504 Plans	Compliance Links	105 ILCS 5/14-6.01	TRUE		
comp_conduct	@comp_conduct_folder_id	Code of Professional Conduct Policy (5:120)	Compliance Links	105 ILCS 5/22-85.5	FALSE		
comp_diabetes	@comp_diabetes_folder_id	Type I Diabetes Information for Parents	Compliance Links	PA103-0641	FALSE		
comp_able	https://www.ablenrc.org/	Link to ABLE (Achieving a Better Life Experience) NEW FY27	Compliance Links	PA 104-0314	TRUE		
```

/// Site Settings tab ///
```
Key	Value	Display Title	Category	Description	External? 
					
	--- SITE IDENTITY ---		HEADER		
site_name	Kell Grade School		Identity	The primary name used in the browser title and navigation.	
district_name	Kell Consolidated School District #2		Identity	The official district name used in the footer copyright.	
principal_name	John Smith		Identity	Principal's name.	
secretary_name	Freda Schmidt	Identity	Secretary's name.	
					
	--- CONTACT INFO ---		HEADER		
address	207 N Johnson St, Kell, IL 62853		Contact	Physical school address.	
phone	618-822-6234		Contact	Main office phone number.	
fax	618-822-6733		Contact	Office fax number.	
email	contact@kellgradeschool.com		Contact	General inquiry email address.	
					
	--- DATA FOR THE SITE ---		HEADER		
weather_location	62853		Content IDs	City, State OR zipcode for weather report	
					
	--- SITE BRANDING COLORS ---		HEADER		
color_primary	#015BA7		Branding	The main theme color (Kell Blue).	
color_secondary	#002366		Branding	The secondary theme color (Midnight Navy).	
color_accent	#87d3f8		Branding	The highlight color for links and icons (Sky Blue).	
					
	--- SITE BRANDING IMAGES ---		HEADER		
site_favicon_img_ID	1bqWe6AQZ-fwpwAQozksi4qzgT53Da36s		Assets	Image ID used for the navigation bar background.	FALSE
nav_bg_img_ID	1ZvcoxoqAaq10KPMsAoLWP71-dXazxBBo		Assets	Image ID used for the navigation bar background.	FALSE
indian_head_img_ID	1reERKYZJsy1hkLVsxwV_rgDgSvk89eC1		Assets	Image ID used for the site logo.	FALSE
staff_photo_placeholder_img_id	1s5Oa2X34Kn23PDjtEMhjRsoAIslZqwuG		Assets	Image ID used as a staff photo placeholder.	FALSE
					
	--- SITE CONFIGURATION ---		HEADER		
config_sheet_id	1zkL8AdBnHtnDOQeGLrXnjSgPET9SFEdB3RJv7DkVNpM		System IDs	CRITICAL: The ID of THIS Google Sheet.	
route_aliases_sheet_id	1zkL8AdBnHtnDOQeGLrXnjSgPET9SFEdB3RJv7DkVNpM		System IDs	The ID of the Google Sheet containing URL aliases.	
					
	-- HERO BANNER -- 		HEADER		
hero_img	1YtFrS1cgzMgm23drDAXrxJSBWe7P_ohj		Assets	Default image for the homepage hero section.	FALSE
hero_title	Welcome to Kell Grade School		Hero Content		
hero_subtitle	Home of the Indians		Hero Content		
					
	-- SHOW SECTIONS -- 		HEADER		
show_hero_section	TRUE		Toggle	Set to FALSE to hide the Homepage banner.	
show_hero_img	TRUE		Toggle	Set to FALSE to hide the big image on the Homepage banner.	
show_hero_title	FALSE		Toggle	Set to FALSE to hide the title text on the Homepage banner.	
show_hero_subtitle	FALSE		Toggle	Set to FALSE to hide the subtitle text on the Homepage banner.	
show_live_feed	TRUE		Toggle	Set to FALSE to hide the Announcements section.	
show_highlights	TRUE		Toggle	Set to FALSE to hide the School Highlights section.	
					
	-- SHOW NAVIGATION MENU LINKS -- 		HEADER		
show_home_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for Home	
show_district_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for District	
show_academics_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for Academics	
show_calendar_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for Calendar	
show_dining_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for Dining	
show_news_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for News	
show_activities_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for Athletics	
show_family_nav_link	TRUE		Toggle	Set to FALSE to hide the nav link for Family	
```

/// Widgets tab ///
```
Key	Value	Display Title	Category	Description	Width out of 12
	-- HOME ROW 1 -- 		HEADER		3, 4, 6, 8. or 12
home_row_1_slot_1	School Facebook	KellCSD2 on Facebook	Widgets	Select a widget to display on the Homepage	8
home_row_1_slot_2	None		Widgets	Select a widget to display on the Homepage	
home_row_1_slot_3	Local Weather	Local Weather	Widgets	Select a widget to display on the Homepage	4
home_row_1_slot_4	None		Widgets	Select a widget to display on the Homepage	
					
	-- HOME ROW 2 -- 		HEADER		
home_row_2_slot_1	None	Live Feed	Widgets	Select a widget to display on the Homepage	6
home_row_2_slot_2	None		Widgets	Select a widget to display on the Homepage	
home_row_2_slot_3	None	Google Calendar	Widgets	Select a widget to display on the Homepage	6
home_row_2_slot_4	None		Widgets	Select a widget to display on the Homepage	
					
	-- HOME ROW 3 -- 		HEADER		
home_row_3_slot_1	None		Widgets	Select a widget to display on the Homepage	
home_row_3_slot_2	None		Widgets	Select a widget to display on the Homepage	
home_row_3_slot_3	None		Widgets	Select a widget to display on the Homepage	
home_row_3_slot_4	None		Widgets	Select a widget to display on the Homepage	
					
	-- DISTRICT ROW 1 -- 		HEADER		
district_row_1_slot_1	None		Widgets	Select a widget to display on the District page	
district_row_1_slot_2	None		Widgets	Select a widget to display on the District page	
district_row_1_slot_3	None		Widgets	Select a widget to display on the District page	
district_row_1_slot_4	None		Widgets	Select a widget to display on the District page	
					
	-- BOARD ROW 1 -- 				
board_row_1_slot_1	School Board Members	Board Members	Widgets	Select a widget to display on the School Board page	6
board_row_1_slot_2	None		Widgets	Select a widget to display on the School Board page	
board_row_1_slot_3	School Board Documents	Board Documents	Widgets	Select a widget to display on the School Board page	6
board_row_1_slot_4	None		Widgets	Select a widget to display on the School Board page	
					
	-- DOCS ROW 1 -- 		HEADER		
docs_row_1_slot_1	None		Widgets	Select a widget to display on the Documents page	
docs_row_1_slot_2	None		Widgets	Select a widget to display on the Documents page	
docs_row_1_slot_3	None		Widgets	Select a widget to display on the Documents page	
docs_row_1_slot_4	None		Widgets	Select a widget to display on the Documents page	
					
	-- DOCS ROW 2 -- 		HEADER		
docs_row_2_slot_1	None		Widgets	Select a widget to display on the Documents page	
docs_row_2_slot_2	None		Widgets	Select a widget to display on the Documents page	
docs_row_2_slot_3	None		Widgets	Select a widget to display on the Documents page	
docs_row_2_slot_4	None		Widgets	Select a widget to display on the Documents page	
					
	-- DINING ROW 1 -- 		HEADER		
dining_row_1_slot_1	None		Widgets	Select a widget to display on the Dining page	
dining_row_1_slot_2	None		Widgets	Select a widget to display on the Dining page	
dining_row_1_slot_3	None		Widgets	Select a widget to display on the Dining page	
dining_row_1_slot_4	None		Widgets	Select a widget to display on the Dining page	
					
	-- NEWS ROW 1 --		HEADER		
news_row_1_slot_1	None		Widgets	Select a widget to display on the News page	
news_row_1_slot_2	None		Widgets	Select a widget to display on the News page	
news_row_1_slot_3	None		Widgets	Select a widget to display on the News page	
news_row_1_slot_4	None		Widgets	Select a widget to display on the News page	
					
	-- FAMILY ROW 1 -- 		HEADER		
family_row_1_slot_1	None		Widgets	Select a widget to display on the Family page	
family_row_1_slot_2	None		Widgets	Select a widget to display on the Family page	
family_row_1_slot_3	None		Widgets	Select a widget to display on the Family page	
family_row_1_slot_4	None		Widgets	Select a widget to display on the Family page	
					
	-- PTO ROW 1 -- 		HEADER		
pto_row_1_slot_1	About PTO		Widgets	Select a widget to display on the PTO page	6
pto_row_1_slot_2	None		Widgets	Select a widget to display on the PTO page	
pto_row_1_slot_3	PTO Feature Image	...	Widgets	Select a widget to display on the PTO page	6
pto_row_1_slot_4	None		Widgets	Select a widget to display on the PTO page	
					
	-- CONTACT ROW 1 -- 		HEADER		
contact_row_1_slot_1	None		Widgets	Select a widget to display on the Contact page	
contact_row_1_slot_2	None		Widgets	Select a widget to display on the Contact page	
contact_row_1_slot_3	None		Widgets	Select a widget to display on the Contact page	
contact_row_1_slot_4	None		Widgets	Select a widget to display on the Contact page	
					
	-- CALENDAR ROW 1 -- 		HEADER		
calendar_row_1_slot_1	Monthly Calendar		Widgets	Select a widget to display on the Calendar page	12
calendar_row_1_slot_2	None		Widgets	Select a widget to display on the Calendar page	
calendar_row_1_slot_3	None		Widgets	Select a widget to display on the Calendar page	
calendar_row_1_slot_4	None		Widgets	Select a widget to display on the Calendar page	
					
	-- CALENDAR ROW 2 -- 				
calendar_row_2_slot_1	Academic Calendar		Widgets	Select a widget to display on the Calendar page	12
calendar_row_2_slot_2	None		Widgets	Select a widget to display on the Calendar page	
calendar_row_2_slot_3	None		Widgets	Select a widget to display on the Calendar page	
calendar_row_2_slot_4	None		Widgets	Select a widget to display on the Calendar page	
					
	-- CALENDAR ROW 3 -- 		HEADER		
calendar_row_3_slot_1	Calendar Feed		Widgets	Select a widget to display on the Calendar page	12
calendar_row_3_slot_2	None		Widgets	Select a widget to display on the Calendar page	
calendar_row_3_slot_3	None		Widgets	Select a widget to display on the Calendar page	
calendar_row_3_slot_4	None		Widgets	Select a widget to display on the Calendar page	
					
	--ACADEMICS ROW 1 -- 		HEADER		
academics_row_1_slot_1	None		Widgets	Select a widget to display on the Academics page	
academics_row_1_slot_2	None		Widgets	Select a widget to display on the Academics page	
academics_row_1_slot_3	None		Widgets	Select a widget to display on the Academics page	
academics_row_1_slot_4	None		Widgets	Select a widget to display on the Academics page	
					
	-- ACTIVITIES PAGE ROW 1 -- 		HEADER		
activities_row_1_slot_1	None		Widgets	Select a widget to display on the Activities page	
activities_row_1_slot_2	None		Widgets	Select a widget to display on the Activities page	
activities_row_1_slot_3	None		Widgets	Select a widget to display on the Activities page	
activities_row_1_slot_4	None		Widgets	Select a widget to display on the Activities page	
					
	--ATHLETICS ROW 1 -- 		HEADER		
athletics_row_1_slot_1	None		Widgets	Select a widget to display on the Athletics page	
athletics_row_1_slot_2	None		Widgets	Select a widget to display on the Athletics page	
athletics_row_1_slot_3	None		Widgets	Select a widget to display on the Athletics page	
athletics_row_1_slot_4	None		Widgets	Select a widget to display on the Athletics page	
					
	-- CLUBS ROW 1 -- 		HEADER		
clubs_row_1_slot_1	None		Widgets	Select a widget to display on the Clubs page	
clubs_row_1_slot_2	None		Widgets	Select a widget to display on the Clubs page	
clubs_row_1_slot_3	None		Widgets	Select a widget to display on the Clubs page	
clubs_row_1_slot_4	None		Widgets	Select a widget to display on the Clubs page	
					
	-- JOBS ROW 1 -- 		HEADER		
employment_row_1_slot_1	None		Widgets	Select a widget to display on the Jobs page	
employment_row_1_slot_2	None		Widgets	Select a widget to display on the Jobs page	
employment_row_1_slot_3	None		Widgets	Select a widget to display on the Jobs page	
employment_row_1_slot_4	None		Widgets	Select a widget to display on the Jobs page	
					
	-- QUICKLINKS ROW 1 -- 		HEADER		
quicklinks_row_1_slot_1	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
quicklinks_row_1_slot_2	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
quicklinks_row_1_slot_3	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
quicklinks_row_1_slot_4	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
					
	-- QUICKLINKS ROW 2-- 		HEADER		
quicklinks_row_2_slot_1	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
quicklinks_row_2_slot_2	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
quicklinks_row_2_slot_3	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
quicklinks_row_2_slot_4	None		Link Tile	Select a QuickLink to display in the Quicklinks Row	3
```

/// Widget Registry tab ///
```
Key	Friendly Name	Component	Data Source	Parameter
none	None	none		
widget_facebook	School Facebook	facebook-feed	@school_facebook_url	url
widget_calendar	Google Calendar	calendar-embed	@google_calendar_id	calendar_id
widget_monthly_calendar	Monthly Calendar	latest-file-view	@monthly_calendar_folder_id	folder_id
widget_academic_calendar	Academic Calendar	latest-file-view	@academic_calendar_folder_id	folder_id
				
widget_livefeed	Live Feed	live-feed	@live_feed_sheet_id	doc_id
widget_weather	Local Weather	weather	@weather_location	location
widget_quick_list	Quick Links List	link-list	Sidebar Links: Clubs	category
widget_quick_tiles	Quick Links Tiles	link-tiles	Sidebar Links: Home	category
widget_staff	Staff Directory	people-list	staff	type
				
widget_board_members	School Board Members	people-list	board	type
widget_board_docs	School Board Documents	file-list	@board_docs_folder_id	folder_id
				
widget_about_pto	About PTO	google-doc-content	@about_pto_doc_id	doc_id
widget_pto_feature_img	PTO Feature Image	latest-file-view	@pto_feature_img_folder_id	folder_id
```

/// URL Aliases tab ///
```
Key	Value	Display Title	Category	Description	External? 
	--- QUICK ACCESS LINKS ---		HEADER		
board	district/school-board	School Board	Redirect	Short link for board info.	FALSE
staff	district/staff-directory	Staff Directory	Redirect	Short link for directory.	FALSE
menus	dining	Menus	Redirect	Short link for lunch/breakfast.	FALSE
sports	activities/athletics	Athletics	Redirect	Short link for sports home.	FALSE
cal	calendar	District Calendar	Redirect	Short link for the calendar.	FALSE
docs	district/documents	Public Documents	Redirect	Short link for public documents	FALSE
compliance	district/compliance/	ROE Website Compliance	Redirect	Short link for website compliance	FALSE
board-agendas	district/school-board/school-board-agendas/	School Board Agendas	Redirect	Short link for board agendas	FALSE
board-minutes	district/school-board/school-board-minutes/	School Board Minutes	Redirect	Short link for board minutes	FALSE
soppa	district/documents/policies/soppa/	SOPPA	Redirect	Short link for soppa	FALSE
```

/// Navigation tab ///
```
Key	Label	Parent Category	Show in Menu?	Icon Class
nav_district	District		TRUE	
nav_documents	Documents	District	TRUE	
nav_board	School Board	District	TRUE	
nav_staff	Staff Directory	District	TRUE	
nav_employment	Employment	District	TRUE	
nav_compliance	Compliance	District	TRUE	
nav_calendar	Calendar		TRUE	
nav_dining	Dining		TRUE	
nav_news	News		FALSE	
nav_academics	Academics		FALSE	
nav_prek	Pre-K	Academics	TRUE	
nav_k	Kindergarten	Academics	TRUE	
nav_grade_1	Grade 1	Academics	TRUE	
nav_grade_2	Grade 2	Academics	TRUE	
nav_grade_3	Grade 3	Academics	TRUE	
nav_grade_4	Grade 4	Academics	TRUE	
nav_grade_5	Grade 5	Academics	TRUE	
nav_grade_6	Grade 6	Academics	TRUE	
nav_grade_7	Grade 7	Academics	TRUE	
nav_grade_8	Grade 8	Academics	TRUE	
nav_jh_ela	Jr High ELA	Academics	TRUE	
nav_jh_math	Jr High Math	Academics	TRUE	
nav_jh_science	Jr High Science	Academics	TRUE	
nav_jh_social_studies	Jr High Social Studies	Academics	TRUE	
nav_activities	Activities		TRUE	
nav_athletics	Athletics	Activities	TRUE	
nav_baseball	Baseball	Athletics	TRUE	
nav_basketball	Basketball	Athletics	TRUE	
nav_bowling	Bowling	Athletics	TRUE	
nav_cheer	Cheerleading	Athletics	TRUE	
nav_cross_country	Cross Country	Athletics	TRUE	
nav_volleyball	Volleyball	Athletics	TRUE	
nav_clubs	Clubs	Activities	TRUE	
nav_book_club	Book Club	Clubs	TRUE	
nav_brain_games	Brain Games	Clubs	TRUE	
nav_cooking_club	Cooking Club	Clubs	TRUE	
nav_scholar_bowl	Scholar Bowl	Clubs	TRUE	
nav_student_council	Student Council	Clubs	TRUE	
nav_yearbook	Yearbook	Clubs	TRUE	
nav_family	Family		TRUE	
nav_pto	PTO	Family	TRUE	
nav_contact	Contact		TRUE	
```

/// Icon Map tab ///
```
Key	Icon Name	Category
compliance	scale-balanced	Administration
employment	briefcase	Administration
policies	file-shield	Administration
soppa	user-shield	Administration
announcement	bell	Alerts
attention	triangle-exclamation	Alerts
deadline	stopwatch	Alerts
[... etc...]
```

/// Link Tile Registry tab ///
```
Key	URL	Label	Icon Class	Color Class	Description
tile_lunch	/dining	Lunch Menu	bi-egg-fried	btn-success	Big green lunch button
tile_bus	/transport	Bus Routes	bi-bus-front	btn-primary	Link to bus schedules
tile_grades	https://teacherease.com	Grades	bi-person-badge	btn-info	TeacherEase direct link
tile_board	/board	School Board	bi-people	btn-secondary	Link to board page
```


///// [KGS] PEOPLE DIRECTORY.gsheet /////
```
Title	First Name	Last Name	Email	Role	Image ID	Bio	Website Editor? (TRUE/FALSE)	Category	Context (staff or board)
Mrs.	Jane	Doe	jjanedoeeee@yahoo.com	President	staff_photo_placeholder_img		TRUE	Board of Education	board
Mr.	John	Smith	johnsmith@kellgradeschool.com	Superintendent/ Principal	smith-john		TRUE	Administration	staff
Mrs.	Sarah	Brown	sbrown@kellgradeschool.com	2nd/3rd Grade Teacher	brown-sarah		TRUE	Elementary Faculty	staff
[... etc...]
```
END OF SOURCE OF TRUTH. This document contains the full logic, mapping, and
safety requirements to maintain the Kell Grade School CMS.
